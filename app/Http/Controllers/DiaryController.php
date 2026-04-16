<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\CryptoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiaryController extends Controller
{
    public function __construct(protected CryptoService $crypto)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $type = $request->query('type');
        return match ($type) {
            'events'  => $this->events($request),
            'details' => $this->details($request),
            'create'  => $this->create($request),
            'edit'    => $this->edit($request),
            default   => response()->json(['response' => 'error', 'text' => 'Invalid type.']),
        };
    }

    protected function events(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $year   = (int) $request->query('year', date('Y'));
        $month  = (int) $request->query('month', date('m'));

        $events = File::where('user_id', $userId)
            ->active()
            ->ofType('diary')
            ->whereYear('diary_date', $year)
            ->whereMonth('diary_date', $month)
            ->select('id', 'name', 'diary_type', 'diary_date', 'diary_color', 'diary_priority', 'diary_reminder', 'fav')
            ->orderBy('diary_date')
            ->get();

        return response()->json(['response' => 'success', 'events' => $events]);
    }

    protected function details(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');

        $event = File::where('id', $id)
            ->where('user_id', $userId)
            ->ofType('diary')
            ->whereNull('deleted_at')
            ->first(['id', 'name', 'diary_type', 'diary_date', 'diary_priority', 'diary_color', 'diary_reminder', 'fav', 'html', 'cypher']);

        if (!$event) return response()->json(['response' => 'error', 'text' => __('diary-error-not-found')]);

        $content = null;
        if ($event->cypher && $event->html) {
            try {
                $content = $this->crypto->decrypt($event->html, $event->cypher, $userId);
            } catch (\Throwable $e) {
                // decryption failed, content stays null
            }
        } elseif ($event->html) {
            $content = $event->html;
        }

        return response()->json(['response' => 'success', 'event' => [
            'id'             => $event->id,
            'name'           => $event->name,
            'diary_type'     => $event->diary_type,
            'diary_date'     => $event->diary_date,
            'diary_priority' => $event->diary_priority,
            'diary_color'    => $event->diary_color,
            'diary_reminder' => $event->diary_reminder,
            'fav'            => $event->fav,
            'content'        => $content,
        ]]);
    }

    protected function create(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if ($err = $this->validateInput(
            [
                'type'    => $request->post('type'),
                'subject' => $request->input('subject'),
                'date'    => $request->input('date'),
            ],
            [
                'type'    => 'required|string',
                'subject' => 'required|string',
                'date'    => 'required|date',
            ],
            [
                'type.required'    => __('diary-error-type-required'),
                'subject.required' => __('diary-error-subject-required'),
                'date.required'    => __('diary-error-date-required'),
                'date.date'        => __('diary-error-date-invalid'),
            ]
        )) return $err;

        $color    = ltrim((string) $request->input('color', ''), '#');
        $reminder = $request->input('reminder');
        $priority = (int) $request->input('priority', 0);
        $content  = $request->input('content');

        $encrypted = null;
        if ($content !== null) {
            $encrypted = $this->crypto->encrypt($content, $userId);
        }

        File::create([
            'user_id'        => $userId,
            'type'           => 'diary',
            'name'           => $request->input('subject'),
            'diary_type'     => $request->input('type'),
            'diary_date'     => $request->input('date'),
            'diary_reminder' => ($reminder === '0000-00-00' || !$reminder) ? null : $reminder,
            'diary_priority' => $priority,
            'diary_color'    => $color ?: null,
            'cypher'         => $encrypted ? $encrypted['key'] : null,
            'html'           => $encrypted ? $encrypted['data'] : null,
        ]);

        return response()->json(['response' => 'success', 'text' => __('diary-success-created')]);
    }

    protected function edit(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->input('id');

        if ($err = $this->validateInput(
            [
                'id'      => $request->input('id'),
                'subject' => $request->input('subject'),
                'date'    => $request->input('date'),
            ],
            [
                'id'      => 'required|integer|min:1',
                'subject' => 'required|string',
                'date'    => 'required|date',
            ],
            [
                'id.required'      => __('diary-error-id-required'),
                'subject.required' => __('diary-error-subject-required'),
                'date.required'    => __('diary-error-date-required'),
                'date.date'        => __('diary-error-date-invalid'),
            ]
        )) return $err;

        $event = File::where('id', $id)->where('user_id', $userId)->ofType('diary')->first();
        if (!$event) return response()->json(['response' => 'error', 'text' => __('diary-error-not-found')]);

        $color    = ltrim((string) $request->input('color', ''), '#');
        $reminder = $request->input('reminder');
        $priority = (int) $request->input('priority', 0);
        $content  = $request->input('content');

        $update = [
            'diary_type'     => $request->input('type'),
            'name'           => $request->input('subject'),
            'diary_date'     => $request->input('date'),
            'diary_reminder' => ($reminder === '0000-00-00' || !$reminder) ? null : $reminder,
            'diary_priority' => $priority,
            'diary_color'    => $color ?: null,
        ];

        if ($content !== null) {
            $encrypted              = $this->crypto->encrypt($content, $userId);
            $update['cypher']       = $encrypted['key'];
            $update['html']         = $encrypted['data'];
        }

        $event->update($update);
        return response()->json(['response' => 'success', 'text' => __('diary-success-updated')]);
    }
}
