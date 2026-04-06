@extends('layouts.mobile')

@section('title', 'Leaflet Tracker - Route Assignment')

@section('body-class', "overflow-x-hidden bg-[#f6fbf6] font-['Inter'] text-[#171d1a] selection:bg-[#a5d6a7] selection:text-[#1b5e20]")

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="preconnect" href="https://a.tile.openstreetmap.org" crossorigin>
    <link rel="preconnect" href="https://b.tile.openstreetmap.org" crossorigin>
    <link rel="preconnect" href="https://c.tile.openstreetmap.org" crossorigin>

    <style>
        .tracker-shadow {
            box-shadow: 0 16px 32px 0 rgba(18, 18, 18, 0.06);
        }

        .leaflet-container {
            font-family: 'Inter', sans-serif;
            background: #f1f8f1;
        }

        .leaflet-control-attribution {
            font-size: 10px;
        }

        #assignment-map {
            width: 100%;
            height: 100%;
        }
    </style>
@endpush

@section('content')
    <main
        data-assignment
        data-route='@json($campaignRoute->route_data)'
        data-status="{{ $assignment->status }}"
        data-start-url="{{ route('assignments.start', $assignment) }}"
        data-complete-url="{{ route('assignments.complete', $assignment) }}"
        data-tracking-index-url="{{ route('assignments.tracking.index', $assignment) }}"
        data-tracking-store-url="{{ route('assignments.tracking.store', $assignment) }}"
        data-osrm-url="https://router.project-osrm.org"
        class="relative min-h-dvh w-full pb-24"
    >
        <header class="fixed top-0 z-50 w-full border-b border-[#e8f5e9] bg-[#f6fbf6]/80 backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-md items-center justify-between px-6">
                <div class="flex items-center gap-3">
                    <x-icon name="map" class="h-6 w-6 text-[#1b5e20]" />
                    <h1 class="font-['Plus_Jakarta_Sans'] text-sm font-black uppercase tracking-wider text-[#1b5e20]">Route Assignment</h1>
                </div>
                <span class="rounded-full bg-[#e8f5e9] px-3 py-1 text-[11px] font-black uppercase tracking-wider text-[#1b5e20]">
                    {{ $assignment->status === 'in_progress' ? 'In Progress' : ucfirst($assignment->status) }}
                </span>
            </div>
        </header>

        <section class="mx-auto max-w-md px-6 pt-24">
            <div class="mb-8">
                <h2 class="font-['Plus_Jakarta_Sans'] text-2xl font-black uppercase tracking-tight text-[#1b5e20]">
                    {{ $campaignRoute->campaign ? $campaignRoute->campaign->name : 'Campaign' }}
                </h2>
                <p class="mt-2 text-[11px] font-black uppercase tracking-[0.2em] text-[#41493e]">Route Assignment</p>
            </div>

            <section class="rounded-2xl bg-white p-8 shadow-[0_6px_30px_rgba(23,29,26,0.05)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-['Plus_Jakarta_Sans'] text-2xl font-black text-[#171d1a]">{{ $assignment->user?->name ?? 'Volunteer' }}</h3>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-[#1b6d24]"></span>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[#1b6d24]">
                                {{ $assignment->status === 'in_progress' ? 'In Progress' : ucfirst($assignment->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#f0f5f0] text-[#1b5e20] font-['Plus_Jakarta_Sans'] text-lg font-black">
                        {{ strtoupper(substr($assignment->user?->name ?? 'V', 0, 1)) }}
                    </div>
                </div>
                <p class="mt-4 text-sm font-medium text-[#41493e]">
                    Please complete the assigned route and mark it done when finished.
                </p>
            </section>

            <section class="mt-6">
                <div class="relative h-64 overflow-hidden rounded-2xl bg-[#e4e9e5] shadow-[0_4px_24px_rgba(23,29,26,0.05)]">
                    <div id="assignment-map" class="absolute inset-0"></div>
                    <div class="absolute bottom-4 left-4 right-4 rounded-xl bg-white/90 p-4 shadow-lg backdrop-blur-md">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e8f5e9] text-[#1b5e20]">
                                <x-icon name="map" class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#41493e]">Status</p>
                                <p class="font-['Plus_Jakarta_Sans'] text-sm font-bold text-[#1b5e20]">
                                    {{ $assignment->status === 'in_progress' ? 'Ready to continue' : 'Ready to begin' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mt-6 grid grid-cols-2 gap-4">
                <div class="rounded-2xl bg-[#f0f5f0] p-5">
                    <x-icon name="polyline" class="h-5 w-5 text-[#1b5e20]" />
                    <p class="mt-3 text-[10px] font-black uppercase tracking-[0.2em] text-[#41493e]">Route Length</p>
                    <p id="route-length" class="mt-2 font-['Plus_Jakarta_Sans'] text-xl font-black text-[#1b5e20]">—</p>
                </div>
                <div class="rounded-2xl bg-[#f0f5f0] p-5">
                    <x-icon name="history" class="h-5 w-5 text-[#1b5e20]" />
                    <p class="mt-3 text-[10px] font-black uppercase tracking-[0.2em] text-[#41493e]">Last Update</p>
                    <p class="mt-2 font-['Plus_Jakarta_Sans'] text-xl font-black text-[#1b5e20]">{{ $assignment->updated_at->diffForHumans() }}</p>
                </div>
            </section>

            <section class="mt-6 rounded-2xl bg-white p-6 shadow-[0_6px_30px_rgba(23,29,26,0.05)]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#41493e]">Progress</p>
                        <p id="route-progress" class="mt-2 font-['Plus_Jakarta_Sans'] text-2xl font-black text-[#1b5e20]">0%</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span id="tracking-state" class="rounded-full bg-[#e8f5e9] px-3 py-1 text-[11px] font-black uppercase tracking-wider text-[#1b5e20]">
                            Not Tracking
                        </span>
                        <span id="gps-accuracy" class="text-[11px] font-semibold text-[#41493e]">GPS ±—m</span>
                    </div>
                </div>
                <div class="mt-4 h-2 rounded-full bg-[#e8f5e9]">
                    <div id="route-progress-bar" class="h-2 rounded-full bg-[#1b5e20]" style="width: 0%;"></div>
                </div>
                <p id="tracking-note" class="mt-3 text-xs font-semibold text-[#41493e]">
                    Keep this screen open while tracking your route.
                </p>
                <div id="resume-banner" class="mt-4 hidden rounded-xl border border-[#e8f5e9] bg-[#f6fbf6] p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-[#41493e]">Resume</p>
                            <p id="resume-time" class="mt-1 text-sm font-semibold text-[#1b5e20]">Last tracked recently</p>
                        </div>
                        <button
                            id="resume-tracking-button"
                            type="button"
                            class="rounded-full bg-[#1b5e20] px-4 py-2 text-[11px] font-black uppercase tracking-[0.2em] text-white shadow-md transition-all active:scale-95"
                        >
                            Resume
                        </button>
                    </div>
                </div>
            </section>

            <div id="assignment-status" class="mt-6 hidden rounded-xl bg-[#e8f5e9] px-4 py-3 text-sm font-semibold text-[#1b5e20]"></div>

            <div class="mt-8 flex flex-col gap-4">
                <button
                    id="start-assignment-button"
                    type="button"
                    class="flex h-14 w-full items-center justify-center gap-3 rounded-full bg-[#1b5e20] text-sm font-black uppercase tracking-[0.2em] text-white shadow-lg transition-all active:scale-95"
                >
                    <span id="start-assignment-label">Start Route</span>
                    <x-icon name="check-circle" class="h-5 w-5" />
                </button>
                <button
                    id="manual-mark-button"
                    type="button"
                    class="flex h-12 w-full items-center justify-center gap-3 rounded-full border border-[#c0c9bb] bg-white text-xs font-black uppercase tracking-[0.2em] text-[#1b5e20] transition-all active:scale-95"
                >
                    <span id="manual-mark-label">Mark Manually</span>
                    <x-icon name="edit" class="h-4 w-4" />
                </button>
                <button
                    id="complete-assignment-button"
                    type="button"
                    class="flex h-14 w-full items-center justify-center gap-3 rounded-full bg-[#1b5e20] text-sm font-black uppercase tracking-[0.2em] text-white shadow-lg transition-all active:scale-95"
                >
                    Mark Complete
                    <x-icon name="check-circle" class="h-5 w-5" />
                </button>
                <a class="text-center text-sm font-semibold text-[#41493e]" href="{{ route('campaigns.index') }}">
                    Need help? <span class="font-black text-[#1b5e20]">Contact coordinator</span>
                </a>
            </div>
        </section>

        <x-mobile-bottom-nav
            active="routes"
            campaigns-href="{{ route('campaigns.index') }}"
            routes-href="{{ route('routes.index') }}"
            profile-href="{{ route('profile.show') }}"
        />
    </main>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @vite('resources/js/pages/assignment-show.js')
@endpush
