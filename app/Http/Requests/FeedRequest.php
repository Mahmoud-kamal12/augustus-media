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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.$this->maxPerPage()],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', $this->defaultPerPage());
    }

    public function shouldUseFirstPageCache(): bool
    {
        return $this->perPage() === $this->defaultPerPage()
            && ! $this->query->has('cursor');
    }

    private function defaultPerPage(): int
    {
        return config('feed.pagination.default_per_page');
    }

    private function maxPerPage(): int
    {
        return config('feed.pagination.max_per_page');
    }
}
