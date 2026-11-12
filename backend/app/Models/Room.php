<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'room_number',
        'room_type',
        'price',
        'capacity',
        'status',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'capacity' => 'integer',
    ];

    /**
     * The tenants assigned to this room (joined on tenants.room_id = rooms.id).
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * How many tenants currently occupy the room.
     */
    public function getOccupancyAttribute(): int
    {
        return $this->relationLoaded('tenants')
            ? $this->tenants->count()
            : $this->tenants()->count();
    }

    /**
     * Capacity is the source of truth for availability: a room is full only
     * once its tenant count reaches its capacity.
     */
    public function getIsFullAttribute(): bool
    {
        return $this->occupancy >= max((int) $this->capacity, 1);
    }

    public function getAvailableSlotsAttribute(): int
    {
        return max(max((int) $this->capacity, 1) - $this->occupancy, 0);
    }

    /**
     * Whether another tenant may still be assigned to this room. Pass the ID of
     * the tenant being edited so their own existing slot is not counted twice.
     */
    public function hasFreeSlot(?int $ignoringTenantId = null): bool
    {
        $occupancy = $ignoringTenantId
            ? $this->tenants()->whereKeyNot($ignoringTenantId)->count()
            : $this->occupancy;

        return $occupancy < max((int) $this->capacity, 1);
    }

    /**
     * Recalculate the stored status badge from the live tenant count.
     * Returns true when the status changed.
     */
    public function syncOccupancyStatus(): bool
    {
        $expected = $this->is_full ? 'occupied' : 'available';

        if ($this->status === $expected) {
            return false;
        }

        $this->forceFill(['status' => $expected])->save();

        return true;
    }
}
