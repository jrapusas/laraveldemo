<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id',
        'product_code',
        'description',
        'quantity',
        'unit_price_aud',
        'line_total_aud',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_aud' => 'decimal:2',
            'line_total_aud' => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
