<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkAsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $this->user()
            && $conversation
            && $this->user()->can('markAsRead', $conversation);
    }

    public function rules(): array
    {
        return [
            'last_read_message_id' => ['required', 'integer', 'exists:messages,id'],
        ];
    }
}

