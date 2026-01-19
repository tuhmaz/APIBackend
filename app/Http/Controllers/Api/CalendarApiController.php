<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Http\Resources\BaseResource;

class CalendarApiController extends Controller
{
    /**
     * ===============================
     *  GET DATABASE LIST
     * ===============================
     */
    public function databases()
    {
        $allowed = ['mysql', 'jo', 'sa', 'eg', 'ps'];

        $databases = collect(config('database.connections'))
            ->keys()
            ->filter(fn($name) => in_array($name, $allowed))
            ->values()
            ->toArray();

        return new BaseResource([
            'databases' => $databases
        ]);
    }

    /**
     * ===============================
     *  GET EVENTS
     * ===============================
     */
    public function getEvents(Request $request)
    {
        try {
            $allowed = ['mysql', 'jo', 'sa', 'eg', 'ps'];

            $database = $request->input('database', config('database.default'));

            if (!in_array($database, $allowed, true)) {
                $database = config('database.default');
            }

            DB::setDefaultConnection($database);

            $query = Event::on($database)->select([
                'id',
                'title',
                'description',
                'event_date'
            ]);

            if ($request->start && $request->end) {
                $query->whereBetween('event_date', [
                    Carbon::parse($request->start)->toDateString(),
                    Carbon::parse($request->end)->toDateString()
                ]);
            }

            $events = $query->orderBy('event_date', 'asc')
                ->get()
                ->map(fn($event) => [
                    'id'    => $event->id,
                    'title' => $event->title,
                    'start' => $event->event_date,
                    'allDay' => true,
                    'extendedProps' => [
                        'description' => $event->description ?: 'لا يوجد وصف',
                        'database' => $database
                    ]
                ]);

            return new BaseResource(['data' => $events]);

        } catch (Throwable $e) {
            Log::warning('Calendar Events Error:', [
                'error' => $e->getMessage(),
                'database' => $database ?? 'unknown'
            ]);

            return new BaseResource(['data' => []]);
        }
    }

    /**
     * ===============================
     *  STORE EVENT
     * ===============================
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title'         => 'required|string|max:255',
                'description'   => 'nullable|string',
                'event_date'    => 'required|date',
                'eventDatabase' => 'required|string'
            ]);

            $database = $validated['eventDatabase'];

            $allowed = ['mysql', 'jo', 'sa', 'eg', 'ps'];
            if (!in_array($database, $allowed, true)) {
                return (new BaseResource(['message' => 'Invalid database selected']))
                    ->response($request)
                    ->setStatusCode(422);
            }

            DB::setDefaultConnection($database);

            $formattedDate = Carbon::parse($validated['event_date'])->format('Y-m-d');

            $event = new Event();
            $event->setConnection($database);
            $event->title = $validated['title'];
            $event->description = $validated['description'];
            $event->event_date = $formattedDate;
            $event->save();

            return new BaseResource([
                'message' => 'Event created successfully',
                'data' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $formattedDate,
                    'allDay' => true,
                    'extendedProps' => [
                        'description' => $event->description ?? '',
                        'database' => $database
                    ]
                ]
            ]);

        } catch (Throwable $e) {
            Log::error('Event Creation Error:', [
                'error' => $e->getMessage()
            ]);

            return (new BaseResource(['message' => 'Failed to create event: ' . $e->getMessage()]))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * ===============================
     *  UPDATE EVENT
     * ===============================
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'title'         => 'required|string|max:255',
                'description'   => 'nullable|string',
                'event_date'    => 'required|date',
                'eventDatabase' => 'required|string'
            ]);

            $database = $validated['eventDatabase'];

            DB::setDefaultConnection($database);

            $formattedDate = Carbon::parse($validated['event_date'])->format('Y-m-d');

            $event = Event::on($database)->findOrFail($id);
            $event->title = $validated['title'];
            $event->description = $validated['description'];
            $event->event_date = $formattedDate;
            $event->save();

            return new BaseResource([
                'message' => 'Event updated successfully',
                'data' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $formattedDate,
                    'allDay' => true,
                    'extendedProps' => [
                        'description' => $event->description ?? '',
                        'database' => $database
                    ]
                ]
            ]);

        } catch (Throwable $e) {
            Log::error('Event Update Error:', [
                'error' => $e->getMessage(),
            ]);

            return (new BaseResource(['message' => 'Failed to update event: ' . $e->getMessage()]))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * ===============================
     *  DELETE EVENT
     * ===============================
     */
    public function destroy(Request $request, $id)
    {
        try {
            if (!$request->has('database')) {
                return (new BaseResource(['message' => 'Database parameter is required']))
                    ->response($request)
                    ->setStatusCode(422);
            }

            $database = $request->input('database');

            DB::setDefaultConnection($database);

            $event = Event::on($database)->findOrFail($id);
            $event->delete();

            return new BaseResource(['message' => 'Event deleted successfully']);

        } catch (Throwable $e) {
            Log::error('Event Delete Error:', [
                'error' => $e->getMessage()
            ]);

            return (new BaseResource(['message' => 'Failed to delete event: ' . $e->getMessage()]))
                ->response($request)
                ->setStatusCode(500);
        }
    }
}
