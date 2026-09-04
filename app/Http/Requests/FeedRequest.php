<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config('feed.pagination.max_per_page')],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', config('feed.pagination.default_per_page'));
    }

    public function shouldUseFirstPageCache(): bool
    {
        return $this->perPage() === config('feed.pagination.default_per_page')
            && ! $this->query->has('cursor');
    }
}
