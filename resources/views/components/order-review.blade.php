@props(['order', 'item'])

@if($item->review)
    <section class="my-3 rounded-2xl border border-amber-500/30 bg-slate-950 p-4" aria-label="Sua avaliação de {{ $item->seller->name }}">
        <p class="font-semibold text-amber-400">
            <span aria-hidden="true">{{ str_repeat('★', $item->review->rating) }}{{ str_repeat('☆', 5 - $item->review->rating) }}</span>
            Você avaliou com {{ $item->review->rating }} de 5 estrelas
        </p>
        @if($item->review->comment)
            <p class="mt-2 whitespace-pre-wrap break-words text-sm text-slate-300">{{ $item->review->comment }}</p>
        @else
            <p class="mt-2 text-sm text-slate-400">Você não deixou um comentário.</p>
        @endif
    </section>
@elseif(auth()->user()->can('create', [\App\Models\Review::class, $order, $item]))
    @once
        <script src="{{ asset('js/review-form.js') }}"></script>
    @endonce
    <div x-data="reviewForm" class="my-3">
        <details x-show="!saved" class="rounded-2xl border border-slate-700 bg-slate-950 p-4">
            <summary class="cursor-pointer rounded text-sm font-semibold text-amber-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-400">Avaliar Vendedor</summary>
            <form action="{{ route('orders.review.store', [$order, $item]) }}" method="POST"
                  @submit.prevent="submit($el)" :aria-busy="sending" class="mt-4 space-y-4">
                @csrf
                <p class="text-sm text-slate-300">Como foi o atendimento de {{ $item->seller->name }}?</p>
                <div x-ref="error" role="alert" tabindex="-1" x-show="error" style="display:none"
                     class="rounded-lg border border-rose-400 p-3 text-sm text-rose-300" x-text="error"></div>
                <fieldset :disabled="sending" @keydown="hover = 0" aria-describedby="review-help-{{ $item->id }} review-rating-error-{{ $item->id }}">
                    <legend class="text-sm font-semibold">Nota (obrigatória)</legend>
                    <div class="flex flex-wrap gap-1" @mouseleave="hover = 0">
                        @for($star = 1; $star <= 5; $star++)
                            <label class="relative flex min-h-12 min-w-12 cursor-pointer items-center justify-center rounded-lg"
                                   @mouseenter="hover = {{ $star }}">
                                <input type="radio" name="rating" value="{{ $star }}" required x-model="rating"
                                       aria-label="{{ $star }} {{ $star === 1 ? 'estrela' : 'estrelas' }}"
                                       :aria-invalid="Boolean(errors.rating)" aria-describedby="review-rating-error-{{ $item->id }}"
                                       class="peer sr-only">
                                <span aria-hidden="true" class="rounded-lg p-2 text-3xl text-slate-400 peer-checked:text-amber-400 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-amber-400"
                                      :style="{ color: (hover || Number(rating)) >= {{ $star }} ? '#fbbf24' : '#94a3b8' }">★</span>
                            </label>
                        @endfor
                    </div>
                    <p id="review-help-{{ $item->id }}" class="text-xs text-slate-400">Selecione de 1 a 5 estrelas. Use as setas do teclado ou toque nas estrelas.</p>
                    <p class="mt-1 text-sm text-amber-400" aria-live="polite" x-text="rating ? rating + ' de 5 estrelas selecionadas' : 'Nenhuma nota selecionada'"></p>
                    <p id="review-rating-error-{{ $item->id }}" class="text-sm text-rose-300" x-text="errors.rating?.[0] || ''"></p>
                </fieldset>
                <div>
                    <label for="review-comment-{{ $item->id }}" class="block text-sm font-semibold">Comentário (opcional)</label>
                    <textarea id="review-comment-{{ $item->id }}" name="comment" rows="3" maxlength="1000"
                              x-model="comment" :disabled="sending" :aria-invalid="Boolean(errors.comment)"
                              aria-describedby="review-count-{{ $item->id }} review-comment-error-{{ $item->id }}"
                              placeholder="Conte como foi a entrega e o atendimento."
                              class="mt-2 w-full rounded-xl border border-slate-600 bg-slate-900 p-3 text-sm text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-400"></textarea>
                    <p id="review-count-{{ $item->id }}" class="text-xs text-slate-400" x-text="Array.from(comment).length + '/1000 caracteres'">Até 1000 caracteres.</p>
                    <p id="review-comment-error-{{ $item->id }}" class="text-sm text-rose-300" x-text="errors.comment?.[0] || ''"></p>
                </div>
                <button type="submit" :disabled="sending" class="min-h-12 rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-slate-950 hover:bg-amber-300 disabled:cursor-wait disabled:opacity-60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400"
                        x-text="sending ? 'Publicando…' : 'Publicar avaliação'">Publicar avaliação</button>
                <span role="status" class="text-sm text-slate-300" x-text="sending ? 'Enviando sua avaliação. Aguarde.' : ''"></span>
            </form>
        </details>
        <section x-show="saved" style="display:none" x-ref="confirmation" tabindex="-1"
                 class="rounded-2xl border border-amber-500/30 bg-slate-950 p-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-400" aria-label="Sua avaliação">
            <p class="font-semibold text-amber-400">
                <span aria-hidden="true" x-text="'★'.repeat(saved?.rating || 0) + '☆'.repeat(5 - (saved?.rating || 0))"></span>
                <span x-text="'Você avaliou com ' + (saved?.rating || 0) + ' de 5 estrelas'"></span>
            </p>
            <p class="mt-2 whitespace-pre-wrap break-words text-sm text-slate-300" x-text="saved?.comment || 'Você não deixou um comentário.'"></p>
        </section>
        <div role="status" aria-live="polite" aria-atomic="true" class="text-sm">
            <div x-show="toast" style="display:none" class="mt-3 flex items-center justify-between gap-4 rounded-xl border border-emerald-500 bg-emerald-950 p-3 text-emerald-200">
                <span x-text="toast"></span>
                <button type="button" @click="toast = ''; $refs.confirmation.focus()" class="min-h-11 min-w-11 rounded-lg p-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-200" aria-label="Fechar confirmação de envio">×</button>
            </div>
        </div>
    </div>
@endif
