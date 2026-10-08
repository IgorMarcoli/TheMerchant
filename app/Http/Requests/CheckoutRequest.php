<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:500'],
            'item_instructions' => ['nullable', 'array'],
            'item_instructions.*' => ['nullable', 'string', 'max:1000'],
            'terms_agreed' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms_agreed.accepted' => 'Você deve concordar com as regras de entrega e conformidade com os termos do jogo.',
            'item_instructions.*.max' => 'Cada instrução por item deve ter no máximo 1.000 caracteres.',
        ];
    }
}
