<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    /** @var list<string> */
    public const TYPES = ['fault', 'provisioning', 'number_port'];

    /** @var list<string> */
    public const STATUSES = ['open', 'in_progress', 'waiting', 'resolved'];

    /** @var list<string> */
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected $fillable = [
        'reference',
        'account_id',
        'service_line_id',
        'type',
        'subject',
        'description',
        'priority',
        'status',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function serviceLine(): BelongsTo
    {
        return $this->belongsTo(ServiceLine::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class)->orderByDesc('created_at');
    }

    public function recordStatusChange(?string $from, string $to, ?string $note = null): void
    {
        if ($from === $to) {
            return;
        }

        $this->statusHistories()->create([
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
        ]);
    }
}
