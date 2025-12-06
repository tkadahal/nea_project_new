<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Calendar extends Component
{
    public $events;

    public $activeDate;

    public $currentMonth;

    protected $listeners = ['setActiveDate' => 'setActiveDate'];

    public function mount()
    {
        $userId = Auth::id() ?? 1;
        $this->events = Event::where('user_id', $userId)
            ->select('id', 'title', 'description', 'start_time', 'end_time', 'is_reminder')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'start' => $event->start_time,
                    'end' => $event->end_time,
                    'is_reminder' => $event->is_reminder,
                ];
            });
        $this->activeDate = Carbon::now()->toDateString();
        $this->currentMonth = Carbon::now()->startOfMonth()->toDateString();
    }

    public function setActiveDate($date)
    {
        $this->activeDate = $date;
    }

    public function prevMonth()
    {
        $this->currentMonth = Carbon::parse($this->currentMonth)->subMonth()->startOfMonth()->toDateString();
        $this->dispatch('changeMonth', date: $this->currentMonth);
    }

    public function nextMonth()
    {
        $this->currentMonth = Carbon::parse($this->currentMonth)->addMonth()->startOfMonth()->toDateString();
        $this->dispatch('changeMonth', date: $this->currentMonth);
    }

    public function goToToday()
    {
        $this->currentMonth = Carbon::now()->startOfMonth()->toDateString();
        $this->activeDate = Carbon::now()->toDateString();
        $this->dispatch('changeMonth', date: $this->currentMonth);
    }

    public function render()
    {
        $filteredEvents = $this->events->filter(function ($event) {
            return Carbon::parse($event['start'])->toDateString() === $this->activeDate;
        })->values();

        return view('livewire.calendar', [
            'filteredEvents' => $filteredEvents,
        ]);
    }
}
