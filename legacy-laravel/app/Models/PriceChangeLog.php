<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PriceChangeLog extends Model
{
    protected $fillable = [
        'product_id',
        'harga_lama',
        'harga_baru',
        'sumber',
        'reference_type',
        'reference_id',
        'user_id',
    ];

    protected $casts = [
        'harga_lama' => 'decimal:2',
        'harga_baru' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Computed attributes.
     */
    public function getSelisihAttribute(): float
    {
        return (float) $this->harga_baru - (float) $this->harga_lama;
    }

    public function getPersenAttribute(): float
    {
        $lama = (float) $this->harga_lama;

        return $lama > 0 ? round((($this->selisih) / $lama) * 100, 1) : 0;
    }

    public function getTipeAttribute(): string
    {
        return $this->selisih >= 0 ? 'naik' : 'turun';
    }
}
