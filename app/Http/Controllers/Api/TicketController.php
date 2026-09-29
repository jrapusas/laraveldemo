<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AppliesListSort;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    use AppliesListSort;

    /** @var array<string, string> */
    private const SORT_MAP = [
        'reference' => 'tickets.reference',
        'account' => 'accounts.name',
        'type' => 'tickets.type',
        'subject' => 'tickets.subject',
        'priority' => 'tickets.priority',
        'status' => 'tickets.status',
        'created_at' => 'tickets.created_at',
    ];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', Rule::in(Ticket::STATUSES)],
            'type' => ['sometimes', 'nullable', 'string', Rule::in(Ticket::TYPES)],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:10', 'max:100'],
            ...$this->listSortValidationRules(self::SORT_MAP),
        ]);

        $query = Ticket::query()
            ->with(['account:id,name,tier', 'serviceLine:id,label,did']);

        if (($filters['sort'] ?? null) === 'account') {
            $query->leftJoin('accounts', 'tickets.account_id', '=', 'accounts.id')
                ->select('tickets.*');
        }

        if (! empty($filters['status'])) {
            $query->where('tickets.status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('tickets.type', $filters['type']);
        }

        if (! empty($filters['q'])) {
            $like = $this->likeContains($filters['q']);
            $query->where(function ($inner) use ($like) {
                $inner->where('tickets.reference', 'like', $like)
                    ->orWhere('tickets.subject', 'like', $like);
            });
        }

        $this->applyListSort($query, $filters, self::SORT_MAP);

        $perPage = $filters['per_page'] ?? 100;

        return response()->json([
            'data' => $query->paginate($perPage),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'service_line_id' => [
                'nullable',
                Rule::exists('service_lines', 'id')->where(
                    fn ($q) => $q->where('account_id', $request->input('account_id'))
                ),
            ],
            'type' => ['required', Rule::in(Ticket::TYPES)],
            'subject' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
        ]);

        $ticket = retry(5, function () use ($validated) {
            return DB::transaction(function () use ($validated) {
                $ticket = Ticket::create([
                    ...$validated,
                    'reference' => 'UC-'.strtoupper(Str::random(6)),
                    'status' => 'open',
                ]);

                $ticket->recordStatusChange(null, 'open');

                return $ticket;
            });
        }, 0, fn ($e) => $e instanceof UniqueConstraintViolationException);

        $ticket->load(['account:id,name,tier', 'serviceLine:id,label,did']);

        return response()->json(['data' => $ticket], 201);
    }

    public function show(Ticket $ticket): JsonResponse
    {
        $ticket->load([
            'account',
            'serviceLine',
            'statusHistories' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        return response()->json(['data' => $ticket]);
    }

    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(Ticket::STATUSES)],
            'priority' => ['sometimes', Rule::in(Ticket::PRIORITIES)],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $statusChanging = array_key_exists('status', $validated)
            && $validated['status'] !== $ticket->status;

        $statusNote = null;
        if ($statusChanging) {
            $statusNote = isset($validated['status_note']) ? trim((string) $validated['status_note']) : '';
            if ($statusNote === '') {
                return response()->json([
                    'message' => 'A note is required when changing status.',
                ], 422);
            }
        }

        unset($validated['status_note']);

        DB::transaction(function () use ($ticket, $validated, $statusChanging, $statusNote) {
            // ponytail: row lock. SQLite drops FOR UPDATE; MySQL keeps it. 409 if status moved.
            $locked = Ticket::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();

            if ($statusChanging && $locked->status !== $ticket->status) {
                abort(409, 'Ticket status changed. Reload and try again.');
            }

            if ($statusChanging) {
                $locked->recordStatusChange($locked->status, $validated['status'], $statusNote);
            }

            $locked->update($validated);
        });

        $ticket->refresh()->load([
            'account:id,name,tier',
            'serviceLine:id,label,did',
            'statusHistories' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        return response()->json(['data' => $ticket]);
    }
}
