<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvisioningOrder extends Model
{
    /** @var list<string> */
    public const STATUSES = ['submitted', 'scheduled', 'in_progress', 'completed', 'cancelled'];

    protected $fillable = [
        'reference',
        'account_id',
        'quote_id',
        'title',
        'status',
        'requested_for',
        'completed_at',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'requested_for' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
