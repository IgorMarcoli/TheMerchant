<?php

namespace App\Http\Requests;

use App\Models\CartItem;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $this->user() !== null
            && $item instanceof CartItem
            && $this->user()->can('update', $item);
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Informe a quantidade de sessões desejada.',
            'quantity.integer' => 'A quantidade de sessões deve ser um número inteiro.',
            'quantity.min' => 'Selecione pelo menos 1 sessão.',
        ];
    }
}
