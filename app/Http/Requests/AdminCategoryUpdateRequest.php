<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('categoria');

        return $this->user()?->can('update', $category) ?? false;
    }

    public function rules(): array
    {
        return [
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:cosmetic,service'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'game_id.required' => 'O jogo associado é obrigatório.',
            'game_id.exists' => 'O jogo selecionado não existe.',
            'name.required' => 'O nome da categoria é obrigatório.',
            'name.max' => 'O nome da categoria deve ter no máximo 100 caracteres.',
            'type.required' => 'O tipo da categoria (cosmético ou serviço) é obrigatório.',
            'type.in' => 'O tipo deve ser cosmético ou serviço.',
        ];
    }
}

