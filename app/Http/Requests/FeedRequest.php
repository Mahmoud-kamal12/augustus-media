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
        $maximumPostsPerPage = config('feed.pagination.max_per_page');

        return [
            'per_page' => ['sometimes', 'integer', 'min:1', "max:{$maximumPostsPerPage}"],
        ];
    }

    public function perPage(): int
    {
        $defaultPostsPerPage = config('feed.pagination.default_per_page');

        return $this->integer('per_page', $defaultPostsPerPage);
    }

    public function shouldUseFirstPageCache(): bool
    {
        $defaultPostsPerPage = config('feed.pagination.default_per_page');

        return $this->perPage() === $defaultPostsPerPage
            && ! $this->query->has('cursor');
    }
}
