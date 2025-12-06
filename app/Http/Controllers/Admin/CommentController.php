<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Task;
use App\Models\Comment;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class CommentController extends Controller
{
    /**
     * Store a comment for a project.
     */
    public function storeForProject(Request $request, Project $project)
    {
        return $this->storeComment($request, $project, 'admin.project.show');
    }

    /**
     * Store a comment for a task.
     */
    public function storeForTask(Request $request, Task $task)
    {
        return $this->storeComment($request, $task, 'admin.task.show');
    }

    /**
     * Shared logic to store a comment for any commentable model.
     */
    protected function storeComment(Request $request, Model $commentable, string $redirectRoute)
    {
        $request->validate([
            'content' => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
            'project_id' => ['nullable', 'numeric', 'exists:projects,id'],
        ]);

        $projectId = $request->input('project_id');

        // Validate that task belongs to project (if applicable)
        if ($commentable instanceof Task && $projectId !== null) {
            if (!$commentable->projects()->where('project_id', $projectId)->exists()) {
                return redirect()->back()->withErrors(['project_id' => 'Task not associated with this project']);
            }
        }

        $comment = $commentable->comments()->create([
            'content' => $request->input('content'),
            'user_id' => Auth::id(),
            'project_id' => $projectId,
            'parent_id' => $request->input('parent_id'),
        ]);

        // Notify relevant users
        $this->notifyUsers($comment, $commentable);

        // Build redirect parameters dynamically
        $routeParams = [$commentable->id];
        if ($commentable instanceof Task && $projectId) {
            $routeParams[] = $projectId;
        }

        return redirect()
            ->route($redirectRoute, $routeParams)
            ->with('success', 'Comment added.');
    }

    /**
     * Notify users about a new comment.
     */
    protected function notifyUsers(Comment $comment, Model $commentable)
    {
        $commentAuthor = Auth::user();
        $recipients = collect();

        // Add project/task assignees
        if ($commentable instanceof Project || $commentable instanceof Task) {
            $recipients = $commentable->users->pluck('id');
        }

        // Add thread participants (for replies)
        if ($comment->parent_id) {
            $threadComments = Comment::where('parent_id', $comment->parent_id)
                ->orWhere('id', $comment->parent_id)
                ->with('user')
                ->get();
            $threadParticipants = $threadComments->pluck('user.id')->unique();
            $recipients = $recipients->merge($threadParticipants);
        }

        // Include project manager for projects
        if ($commentable instanceof Project && $commentable->project_manager_id) {
            $recipients->push($commentable->project_manager_id);
        }

        // Exclude comment author
        $recipients = $recipients->unique()->reject(
            fn($userId) => $userId == $commentAuthor->id
        );

        // Attach users to comment with unread status
        $comment->users()->syncWithoutDetaching(
            $recipients->mapWithKeys(fn($userId) => [$userId => ['read_at' => null]])
        );
    }
}
