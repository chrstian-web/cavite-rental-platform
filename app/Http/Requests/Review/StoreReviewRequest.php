<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('contract');

        // Only the tenant on a contract that has actually ended may review it —
        // and only once per contract, enforced again at the DB level (unique constraint).
        return $this->user()->id === $contract->user_id
            && in_array($contract->status, ['expired', 'terminated'], true)
            && ! $contract->review()->exists();
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'cleanliness_rating' => ['nullable', 'integer', 'between:1,5'],
            'location_rating' => ['nullable', 'integer', 'between:1,5'],
            'amenities_rating' => ['nullable', 'integer', 'between:1,5'],
            'value_rating' => ['nullable', 'integer', 'between:1,5'],
            'management_rating' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
