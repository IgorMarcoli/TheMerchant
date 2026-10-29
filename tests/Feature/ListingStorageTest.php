<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\ListingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ListingStorageTest extends TestCase
{
    private User $seller;
    private array $data;

    protected function setUp(): void
    {
        parent::setUp();
        // Real commit callbacks require a test without RefreshDatabase's outer transaction.
        // Refuse to migrate any connection other than this disposable in-memory database.
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->artisan('migrate:fresh')->assertSuccessful();
        Storage::fake('public');
        config(['filesystems.default' => 'public']);
        $this->seller = User::factory()->seller()->create();
        $game = Game::create(['name' => 'Jogo', 'slug' => 'storage-game']);
        $category = Category::create(['name' => 'Skins', 'slug' => 'storage-skins', 'game_id' => $game->id]);
        $this->data = ['game_id' => $game->id, 'category_id' => $category->id, 'title' => 'Oferta com fotos',
            'description' => 'Descrição detalhada de uma oferta.', 'price' => '10.00', 'status' => 'publicado',
            'capacity' => 1, 'delivery_conditions' => 'Entrega combinada pelo chat.', 'images' => [$this->photo()]];
    }

    public function test_replaced_files_are_removed_after_commit_and_retained_file_remains(): void
    {
        $service = app(ListingService::class);
        $listing = $service->create($this->seller, array_replace($this->data, ['images' => [$this->photo(), $this->photo()]]));
        $old = $listing->images;
        $service->update($this->seller, $listing, array_replace($this->data, ['retained_images' => [$old[1]->id]]));
        Storage::disk('public')->assertMissing($old[0]->image_path);
        Storage::disk('public')->assertExists($old[1]->image_path);
        $this->assertCount(2, Storage::disk('public')->allFiles());
        $this->assertCount(2, $listing->fresh()->images);
    }

    public function test_partial_upload_failure_rolls_back_listing_and_removes_new_files(): void
    {
        $disk = Storage::disk('public');
        $proxy = Mockery::mock($disk)->makePartial();
        $calls = 0;
        $proxy->shouldReceive('putFileAs')->twice()->andReturnUsing(function (...$arguments) use ($disk, &$calls) {
            $path = $disk->putFileAs(...$arguments);

            return ++$calls === 1 ? $path : false;
        });
        Storage::set('public', $proxy);
        try {
            app(ListingService::class)->create($this->seller, array_replace($this->data, ['images' => [$this->photo(), $this->photo()]]));
            $this->fail('The second upload must fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('images', $exception->errors());
        }
        $this->assertDatabaseCount('listings', 0);
        $this->assertDatabaseCount('listing_images', 0);
        $this->assertSame([], $disk->allFiles());
    }

    public function test_database_failure_keeps_old_gallery_and_cleans_new_uploads(): void
    {
        $service = app(ListingService::class);
        $listing = $service->create($this->seller, $this->data);
        $oldPath = $listing->images->first()->image_path;
        ListingImage::creating(fn () => throw new RuntimeException('Simulated database failure'));
        try {
            $service->update($this->seller, $listing, array_replace($this->data, ['retained_images' => []]));
            $this->fail('Image persistence must fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated database failure', $exception->getMessage());
        } finally {
            ListingImage::flushEventListeners();
        }
        $this->assertSame([$oldPath], Storage::disk('public')->allFiles());
        $this->assertDatabaseHas('listing_images', ['listing_id' => $listing->id, 'image_path' => $oldPath]);
    }

    public function test_uploaded_image_keeps_its_disk_when_default_storage_changes(): void
    {
        Storage::fake('s3');
        config(['filesystems.default' => 's3']);
        $listing = app(ListingService::class)->create($this->seller, $this->data);
        $image = $listing->images->first();
        config(['filesystems.default' => 'public']);
        $this->assertSame('s3', $image->disk);
        Storage::disk('s3')->assertExists($image->image_path);
        $this->assertSame(Storage::disk('s3')->url($image->image_path), $image->url);
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l9sAAAAASUVORK5CYII='));
    }
}
