<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ListingService
{
    private const FIELDS = [
        'game_id', 'category_id', 'title', 'description', 'price', 'status',
        'capacity', 'session_duration_minutes', 'delivery_conditions', 'timezone',
    ];

    public function forSeller(User $seller): LengthAwarePaginator
    {
        return $seller->listings()->whereNull('archived_at')->with(['game', 'category', 'primaryImage'])
            ->latest()->orderByDesc('id')->paginate(10);
    }

    public function formData(?Listing $listing = null): array
    {
        return [
            'listing' => $listing,
            'games' => Game::where('active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function create(User $seller, array $data): Listing
    {
        return $this->save($seller, null, $data);
    }

    public function update(User $user, Listing $listing, array $data): Listing
    {
        return $this->save($user, $listing, $data);
    }

    public function toggleStatus(User $user, Listing $listing): string
    {
        return DB::transaction(function () use ($user, $listing): string {
            $record = Listing::query()->lockForUpdate()->findOrFail($listing->id);
            Gate::forUser($user)->authorize('changeStatus', $record);
            $status = $record->status === 'publicado' ? 'pausado' : 'publicado';
            if ($status === 'publicado') {
                $this->validateOffer($record->only(self::FIELDS));
            }
            $record->update(['status' => $status]);

            return $status;
        });
    }

    public function archive(User $user, Listing $listing): void
    {
        DB::transaction(function () use ($user, $listing): void {
            $record = Listing::query()->lockForUpdate()->findOrFail($listing->id);
            Gate::forUser($user)->authorize('delete', $record);
            $record->update(['archived_at' => now()]);
        });
    }

    private function save(User $user, ?Listing $listing, array $data): Listing
    {
        $storedFiles = [];
        try {
            return DB::transaction(function () use ($user, $listing, $data, &$storedFiles): Listing {
                $record = $listing === null ? new Listing() : Listing::query()->lockForUpdate()->findOrFail($listing->id);
                Gate::forUser($user)->authorize($record->exists ? 'update' : 'create', $record->exists ? $record : Listing::class);
                $category = $this->validateOffer($data);
                $attributes = Arr::only($data, self::FIELDS);
                $attributes['status'] = $data['status'] ?? 'publicado';
                if ($category->type === 'cosmetic') {
                    $attributes['capacity'] = 1;
                    $attributes['session_duration_minutes'] = null;
                    $attributes['timezone'] = null;
                }
                if ($record->exists && $record->orderItems()->exists()) {
                    foreach (['game_id', 'category_id', 'capacity', 'session_duration_minutes', 'delivery_conditions', 'timezone'] as $field) {
                        if ((string) $record->$field !== (string) ($attributes[$field] ?? null)) {
                            throw ValidationException::withMessages([
                                $field => 'Este anúncio possui pedidos. Crie outro anúncio para alterar o tipo, a capacidade ou as condições contratadas.',
                            ]);
                        }
                    }
                }
                if (! $record->exists) {
                    $attributes['seller_id'] = $user->id;
                    $attributes['slug'] = Str::limit(Str::slug($data['title']), 140, '').'-'.Str::uuid();
                }
                $record->fill($attributes)->save();
                $existing = $record->images()->get();
                $retainedIds = array_map('intval', $listing === null ? [] : ($data['retained_images'] ?? []));
                $retained = $existing->whereIn('id', $retainedIds)->values();
                if ($retained->count() !== count($retainedIds)) {
                    throw ValidationException::withMessages(['images' => 'A galeria mudou. Recarregue a página antes de salvar.']);
                }
                $gallery = $retained->map(fn ($image): array => ['token' => 'existing:'.$image->id, 'image' => $image])->all();
                $disk = (string) config('filesystems.default', 'public');
                $disk = $disk === 'local' ? 'public' : $disk;
                foreach ($data['images'] ?? [] as $index => $file) {
                    $directory = 'listings/'.$record->id;
                    $filename = Str::uuid().'.'.$file->extension();
                    $storedFiles[] = ['disk' => $disk, 'path' => $directory.'/'.$filename];
                    $path = $file->storePubliclyAs($directory, $filename, $disk);
                    if ($path === false) {
                        throw ValidationException::withMessages(['images' => 'Não foi possível salvar as fotos. Tente novamente.']);
                    }
                    $gallery[] = ['token' => 'new:'.$index, 'image' => $record->images()->make(['image_path' => $path, 'disk' => $disk])];
                }
                if (count($gallery) < 1 || count($gallery) > 6) {
                    throw ValidationException::withMessages(['images' => 'Mantenha entre 1 e 6 fotos no anúncio.']);
                }
                $primary = $data['primary_image'] ?? $gallery[0]['token'];
                if (! in_array($primary, array_column($gallery, 'token'), true)) {
                    throw ValidationException::withMessages(['primary_image' => 'Escolha uma foto válida para a capa.']);
                }
                foreach ($gallery as $position => $entry) {
                    $entry['image']->fill(['is_primary' => $entry['token'] === $primary, 'display_order' => $position])->save();
                }
                $removed = $existing->whereNotIn('id', $retainedIds);
                $obsoleteFiles = $removed->map(fn ($image): array => [
                    'disk' => $image->disk ?? config('filesystems.default'), 'path' => $image->image_path,
                ])->all();
                if ($removed->isNotEmpty()) {
                    $record->images()->whereIn('id', $removed->modelKeys())->delete();
                    DB::afterCommit(fn () => $this->deleteFiles($obsoleteFiles));
                }

                return $record->load('images');
            });
        } catch (Throwable $exception) {
            $this->deleteFiles($storedFiles);
            throw $exception;
        }
    }

    private function validateOffer(array $data): Category
    {
        $category = Category::find($data['category_id']);
        if (! Game::whereKey($data['game_id'])->where('active', true)->exists()
            || $category === null || ! $category->is_active
            || ($category->game_id !== null && $category->game_id !== (int) $data['game_id'])) {
            throw ValidationException::withMessages(['category_id' => 'Selecione um jogo ativo e uma categoria compatível e ativa.']);
        }
        if ($category->type === 'service' && ((int) ($data['capacity'] ?? 0) < 1
            || (int) ($data['session_duration_minutes'] ?? 0) < 1 || empty($data['timezone']) || empty($data['delivery_conditions']))) {
            throw ValidationException::withMessages(['capacity' => 'Complete a capacidade, duração, condições e fuso do coaching antes de publicar.']);
        }

        return $category;
    }

    private function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            if (str_starts_with($file['path'], 'http://') || str_starts_with($file['path'], 'https://')) {
                continue;
            }
            try {
                if (Storage::disk($file['disk'])->delete($file['path'])) {
                    continue;
                }
            } catch (Throwable) {
                // A failed cleanup must not undo an already committed gallery.
            }
            Log::warning('Não foi possível remover uma foto de anúncio do armazenamento.', ['disk' => $file['disk']]);
        }
    }
}
