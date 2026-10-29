<?php

namespace App\Http\Requests;

use App\Models\Listing;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Listing::class) ?? false;
    }

    public function rules(): array
    {
        $categoryId = filter_var($this->input('category_id'), FILTER_VALIDATE_INT);
        $isService = $categoryId && Category::find($categoryId)?->type === 'service';
        $gameId = filter_var($this->input('game_id'), FILTER_VALIDATE_INT) ?: 0;

        return [
            'game_id' => ['required', 'integer', Rule::exists('games', 'id')->where('active', true)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(fn ($query) => $query
                ->where('is_active', true)->where(fn ($scope) => $scope->whereNull('game_id')->orWhere('game_id', $gameId)))],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:1.00', 'max:999999.99'],
            'status' => ['nullable', 'in:rascunho,publicado,pausado'],
            'images' => ['required', 'array', 'min:1', 'max:6'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'primary_image' => ['nullable', 'string', 'max:64'],
            'retained_images' => ['prohibited'],
            'capacity' => $isService ? ['required', 'integer', 'min:1', 'max:1000000'] : ['nullable', 'integer', 'in:1'],
            'session_duration_minutes' => $isService ? ['required', 'integer', 'between:1,1440'] : ['prohibited'],
            'delivery_conditions' => ['required', 'string', 'min:10', 'max:2000'],
            'timezone' => $isService ? ['required', 'timezone:all'] : ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $retained = (array) $this->input('retained_images', []);
            $files = $this->file('images', []);
            if (count($retained) + count($files) < 1 || count($retained) + count($files) > 6) {
                $validator->errors()->add('images', 'Mantenha entre 1 e 6 fotos no anúncio.');
            }
            $tokens = array_merge(
                array_map(fn ($id): string => 'existing:'.$id, $retained),
                array_map(fn ($index): string => 'new:'.$index, array_keys($files))
            );
            if ($this->filled('primary_image') && ! in_array($this->input('primary_image'), $tokens, true)) {
                $validator->errors()->add('primary_image', 'Selecione uma das fotos mantidas como capa.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'game_id.required' => 'Selecione o jogo correspondente ao item.',
            'category_id.required' => 'Selecione uma categoria válida.',
            'title.required' => 'O título do anúncio é obrigatório.',
            'price.min' => 'O preço mínimo para anúncio é R$ 1,00.',
            'images.required' => 'Envie pelo menos 1 imagem para o anúncio.',
            'images.max' => 'Envie no máximo 6 fotos.',
            'images.*.image' => 'Cada arquivo deve ser uma imagem válida.',
            'images.*.mimes' => 'Use imagens JPG, PNG ou WebP.',
            'images.*.max' => 'Cada foto pode ter no máximo 4 MB.',
            'game_id.exists' => 'Selecione um jogo ativo.',
            'category_id.exists' => 'Selecione uma categoria ativa compatível com o jogo.',
            'price.decimal' => 'Informe o preço com até duas casas decimais.',
            'capacity.required' => 'Informe a quantidade de sessões disponíveis.',
            'capacity.in' => 'Um cosmético único deve ter capacidade 1.',
            'session_duration_minutes.required' => 'Informe a duração de cada sessão em minutos.',
            'timezone.required' => 'Informe o fuso horário do coaching.',
            'delivery_conditions.required' => 'Informe as condições de entrega ou do coaching.',
        ];
    }
}
