<?php

namespace App\Http\Requests;

use App\Models\Listing;
use Illuminate\Validation\Rule;

class ListingUpdateRequest extends ListingStoreRequest
{
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['retained_images' => []]);
    }

    public function authorize(): bool
    {
        $listing = $this->route('anuncio');

        return $listing instanceof Listing && ($this->user()?->can('update', $listing) ?? false);
    }

    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'images' => ['sometimes', 'array', 'max:6'],
            'retained_images' => ['present', 'array', 'max:6'],
            'retained_images.*' => ['integer', 'distinct', Rule::exists('listing_images', 'id')
                ->where('listing_id', $this->route('anuncio')->id)],
            'status' => ['required', 'in:rascunho,publicado,pausado'],
        ]);
    }
}
