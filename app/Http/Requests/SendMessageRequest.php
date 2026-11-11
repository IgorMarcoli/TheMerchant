<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $this->user() !== null
            && $conversation !== null
            && $this->user()->can('sendMessage', $conversation);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            // Texto simples: quebras de linha e tabulação são aceitas; demais caracteres de controle, não.
            'body' => ['required', 'string', 'min:1', 'max:2000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            // Gerado pelo navegador a cada envio e repetido nas novas tentativas.
            'client_uuid' => ['required', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'A mensagem não pode estar vazia.',
            'body.max' => 'A mensagem deve ter no máximo 2000 caracteres.',
            'body.not_regex' => 'A mensagem aceita apenas texto simples.',
            'client_uuid.required' => 'O identificador único de envio é obrigatório.',
            'client_uuid.uuid' => 'O identificador de envio deve ser um UUID.',
        ];
    }
}
