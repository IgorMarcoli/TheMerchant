<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('usuario')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:active,suspended'],
            'is_admin' => ['required', 'boolean'],
            'seller_status' => ['nullable', 'in:pending,approved,suspended'],
        ];
    }
}
