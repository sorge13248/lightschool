<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimetableController extends Controller
{

    public function handle(Request $request): JsonResponse
    {
        $type = $request->query('type');

        return match ($type) {
            'get'          => $this->get(),
            'get-tomorrow' => $this->getTomorrow(),
            'get-subjects' => $this->getSubjects(),
            'create'       => $this->create($request),
            'edit'         => $this->edit($request),
            'remove'       => $this->remove($request),
            default        => response()->json(['response' => 'error', 'text' => 'Invalid type']),
        };
    }

    protected function get(): JsonResponse
    {
        $userId = auth()->id();

        $rows = Timetable::forUser($userId)->active()
            ->select('id', 'day', 'slot', 'subject', 'book', 'fore')
            ->orderBy('day')->orderBy('slot')
            ->get();

        return response()->json($rows);
    }

    protected function getTomorrow(): JsonResponse
    {
        $userId   = auth()->id();
        $tomorrow = (new \DateTime('tomorrow'))->format('N'); // ISO day 1-7

        $rows = Timetable::forUser($userId)->active()
            ->where('day', $tomorrow)
            ->select('id', 'day', 'slot', 'subject', 'book', 'fore')
            ->orderBy('day')->orderBy('slot')
            ->get();

        return response()->json($rows);
    }

    protected function getSubjects(): JsonResponse
    {
        $userId = auth()->id();

        $rows = Timetable::forUser($userId)->active()
            ->select('subject', 'fore', 'book')
            ->groupBy('subject', 'fore', 'book')
            ->orderBy('subject')
            ->get();

        return response()->json($rows);
    }

    protected function create(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if ($err = $this->validateInput(
            [
                'day'     => $request->input('day'),
                'slot'    => $request->input('slot'),
                'subject' => $request->input('subject'),
            ],
            [
                'day'     => 'required|integer|min:1|max:7',
                'slot'    => 'required|integer|min:0|max:255',
                'subject' => 'required|string',
            ],
            [
                'day.required'     => __('timetable-error-day-required'),
                'day.integer'      => __('timetable-error-day-number'),
                'day.min'          => __('timetable-error-day-range'),
                'day.max'          => __('timetable-error-day-range'),
                'slot.required'    => __('timetable-error-slot-required'),
                'slot.integer'     => __('timetable-error-slot-number'),
                'slot.max'         => __('timetable-error-slot-range'),
                'subject.required' => __('timetable-error-subject-required'),
            ]
        )) return $err;

        $year    = $request->input('year') !== '' && $request->input('year') !== null ? (int) $request->input('year') : null;
        $day     = (int) $request->input('day');
        $slot    = (int) $request->input('slot');
        $color   = $request->input('color') ?: null;
        $subject = $request->input('subject');
        $book    = $request->input('book') ?: null;

        $query = Timetable::forUser($userId)->active()->where('day', $day)->where('slot', $slot);
        if ($year === null) {
            $query->whereNull('year');
        } else {
            $query->where('year', $year);
        }

        if ($query->exists()) {
            return response()->json(['response' => 'error', 'text' => __('timetable-error-slot-occupied')]);
        }

        Timetable::create([
            'user'    => $userId,
            'year'    => $year,
            'day'     => $day,
            'slot'    => $slot,
            'subject' => $subject,
            'book'    => $book,
            'fore'    => $color ? str_replace('#', '', $color) : '',
        ]);

        return response()->json(['response' => 'success', 'text' => __('timetable-success-added')]);
    }

    protected function edit(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if ($err = $this->validateInput(
            [
                'id'      => $request->query('id'),
                'day'     => $request->input('day'),
                'slot'    => $request->input('slot'),
                'subject' => $request->input('subject'),
            ],
            [
                'id'      => 'required|integer|min:1',
                'day'     => 'required|integer|min:1|max:7',
                'slot'    => 'required|integer|min:0|max:255',
                'subject' => 'required|string',
            ],
            [
                'id.required'      => __('timetable-error-id-required'),
                'day.required'     => __('timetable-error-day-required'),
                'day.min'          => __('timetable-error-day-range'),
                'day.max'          => __('timetable-error-day-range'),
                'slot.required'    => __('timetable-error-slot-required'),
                'slot.max'         => __('timetable-error-slot-range'),
                'subject.required' => __('timetable-error-subject-required'),
            ]
        )) return $err;

        $id      = (int) $request->query('id');
        $year    = $request->input('year') !== '' && $request->input('year') !== null ? (int) $request->input('year') : null;
        $day     = (int) $request->input('day');
        $slot    = (int) $request->input('slot');
        $color   = $request->input('color') ?: null;
        $subject = $request->input('subject');
        $book    = $request->input('book') ?: null;

        $entry = Timetable::forUser($userId)->where('id', $id)->first();
        if (!$entry) return response()->json(['response' => 'error', 'text' => __('timetable-error-not-found')]);

        $query = Timetable::forUser($userId)->active()->where('day', $day)->where('slot', $slot)->where('id', '!=', $id);
        if ($year === null) {
            $query->whereNull('year');
        } else {
            $query->where('year', $year);
        }

        if ($query->exists()) {
            return response()->json(['response' => 'error', 'text' => __('timetable-error-slot-occupied')]);
        }

        $entry->update([
            'year'    => $year,
            'day'     => $day,
            'slot'    => $slot,
            'subject' => $subject,
            'book'    => $book,
            'fore'    => $color ? str_replace('#', '', $color) : '',
        ]);

        return response()->json(['response' => 'success', 'text' => __('timetable-success-updated')]);
    }

    protected function remove(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if ($err = $this->validateInput(
            ['id' => $request->query('id')],
            ['id' => 'required|integer|min:1'],
            ['id.*' => __('timetable-error-id-required')]
        )) return $err;

        $id = (int) $request->query('id');

        $affected = Timetable::forUser($userId)->where('id', $id)->update(['deleted_at' => now()]);

        if (!$affected) return response()->json(['response' => 'error', 'text' => __('timetable-error-not-found')]);
        return response()->json(['response' => 'success', 'text' => __('timetable-success-removed')]);
    }
}
