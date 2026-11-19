@extends('admin.layout')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('aside', now()->format('l, M d, Y'))

@push('styles')
<style>
    /* Key figures */
    .bh-kpi {
        position: relative; overflow: hidden; height: 100%;
        background: var(--bh-surface); border: 1px solid var(--bh-line);
        border-radius: var(--bh-radius); box-shadow: var(--bh-shadow);
        padding: 16px 18px 15px 20px;
        transition: transform .16s ease, box-shadow .16s ease;
    }
    .bh-kpi:hover { transform: translateY(-2px); box-shadow: 0 1px 2px rgba(20,30,45,.05), 0 18px 34px -18px rgba(20,30,45,.26); }
    .bh-kpi::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--accent, var(--bh-brand-2)); }
    .bh-kpi-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .bh-kpi-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .09em; font-weight: 650; color: var(--bh-muted); padding-top: 8px; }
    .bh-kpi-icon { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; font-size: 1.05rem; flex: 0 0 36px; background: var(--accent-soft, var(--bh-brand-soft)); color: var(--accent, var(--bh-brand-2)); }
    .bh-kpi-value { font-size: 1.72rem; font-weight: 700; letter-spacing: -.022em; line-height: 1.15; margin-top: 4px; font-variant-numeric: tabular-nums; }
    .bh-kpi-foot { margin-top: 7px; font-size: .8rem; color: var(--bh-muted); }
    .tone-brand { --accent: #40739e; --accent-soft: #eaf0f6; }
    .tone-ink   { --accent: #46566b; --accent-soft: #eef1f5; }
    .tone-ok    { --accent: #1f8a55; --accent-soft: #e7f4ed; }
    .tone-bad   { --accent: #c93c3c; --accent-soft: #fcebeb; }

    /* Panels */
    .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .panel-title { font-size: 1rem; font-weight: 650; letter-spacing: -.011em; margin: 0; }
    .link-quiet { font-size: .8rem; font-weight: 600; color: var(--bh-brand-2); text-decoration: none; white-space: nowrap; }
    .link-quiet:hover { text-decoration: underline; }
    .sub-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .1em; font-weight: 650; color: var(--bh-muted); }

    /* Meters */
    .meter { display: flex; height: 8px; border-radius: 999px; background: #eaeef5; overflow: hidden; }
    .meter > span { display: block; flex: 0 0 auto; height: 100%; transition: width .4s ease; }
    .meter-lg { height: 10px; }
    .meter-paid { background: var(--bh-ok); }
    .meter-due { background: var(--bh-warn); }
    .meter-bed { background: var(--bh-brand-2); }
    .meter-full { background: var(--bh-bad); }
    .legend { display: flex; align-items: center; gap: 7px; font-size: .78rem; color: var(--bh-muted); }
    .legend::before { content: ""; width: 8px; height: 8px; border-radius: 3px; background: var(--sw, var(--bh-brand-2)); }

    /* Bed dots */
    .beds { display: inline-flex; gap: 4px; align-items: center; }
    .beds i { width: 9px; height: 9px; border-radius: 50%; background: #dde3ec; display: block; }
    .beds i.on { background: var(--bh-brand-2); }

    .roomline { display: flex; align-items: center; justify-content: space-between; gap: 6px 12px; flex-wrap: wrap; padding: 9px 0; border-top: 1px dashed var(--bh-line); }
    .roomline:first-of-type { border-top: 0; }
    .roomline > div { min-width: 0; }
    .roomline span { white-space: nowrap; }

    /* Feeds */
    .feed-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-top: 1px solid var(--bh-line); }
    .feed-row:first-of-type { border-top: 0; padding-top: 2px; }
    .feed-main { min-width: 0; flex: 1; }
    .feed-name { font-size: .875rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .feed-meta { font-size: .76rem; color: var(--bh-muted); }
    .feed-end { text-align: right; flex: 0 0 auto; }
    .money { font-variant-numeric: tabular-nums; font-weight: 650; font-size: .875rem; }
    .soft-badge { font-size: .68rem; font-weight: 650; letter-spacing: .03em; text-transform: uppercase; border-radius: 999px; padding: .28em .62em; }
    .soft-paid { background: #e7f4ed; color: #1a7a4b; }
    .soft-pending { background: #fdf1de; color: #96600a; }
    .soft-overdue { background: #fcebeb; color: #b03333; }
    .soft-cancelled { background: #eef1f5; color: #5a6779; }

    /* Quick actions */
    .qa {
        display: flex; align-items: center; gap: 11px; width: 100%;
        padding: 10px 12px; border: 1px solid var(--bh-line); border-radius: 11px;
        background: #fbfcfe; color: var(--bh-ink); text-decoration: none;
        font-size: .875rem; font-weight: 550;
        transition: border-color .15s ease, background-color .15s ease, color .15s ease;
    }
    .qa:hover { border-color: #c7d6e6; background: #fff; color: var(--bh-brand); }
    .qa i.lead { color: var(--bh-brand-2); font-size: 1rem; width: 1.1rem; text-align: center; }
    .qa .chev { margin-left: auto; color: #b6c0cf; }

    .empty { text-align: center; color: var(--bh-muted); font-size: .85rem; padding: 20px 10px; }
    .empty i { display: block; font-size: 1.35rem; opacity: .45; margin-bottom: 7px; }

    @media (max-width: 575.98px) {
        .bh-kpi-value { font-size: 1.5rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .bh-kpi, .qa, .meter > span { transition: none; }
    }
</style>
@endpush

@section('content')
@php
    $bedRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0;
    $roomRate = $roomsCount > 0 ? round(($occupiedRooms / $roomsCount) * 100) : 0;
    $collectable = $collectedTotal + $pendingTotal;
    $collectionRate = $collectable > 0 ? round(($collectedTotal / $collectable) * 100) : 0;
    $dueWidth = $collectable > 0 ? round(($pendingTotal / $collectable) * 100) : 0;
    $badgeTones = ['paid' => 'soft-paid', 'pending' => 'soft-pending', 'overdue' => 'soft-overdue', 'cancelled' => 'soft-cancelled'];
    $initials = fn ($name) => strtoupper(substr($name ?? '?', 0, 1));

    $recentPayments = $recentPayments ?? collect();
    $recentAnnouncements = $recentAnnouncements ?? collect();
@endphp

<!-- Headline stats -->
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="bh-kpi tone-brand">
            <div class="bh-kpi-top">
                <span class="bh-kpi-label">Rooms</span>
                <span class="bh-kpi-icon"><i class="bi bi-door-closed"></i></span>
            </div>
            <div class="bh-kpi-value">{{ $roomsCount }}</div>
            <div class="bh-kpi-foot">
                <span class="text-success fw-semibold">{{ $availableRooms }} open</span>
                &middot;
                <span class="text-danger fw-semibold">{{ $occupiedRooms }} full</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="bh-kpi tone-ink">
            <div class="bh-kpi-top">
                <span class="bh-kpi-label">Tenants</span>
                <span class="bh-kpi-icon"><i class="bi bi-people"></i></span>
            </div>
            <div class="bh-kpi-value">{{ $tenantsCount }}</div>
            <div class="bh-kpi-foot">
                {{ $housedTenants }} with a room &middot;
                <span class="{{ $tenantsCount - $housedTenants > 0 ? 'text-warning-emphasis fw-semibold' : '' }}">
                    {{ $tenantsCount - $housedTenants }} unassigned
                </span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="bh-kpi tone-ok">
            <div class="bh-kpi-top">
                <span class="bh-kpi-label">Collected</span>
                <span class="bh-kpi-icon"><i class="bi bi-cash-stack"></i></span>
            </div>
            <div class="bh-kpi-value">&#8369;{{ number_format($collectedTotal, 2) }}</div>
            <div class="bh-kpi-foot">This year: &#8369;{{ number_format($collectedThisYear, 2) }}</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="bh-kpi tone-bad">
            <div class="bh-kpi-top">
                <span class="bh-kpi-label">Uncollected</span>
                <span class="bh-kpi-icon"><i class="bi bi-hourglass-split"></i></span>
            </div>
            <div class="bh-kpi-value {{ $pendingTotal > 0 ? 'text-danger' : '' }}">&#8369;{{ number_format($pendingTotal, 2) }}</div>
            <div class="bh-kpi-foot">{{ $collectionRate }}% of billed rent collected</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <!-- Occupancy -->
    <div class="col-xl-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 p-md-4">
                <div class="panel-head">
                    <h5 class="panel-title">Occupancy</h5>
                    <a href="{{ route('admin.checkins.index') }}" class="link-quiet">Check-in / Check-out</a>
                </div>

                <div class="d-flex justify-content-between align-items-baseline small mb-1">
                    <span>Bed usage</span>
                    <span class="fw-semibold">{{ $occupiedBeds }} / {{ $totalBeds }} &middot; {{ $bedRate }}%</span>
                </div>
                <div class="meter meter-lg mb-3">
                    <span class="meter-bed" @style(['width' => $bedRate . '%'])></span>
                </div>

                <div class="d-flex justify-content-between align-items-baseline small mb-1">
                    <span>Rooms at full capacity</span>
                    <span class="fw-semibold">{{ $occupiedRooms }} / {{ $roomsCount }} &middot; {{ $roomRate }}%</span>
                </div>
                <div class="meter meter-lg mb-3">
                    <span class="meter-full" @style(['width' => $roomRate . '%'])></span>
                </div>

                @php($roomsNearCapacity = $roomsNearCapacity ?? collect())
                <div class="d-flex justify-content-between align-items-center mb-1 mt-4">
                    <span class="sub-label">Rooms with free beds</span>
                    <span class="text-muted small">{{ $roomsNearCapacity->count() }} listed</span>
                </div>
                @if ($roomsNearCapacity->isNotEmpty())
                    @foreach ($roomsNearCapacity as $room)
                        <div class="roomline">
                            <div class="d-flex align-items-center gap-2">
                                <span class="beds">
                                    @for ($i = 0; $i < $room->capacity; $i++)
                                        <i class="{{ $i < $room->tenants_count ? 'on' : '' }}"></i>
                                    @endfor
                                </span>
                                <span class="small fw-semibold">Room {{ $room->room_number }}</span>
                                <span class="text-muted small">{{ $room->room_type ?: 'Standard' }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted small">&#8369;{{ number_format($room->price, 0) }}/mo</span>
                                <span class="text-success fw-semibold small">{{ $room->capacity - $room->tenants_count }} left</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty">
                        <i class="bi bi-door-open"></i>
                        No partially-filled multi-bed rooms right now.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent payments -->
    <div class="col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 p-md-4">
                <div class="panel-head">
                    <h5 class="panel-title">Recent Payments</h5>
                    <a href="{{ route('admin.payments.index') }}" class="link-quiet">View all</a>
                </div>
                @if (($recentPayments ?? collect())->isNotEmpty())
                    @foreach (($recentPayments ?? collect()) as $payment)
                        <div class="feed-row">
                            <span class="avatar" style="width:30px;height:30px;flex:0 0 30px;font-size:.72rem;border-radius:9px">
                                {{ $initials($payment->tenant->name ?? '?') }}
                            </span>
                            <div class="feed-main">
                                <div class="feed-name">{{ $payment->tenant->name ?? 'Unlinked' }}</div>
                                <div class="feed-meta">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : 'No date' }}</div>
                            </div>
                            <div class="feed-end">
                                <div class="money">&#8369;{{ number_format($payment->amount, 2) }}</div>
                                <span class="soft-badge {{ $badgeTones[$payment->status] ?? 'soft-cancelled' }}">{{ $payment->status }}</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty">
                        <i class="bi bi-cash-coin"></i>
                        No payments recorded yet.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Announcements -->
    <div class="col-xl-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 p-md-4">
                <div class="panel-head">
                    <h5 class="panel-title">Latest Announcements</h5>
                    <a href="{{ route('admin.announcements.index') }}" class="link-quiet">Manage</a>
                </div>
                @if (($recentAnnouncements ?? collect())->isNotEmpty())
                    @foreach (($recentAnnouncements ?? collect()) as $announcement)
                        <div class="feed-row">
                            <span class="bh-kpi-icon tone-brand" style="width:30px;height:30px;flex:0 0 30px;font-size:.85rem;border-radius:9px">
                                <i class="bi bi-megaphone"></i>
                            </span>
                            <div class="feed-main">
                                <div class="feed-name">{{ $announcement->title }}</div>
                                <div class="feed-meta text-muted" style="font-weight:400">{{ Str::limit($announcement->content, 120) }}</div>
                            </div>
                            <div class="feed-end">
                                <div class="feed-meta">{{ $announcement->published_at ? $announcement->published_at->format('M d') : 'Draft' }}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty">
                        <i class="bi bi-megaphone"></i>
                        Nothing posted yet.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Collection status + shortcuts -->
    <div class="col-xl-4">
        <div class="d-flex flex-column gap-3 h-100">
            <div class="card shadow-sm border-0">
                <div class="card-body p-3 p-md-4">
                    <div class="panel-head mb-2">
                        <h5 class="panel-title">Rent Collection</h5>
                        <span class="sub-label">{{ $collectionRate }}%</span>
                    </div>
                    <div class="meter meter-lg mb-3">
                        <span class="meter-paid" data-width="{{ (float) $collectionRate }}"></span>
                        <span class="meter-due" data-width="{{ (float) $dueWidth }}"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="legend" style="--sw: var(--bh-ok)">Collected</span>
                        <span class="money">&#8369;{{ number_format($collectedTotal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="legend" style="--sw: var(--bh-warn)">Uncollected</span>
                        <span class="money">&#8369;{{ number_format($pendingTotal, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 flex-grow-1">
                <div class="card-body p-3 p-md-4">
                    <div class="panel-head">
                        <h5 class="panel-title">Quick Actions</h5>
                        <span class="sub-label">{{ $activeCheckIns }} in-house</span>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <a class="qa" href="{{ route('admin.payments.index') }}">
                            <i class="bi bi-plus-circle lead"></i> Record a payment <i class="bi bi-chevron-right chev"></i>
                        </a>
                        <a class="qa" href="{{ route('admin.checkins.index') }}">
                            <i class="bi bi-box-arrow-in-right lead"></i> Check in a tenant <i class="bi bi-chevron-right chev"></i>
                        </a>
                        <a class="qa" href="{{ route('admin.rooms.index') }}">
                            <i class="bi bi-door-closed lead"></i> Manage rooms <i class="bi bi-chevron-right chev"></i>
                        </a>
                        <a class="qa" href="{{ route('admin.announcements.index') }}">
                            <i class="bi bi-megaphone lead"></i> Post an announcement <i class="bi bi-chevron-right chev"></i>
                        </a>
                        <a class="qa" href="{{ route('admin.rules.index') }}">
                            <i class="bi bi-journal-text lead"></i> House rules <span class="text-muted ms-auto">{{ $rulesCount }} active</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
