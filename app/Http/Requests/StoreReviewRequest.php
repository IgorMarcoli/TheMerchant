<?php

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Review::class, $this->route('order'), $this->route('item')]);
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Selecione uma nota de 1 a 5 estrelas.',
            'rating.integer' => 'Selecione uma nota inteira de 1 a 5 estrelas.',
            'rating.between' => 'A nota deve estar entre 1 e 5 estrelas.',
            'comment.string' => 'O comentário deve ser um texto.',
            'comment.max' => 'O comentário deve ter no máximo 1000 caracteres.',
        ];
    }
}
