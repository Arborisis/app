<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::latestPublished()
            ->paginate($request->input('per_page', 12));

        return response()->json($posts);
    }

    public function show(string $slug): JsonResponse
    {
        $post = BlogPost::where('slug', $slug)
            ->published()
            ->firstOrFail();

        return response()->json($post);
    }
}
