<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeedRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->query->has('per_page')) {
            return;
        }

        $this->merge([
            'per_page' => config('feed.pagination.default_per_page'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maximumPostsPerPage = config('feed.pagination.max_per_page');

        return [
            'per_page' => ['required', 'integer', 'min:1', "max:{$maximumPostsPerPage}"],
        ];
    }
}
