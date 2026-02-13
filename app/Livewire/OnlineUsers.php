<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Message;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OnlineUsers extends Component
{
    public int $thresholdMinutes = 3;
    public array $onlineUsers = [];

    // Chat properties
    public ?int $selectedRecipientId = null;
    public ?string $selectedRecipientName = null;
    public string $newChatMessage = '';
    public array $chatMessages = [];

    public function mount()
    {
        $this->refreshOnlineUsers();
    }

    public function refreshOnlineUsers()
    {
        $cutoff = now()->subMinutes($this->thresholdMinutes)->timestamp;

        $this->onlineUsers = User::query()
            ->whereExists(function ($query) use ($cutoff) {
                $query->select(DB::raw(1))
                    ->from('sessions')
                    ->whereColumn('sessions.user_id', 'users.id')
                    ->where('sessions.last_activity', '>=', $cutoff);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'employee_id'])
            ->map(function ($user) use ($cutoff) {
                $lastActivity = DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->max('last_activity');

                $user->last_active_human = $lastActivity
                    ? now()->createFromTimestamp($lastActivity)->diffForHumans()
                    : '—';

                $user->ip = DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->latest('last_activity')
                    ->value('ip_address') ?? '—';

                return $user;
            })
            ->toArray();
    }

    public function openChatWith($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $this->selectedRecipientId   = $userId;
        $this->selectedRecipientName = $user->name;

        // Mark messages from this user as read (optional but recommended)
        Message::where('sender_id', $userId)
            ->where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->loadChatMessages();
    }

    public function closeChat()
    {
        $this->selectedRecipientId   = null;
        $this->selectedRecipientName = null;
        $this->chatMessages          = [];
        $this->newChatMessage        = '';
    }

    public function loadChatMessages()
    {
        if (!$this->selectedRecipientId) {
            return;
        }

        $this->chatMessages = Message::between(
            Auth::id(),
            $this->selectedRecipientId
        )
            ->with('sender')
            ->take(50)
            ->latest()                // newest first in DB
            ->get()
            ->reverse()               // so oldest appear at top
            ->toArray();              // ← keeping as array, as you requested
    }

    public function sendMessage()
    {
        if (!trim($this->newChatMessage) || !$this->selectedRecipientId) {
            return;
        }

        Message::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $this->selectedRecipientId,
            'content'     => $this->newChatMessage,
        ]);

        $this->newChatMessage = '';
        $this->loadChatMessages();
    }

    public function render()
    {
        return view('livewire.online-users');
    }
}
