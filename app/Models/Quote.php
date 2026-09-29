<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    /** @var list<string> */
    public const STATUSES = ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'expired'];

    protected $fillable = [
        'reference',
        'account_id',
        'title',
        'status',
        'subtotal_aud',
        'valid_until',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'subtotal_aud' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function provisioningOrders(): HasMany
    {
        return $this->hasMany(ProvisioningOrder::class);
    }
}
