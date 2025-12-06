<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Comment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MessageBell extends Component
{
    public $showDropdown = false;
    public $unreadCount = 0;
    public $comments = [];

    public function mount()
    {
        $this->loadComments();
    }

    public function toggleDropdown()
    {
        $this->showDropdown = !$this->showDropdown;
        if ($this->showDropdown) {
            $this->loadComments();
        }
    }

    public function markAsRead($commentId)
    {
        $user = Auth::user();
        $comment = Comment::find($commentId);
        if ($comment && $user->comments()->where('comment_id', $commentId)->wherePivot('read_at', null)->exists()) {
            $user->comments()->updateExistingPivot($commentId, ['read_at' => now()]);
            $this->loadComments();
        }
    }

    protected function loadComments()
    {
        $user = Auth::user();
        $this->comments = $user->comments()
            ->with(['commentable', 'user'])
            ->whereIn('commentable_type', ['App\Models\Project', 'App\Models\Task'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'commentable_type' => $comment->commentable_type == 'App\Models\Project' ? 'Project' : 'Task',
                    'commentable_name' => $comment->commentable->title ?? 'Unknown',
                    'message' => "{$comment->user->name} " . ($comment->parent_id ? 'replied to' : 'commented on') . " {$comment->commentable->title}",
                    'url' => $comment->commentable_type == 'App\Models\Project'
                        ? route('admin.project.show', $comment->commentable_id)
                        : route('admin.task.show', $comment->commentable_id),
                    'created_at' => Carbon::parse($comment->created_at)->diffForHumans(),
                    'read_at' => $comment->pivot->read_at,
                ];
            })
            ->toArray();

        $this->unreadCount = $user->comments()
            ->whereIn('commentable_type', ['App\Models\Project', 'App\Models\Task'])
            ->wherePivot('read_at', null)
            ->count();
    }

    public function render()
    {
        return view('livewire.message-bell');
    }
}
