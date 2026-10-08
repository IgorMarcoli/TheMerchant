<?php

namespace App\Http\Requests;

use App\Models\OrderItem;
use Illuminate\Foundation\Http\FormRequest;

class RecordSaleDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('deliver', $this->route('item')) ?? false;
    }

    public function rules(): array
    {
        $item = $this->route('item');
        $isCoaching = $item instanceof OrderItem
            && $item->listing?->category?->type === 'service';

        return [
            'session_number' => $isCoaching
                ? ['required', 'integer', 'min:1', 'max:'.$item->quantity]
                : ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_number.required' => 'Informe qual sessão de coaching foi concluída.',
            'session_number.max' => 'O número da sessão não pode ultrapassar a quantidade contratada.',
        ];
    }
}
