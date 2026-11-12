<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CheckIn;
use App\Models\HouseRule;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $rooms = Room::withCount('tenants')->get();

        $roomsCount = $rooms->count();
        $occupiedRooms = $rooms->filter->is_full->count();
        $availableRooms = $roomsCount - $occupiedRooms;
        $totalBeds = (int) $rooms->sum('capacity');
        $occupiedBeds = (int) $rooms->sum(fn ($room) => min($room->tenants_count, $room->capacity));

        $tenantsCount = Tenant::count();
        $housedTenants = Tenant::whereNotNull('room_id')->count();

        $collectedTotal = (float) Payment::where('status', 'paid')->sum('amount');
        $pendingTotal = (float) Payment::whereIn('status', ['pending', 'overdue'])->sum('amount');
        $collectedThisYear = (float) Payment::where('status', 'paid')
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $rulesCount = HouseRule::where('is_active', true)->count();
        $activeCheckIns = CheckIn::where('status', 'checked_in')->count();

        $recentPayments = Payment::with('tenant')->latest('payment_date')->limit(5)->get();
        $recentAnnouncements = Announcement::latest('published_at')->limit(5)->get();
        $roomsNearCapacity = $rooms
            ->filter(fn ($room) => $room->capacity > 1 && !$room->is_full && $room->tenants_count > 0)
            ->sortByDesc('tenants_count')
            ->take(5);

        return view('admin.dashboard', compact(
            'roomsCount', 'occupiedRooms', 'availableRooms',
            'totalBeds', 'occupiedBeds',
            'tenantsCount', 'housedTenants',
            'collectedTotal', 'pendingTotal', 'collectedThisYear',
            'rulesCount', 'activeCheckIns',
            'recentPayments', 'recentAnnouncements', 'roomsNearCapacity'
        ));
    }
}
