<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public $unreadCount = 0;

    public $notifications = [];

    public $showDropdown = false;

    protected $listeners = ['refreshNotifications' => 'refresh'];

    public function mount()
    {
        $this->refresh();
    }

    public function refresh()
    {
        if (Auth::check()) {
            $this->unreadCount = Auth::user()->unreadNotifications()->count();
            $this->notifications = Auth::user()->notifications()
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'message' => $notification->data['message'] ?? 'No message',
                        'task_name' => $notification->data['task_name'] ?? 'Unnamed Task',
                        'url' => $notification->data['url'] ?? '#',
                        'created_at' => $notification->created_at->diffForHumans(),
                        'read_at' => $notification->read_at,
                    ];
                })
                ->toArray();
        }
    }

    public function toggleDropdown()
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    public function markAsRead($notificationId)
    {
        if (Auth::check()) {
            $notification = Auth::user()->notifications()->where('id', $notificationId)->first();
            if ($notification && is_null($notification->read_at)) {
                $notification->markAsRead();
                $this->refresh();
            }
        }
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
