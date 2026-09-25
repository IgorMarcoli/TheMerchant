<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'busca' => ['nullable', 'string', 'max:150'],
            'tipo' => ['nullable', Rule::in(['cosmetic', 'service'])],
            'jogo' => ['nullable', 'integer', 'exists:games,id'],
            'categoria' => ['nullable', 'integer', 'exists:categories,id'],
            'preco_min' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'preco_max' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', ...($this->filled('preco_min') ? ['gte:preco_min'] : [])],
            'nota_min' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'ordem' => ['nullable', Rule::in(['recentes', 'menor_preco', 'maior_preco', 'reputacao'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
