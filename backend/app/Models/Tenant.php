<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'room_id',
    ];

    /**
     * The room this tenant is assigned to
     * (joined on tenants.room_id = rooms.id).
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * The login account this tenant record belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    /**
     * Delete the tenant and its linked tenant login account, if any.
     */
    public function deleteWithAccount(): void
    {
        DB::transaction(function () {
            $user = $this->user;

            if (!$this->delete()) {
                return;
            }

            if ($user?->role === 'tenant') {
                $user->tokens()->delete();
                $user->delete();
            }
        });
    }

    /**
     * A room's status is derived from how many tenants point at it, so every
     * write path (tenant CRUD, check-in, check-out, delete) has to refresh the
     * rooms on both sides of the move. Doing it here means no caller can forget.
     */
    protected static function booted(): void
    {
        static::saved(function (Tenant $tenant) {
            $to = $tenant->room_id;
            $from = $tenant->wasRecentlyCreated ? null : $tenant->getOriginal('room_id');

            if ($from === $to) {
                return;
            }

            static::refreshRooms([$from, $to]);
        });

        static::deleted(function (Tenant $tenant) {
            static::refreshRooms([$tenant->room_id]);
        });
    }

    protected static function refreshRooms(array $roomIds): void
    {
        $roomIds = array_filter(array_unique($roomIds));

        if ($roomIds === []) {
            return;
        }

        Room::whereIn('id', $roomIds)->get()->each(
            fn (Room $room) => $room->syncOccupancyStatus()
        );
    }
}
