<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Services\FeedService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedController extends Controller
{
    public function __construct(private FeedService $feed)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        return PostResource::collection(
            $this->feed->followedPosts($request->user(), (int) ($validated['per_page'] ?? 20))
        );
    }
}
