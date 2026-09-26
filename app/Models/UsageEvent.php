<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsageEvent extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'idempotency_key', 'units', 'usage_date'];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
