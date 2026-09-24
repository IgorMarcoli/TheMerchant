<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $this->user()
            && $conversation
            && $this->user()->can('sendMessage', $conversation);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:2000'],
            'client_uuid' => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'A mensagem não pode estar vazia.',
            'body.max' => 'A mensagem deve ter no máximo 2000 caracteres.',
            'client_uuid.required' => 'O identificador único de envio é obrigatório.',
        ];
    }
}

