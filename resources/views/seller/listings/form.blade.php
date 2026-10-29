@php
    $retainedIds = old('retained_images', $listing?->images->modelKeys() ?? []);
    $retainedIds = is_array($retainedIds) ? $retainedIds : [];
    $images = $listing?->images->whereIn('id', $retainedIds)->map(fn ($image) => [
        'id' => $image->id, 'url' => $image->url, 'token' => 'existing:'.$image->id,
    ])->values()->all() ?? [];
    $primaryId = $listing?->images->firstWhere('is_primary', true)?->id;
    $formState = [
        'gameId' => (string) old('game_id', $listing?->game_id ?? ''),
        'categoryId' => (string) old('category_id', $listing?->category_id ?? ''),
        'categories' => $categories->map(fn ($category) => $category->only(['id', 'game_id', 'name', 'type']))->values(),
        'existing' => $images,
        'primary' => old('primary_image', $primaryId ? 'existing:'.$primaryId : ''),
    ];
@endphp

<form x-data="listingForm(@js($formState))" action="{{ $editing ? route('seller.anuncios.update', $listing) : route('seller.anuncios.store') }}"
      method="POST" enctype="multipart/form-data" class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-8 space-y-6">
    @csrf
    @if($editing) @method('PUT') @endif

    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-500/40 bg-rose-950/60 p-4 text-rose-300">
            <p class="font-bold">Revise os dados do anúncio.</p>
            <ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <p class="mt-2 text-sm">Selecione novamente as novas fotos antes de salvar.</p>
        </div>
    @endif

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="listing-game" class="block mb-2 text-sm">Jogo</label>
            <select id="listing-game" name="game_id" x-model="gameId" @change="categoryId = ''" required class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3">
                <option value="">Selecione o jogo</option>
                @foreach($games as $game)<option value="{{ $game->id }}" @selected(old('game_id', $listing?->game_id) == $game->id)>{{ $game->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="listing-category" class="block mb-2 text-sm">Categoria</label>
            <select id="listing-category" name="category_id" x-model="categoryId" required class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3">
                <option value="">Selecione a categoria</option>
                <template x-for="category in compatibleCategories" :key="category.id">
                    <option :value="String(category.id)" x-text="category.name"></option>
                </template>
            </select>
            <p class="mt-2 text-xs text-slate-400" x-text="isService ? 'Coaching: preço por sessão.' : 'Cosmético digital: uma unidade única.'"></p>
        </div>
    </div>

    <div>
        <label for="listing-title" class="block mb-2 text-sm">Título</label>
        <input id="listing-title" name="title" value="{{ old('title', $listing?->title) }}" required minlength="5" maxlength="150" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3">
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="listing-price" class="block mb-2 text-sm">Preço (R$) <span x-show="isService">por sessão</span></label>
            <input id="listing-price" type="number" name="price" value="{{ old('price', $listing?->price) }}" min="1" max="999999.99" step="0.01" required class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3">
        </div>
        <div>
            <label for="listing-status" class="block mb-2 text-sm">Status</label>
            <select id="listing-status" name="status" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3">
                @foreach(['publicado' => 'Publicado', 'pausado' => 'Pausado', 'rascunho' => 'Rascunho'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $listing?->status ?? 'publicado') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div>
        <label for="listing-description" class="block mb-2 text-sm">Descrição detalhada</label>
        <textarea id="listing-description" name="description" rows="5" minlength="20" maxlength="5000" required class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3">{{ old('description', $listing?->description) }}</textarea>
    </div>
    <div>
        <label for="listing-delivery" class="block mb-2 text-sm">Condições de entrega ou do coaching</label>
        <textarea id="listing-delivery" name="delivery_conditions" rows="3" minlength="10" maxlength="2000" required class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3" placeholder="Informe prazo, pré-requisitos e como a entrega ou sessão será combinada.">{{ old('delivery_conditions', $listing?->delivery_conditions) }}</textarea>
    </div>

    <input type="hidden" name="capacity" value="1" :disabled="isService">
    <fieldset x-cloak x-show="isService" class="grid sm:grid-cols-3 gap-4 rounded-xl border border-slate-700 p-4">
        <legend class="px-2 text-sm font-bold">Sessões de coaching</legend>
        <div>
            <label for="listing-capacity" class="block mb-2 text-sm">Sessões disponíveis</label>
            <input id="listing-capacity" type="number" name="capacity" value="{{ old('capacity', $listing?->capacity ?? 1) }}" min="1" max="1000000" step="1" :required="isService" :disabled="!isService" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3">
        </div>
        <div>
            <label for="listing-duration" class="block mb-2 text-sm">Minutos por sessão</label>
            <input id="listing-duration" type="number" name="session_duration_minutes" value="{{ old('session_duration_minutes', $listing?->session_duration_minutes ?? 60) }}" min="1" max="1440" step="1" :required="isService" :disabled="!isService" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3">
        </div>
        <div>
            <label for="listing-timezone" class="block mb-2 text-sm">Fuso horário</label>
            <input id="listing-timezone" name="timezone" value="{{ old('timezone', $listing?->timezone ?? 'America/Sao_Paulo') }}" :required="isService" :disabled="!isService" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3">
        </div>
        <p class="sm:col-span-3 text-xs text-slate-400">Horário a combinar pelo chat com o comprador.</p>
    </fieldset>

    <fieldset class="space-y-4">
        <legend class="font-bold">Galeria de fotos</legend>
        <p id="listing-photo-help" class="text-sm text-slate-400">De 1 a 6 fotos JPG, PNG ou WebP, com até 4 MB cada. Escolha uma capa. Selecionar arquivos novamente substitui a seleção de novas fotos.</p>
        <label for="listing-images" class="block text-sm">{{ $editing ? 'Adicionar fotos' : 'Selecionar fotos' }}</label>
        <input id="listing-images" x-ref="files" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" @change="selectFiles($event.target)" aria-describedby="listing-photo-help" class="w-full text-sm">
        <p x-cloak x-show="photoError" x-text="photoError" role="alert" class="text-rose-300 text-sm"></p>
        <p class="text-sm text-slate-300" aria-live="polite" x-text="`${photos.length} de 6 fotos selecionadas`"></p>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <template x-for="photo in photos" :key="photo.token">
                <div class="rounded-xl border border-slate-700 p-2 space-y-2">
                    <img :src="photo.url" :alt="photo.name || 'Foto do anúncio'" width="200" height="150" class="w-full h-32 object-cover rounded-lg">
                    <template x-if="photo.id"><input type="hidden" name="retained_images[]" :value="photo.id"></template>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" name="primary_image" :value="photo.token" x-model="primary"> Capa</label>
                    <button type="button" @click="removePhoto(photo.token)" class="text-sm text-rose-300 underline">Remover foto</button>
                </div>
            </template>
        </div>
    </fieldset>

    @if($editing && $listing->orderItems()->exists())
        <p class="text-sm text-amber-300">Este anúncio possui pedidos. Jogo, categoria, capacidade, duração, fuso e condições contratadas devem ser preservados.</p>
    @endif
    <button type="submit" :disabled="photoError !== ''" class="w-full rounded-xl bg-brand-600 text-slate-950 font-bold py-3 disabled:opacity-50">{{ $editing ? 'Salvar alterações' : 'Salvar anúncio' }}</button>
</form>

@once
    @push('scripts')
        <script src="{{ asset('js/listing-form.js') }}"></script>
    @endpush
@endonce
