<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Sound;
use App\Services\Social\CommentService;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    public function store(StoreCommentRequest $request, Sound $sound): RedirectResponse
    {
        $this->authorize('create', Comment::class);

        $this->commentService->create(
            $request->user(),
            $sound,
            $request->validated()
        );

        return back()->with('success', 'Commentaire publié.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $this->commentService->delete($comment);

        return back()->with('success', 'Commentaire supprimé.');
    }
}
