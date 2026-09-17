<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSeller() ?? false;
    }

    public function rules(): array
    {
        return [
            'game_id'     => ['required', 'exists:games,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'title'       => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'price'       => ['required', 'numeric', 'min:1.00', 'max:999999.99'],
            'status'      => ['nullable', 'in:rascunho,publicado,pausado'],
            'images'      => ['required', 'array', 'min:1', 'max:6'],
            'images.*'    => ['image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'game_id.required'     => 'Selecione o jogo correspondente ao item.',
            'category_id.required' => 'Selecione uma categoria válida.',
            'title.required'       => 'O título do anúncio é obrigatório.',
            'price.min'            => 'O preço mínimo para anúncio é R$ 1,00.',
            'images.required'      => 'Envie pelo menos 1 imagem para o anúncio.',
        ];
    }
}
