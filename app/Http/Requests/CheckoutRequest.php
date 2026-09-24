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
            'terms_agreed' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms_agreed.accepted' => 'Você deve concordar com as regras de entrega e conformidade com os termos do jogo.',
        ];
    }
}
