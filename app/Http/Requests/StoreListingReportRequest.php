<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreListingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $listing = $this->route('listing');

        return $this->user() !== null
            && $listing !== null
            && $this->user()->id !== $listing->seller_id;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'in:fraude,termos,preco,Outro'],
            'details' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.in' => 'Selecione um motivo válido para a denúncia.',
            'details.min' => 'Descreva a situação com pelo menos 10 caracteres.',
            'details.max' => 'Os detalhes devem ter no máximo 1.000 caracteres.',
        ];
    }
}
