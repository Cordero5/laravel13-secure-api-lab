<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'paid', 'overdue', 'cancelled'];

    protected $fillable = [
        'tenant_id',
        'amount',
        'payment_date',
        'status',
        'reference_number',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date:Y-m-d',
    ];

    /**
     * The tenant this payment belongs to (payments.tenant_id = tenants.id).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
