<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('moderate', $this->route('report')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:em_analise,procedente,improcedente'],
            'resolution_notes' => ['required', 'string', 'min:10', 'max:1000'],
            'block_listing' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'resolution_notes.min' => 'O parecer deve explicar a decisão com pelo menos 10 caracteres.',
            'status.in' => 'Selecione uma situação válida para a denúncia.',
        ];
    }
}
