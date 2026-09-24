<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isActive();
    }

    public function rules(): array
    {
        return [
            'listing_id' => ['required', 'integer', 'exists:listings,id'],
            'buyer_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'listing_id.required' => 'O anúncio é obrigatório para iniciar a conversa.',
            'listing_id.exists' => 'O anúncio selecionado não existe.',
            'buyer_id.exists' => 'O comprador informado não foi localizado.',
        ];
    }
}

