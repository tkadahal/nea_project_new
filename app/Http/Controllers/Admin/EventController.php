<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::where('user_id', Auth::id())->get()->map(function ($event) {
            return [
                'title' => $event->title,
                'start' => $event->start_time,
                'end' => $event->end_time,
                'description' => $event->description,
                'is_reminder' => $event->is_reminder,
            ];
        });

        return view('admin.events.index', compact('events'));
    }

    public function create()
    {
        return view('admin.events.create');
    }

    public function store(Request $request)
    {
        Event::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_reminder' => $request->is_reminder ?? false,
        ]);

        return redirect()->route('admin.event.index')->with('success', 'Event created successfully!');
    }
}
