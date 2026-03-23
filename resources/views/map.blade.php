@extends('layouts.mobile')

@section('title', 'Leaflet Tracker - Professional Navigator')

@section('body-class', "overflow-hidden bg-white font-['Inter'] text-[#121212] selection:bg-[#a5d6a7] selection:text-[#1b5e20]")

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="preconnect" href="https://a.tile.openstreetmap.org" crossorigin>
    <link rel="preconnect" href="https://b.tile.openstreetmap.org" crossorigin>
    <link rel="preconnect" href="https://c.tile.openstreetmap.org" crossorigin>
    <link rel="preconnect" href="https://a.tile.openstreetmap.fr" crossorigin>
    <link rel="preconnect" href="https://b.tile.openstreetmap.fr" crossorigin>
    <link rel="preconnect" href="https://c.tile.openstreetmap.fr" crossorigin>

    <style>
        .tracker-shadow {
            box-shadow: 0 16px 32px 0 rgba(18, 18, 18, 0.06);
        }

        .map-gradient-overlay {
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.88) 0%, rgba(255, 255, 255, 0) 16%, rgba(255, 255, 255, 0) 84%, rgba(255, 255, 255, 0.96) 100%);
        }

        .leaflet-container {
            font-family: 'Inter', sans-serif;
            background: #f1f8f1;
        }

        .leaflet-control-attribution {
            font-size: 10px;
        }

        .route-label {
            background: transparent;
            border: 0;
            pointer-events: none;
        }

        .route-label-chip {
            display: inline-flex;
            max-width: min(72vw, 15rem);
            align-items: center;
            gap: 0.5rem;
            border: 2px solid #fff;
            border-radius: 9999px;
            background: rgba(27, 94, 32, 0.95);
            padding: 0.42rem 0.62rem;
            box-shadow: 0 10px 22px rgba(18, 18, 18, 0.2);
            color: #fff;
        }

        .route-label-chip__dot {
            height: 0.62rem;
            width: 0.62rem;
            flex-shrink: 0;
            border-radius: 9999px;
            background: #a5d6a7;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.28);
        }

        .route-label-chip__meta {
            min-width: 0;
            display: flex;
            flex-direction: column;
            line-height: 1;
        }

        .route-label-chip__state {
            margin-bottom: 0.22rem;
            font-size: 0.58rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            opacity: 0.9;
        }

        .route-label-chip__name {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        #tracker-map {
            width: 100%;
            height: 100%;
        }
    </style>
@endpush

@section('content')
    <main
        data-leaflet-tracker
        data-routes='@json($mapRoutes)'
        data-save-url="{{ route('map-routes.store') }}"
        class="relative h-dvh min-h-[44rem] w-full overflow-hidden bg-white"
    >
        <header
            class="fixed top-0 z-50 flex w-full items-center justify-between border-b border-[#e8f5e9] bg-white/90 px-6 py-4 backdrop-blur-md tracker-shadow"
        >
            <div class="flex items-center gap-3">
                <x-icon name="map" class="h-6 w-6 text-[#1b5e20]" />
                <h1 class="font-['Plus_Jakarta_Sans'] text-xl font-black uppercase tracking-tight text-[#1b5e20]">Leaflet Tracker</h1>
            </div>
            <button type="button" class="text-[#12121299] transition-colors duration-200 hover:text-[#1b5e20] active:scale-95" aria-label="Account">
                <x-icon name="account-circle" class="h-6 w-6" />
            </button>
        </header>

        <section class="relative h-full w-full overflow-hidden pt-20 pb-24">
            <div id="tracker-map" class="absolute inset-0 z-0"></div>
            <div class="pointer-events-none absolute inset-0 z-10 map-gradient-overlay"></div>

            <div class="relative z-30 mx-auto flex max-w-lg flex-col gap-4 px-6 pt-4 md:ml-6 md:mr-0">
                <div class="rounded-2xl border border-[#e8f5e9] bg-white/95 p-6 shadow-xl backdrop-blur-xl tracker-shadow">
                    <label for="name-input" class="mb-3 ml-1 block text-[11px] font-black uppercase tracking-[0.2em] text-[#1b5e20]">
                        Enter your name
                    </label>
                    <div class="group relative">
                        <input
                            id="name-input"
                            type="text"
                            autocomplete="name"
                            placeholder="e.g. Michael Scott"
                            class="h-14 w-full rounded-xl border-2 border-transparent bg-[#f1f8f1] px-5 font-bold text-[#121212] placeholder:text-[#72777599] transition-all focus:border-[#1b5e2033] focus:ring-0"
                        >
                        <x-icon name="edit" class="pointer-events-none absolute right-4 top-4 h-6 w-6 text-[#1b5e2066]" />
                    </div>
                </div>

                <div id="tracker-status" class="hidden rounded-xl bg-[#e8f5e9] px-4 py-3 text-sm font-semibold text-[#1b5e20]"></div>
            </div>

            <div class="absolute bottom-32 left-0 z-40 flex w-full flex-col items-center gap-4 px-6">
                <div class="flex w-full max-w-md flex-col gap-4">
                    <div class="flex gap-4">
                        <button
                            id="undo-button"
                            type="button"
                            class="flex h-16 flex-1 items-center justify-center gap-2 rounded-2xl border-2 border-[#e8f5e9] bg-white text-sm font-black uppercase text-[#121212] shadow-sm transition-all hover:bg-[#f1f8f1] active:scale-95"
                        >
                            <x-icon name="undo" class="h-5 w-5" />
                            Undo Last Move
                        </button>

                        <button
                            id="reset-button"
                            type="button"
                            class="flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-[#e8f5e9] bg-white font-bold text-[#ba1a1a] shadow-sm transition-all hover:bg-red-50 active:scale-95"
                            aria-label="Reset route"
                        >
                            <x-icon name="delete" class="h-5 w-5" />
                        </button>
                    </div>

                    <button
                        id="save-route-button"
                        type="button"
                        class="flex h-20 w-full items-center justify-center gap-3 rounded-2xl border-b-4 border-[#2e7d32] bg-[#1b5e20] font-['Plus_Jakarta_Sans'] text-xl font-black uppercase text-white shadow-2xl shadow-[#1b5e204d] transition-all active:scale-[0.98]"
                    >
                        <x-icon name="check-circle" class="h-7 w-7" />
                        <span id="save-route-label">Finish and Save Route</span>
                    </button>
                </div>
            </div>

            <div class="absolute bottom-64 right-6 z-40 flex flex-col gap-3">
                <button
                    id="locate-button"
                    type="button"
                    class="flex h-14 w-14 items-center justify-center rounded-full border border-[#e8f5e9] bg-white text-[#1b5e20] shadow-xl transition-transform active:scale-90"
                    aria-label="Find my location"
                >
                    <x-icon name="my-location" class="h-6 w-6" />
                </button>
                <button
                    id="layers-button"
                    type="button"
                    class="flex h-14 w-14 items-center justify-center rounded-full border border-[#e8f5e9] bg-white text-[#1b5e20] shadow-xl transition-transform active:scale-90"
                    aria-label="Switch map layer"
                >
                    <x-icon name="layers" class="h-6 w-6" />
                </button>
            </div>
        </section>

        <x-mobile-bottom-nav
            active="mark-road"
            mark-road-href="{{ route('map-routes.show') }}"
            log-href="{{ route('campaigns.index') }}"
            setup-href="#"
        />
    </main>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @vite('resources/js/pages/leaflet-tracker.js')
@endpush
