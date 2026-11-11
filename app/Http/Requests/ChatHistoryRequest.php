<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $this->user() !== null
            && $conversation !== null
            && $this->user()->can('view', $conversation);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'after_id' => ['nullable', 'integer', 'min:0', 'prohibits:before_id'],
            'before_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function afterId(): ?int
    {
        return $this->filled('after_id') ? $this->integer('after_id') : null;
    }

    public function beforeId(): ?int
    {
        return $this->filled('before_id') ? $this->integer('before_id') : null;
    }
}
