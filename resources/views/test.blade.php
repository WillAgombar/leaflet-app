@extends('layouts.mobile')

@section('title', 'Leaflet Tracker - Professional Navigator')

@section('body-class', "overflow-hidden bg-white font-['Inter'] text-[#121212] selection:bg-[#a5d6a7] selection:text-[#1b5e20]")

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">

    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .tracker-shadow {
            box-shadow: 0 16px 32px 0 rgba(18, 18, 18, 0.06);
        }

        .map-gradient-overlay {
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.88) 0%, rgba(255, 255, 255, 0) 16%, rgba(255, 255, 255, 0) 84%, rgba(255, 255, 255, 0.96) 100%);
        }

        .pb-safe {
            padding-bottom: max(1rem, env(safe-area-inset-bottom));
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
                <span class="material-symbols-outlined text-2xl text-[#1b5e20]">map</span>
                <h1 class="font-['Plus_Jakarta_Sans'] text-xl font-black uppercase tracking-tight text-[#1b5e20]">Leaflet Tracker</h1>
            </div>
            <button type="button" class="text-[#12121299] transition-colors duration-200 hover:text-[#1b5e20] active:scale-95" aria-label="Account">
                <span class="material-symbols-outlined">account_circle</span>
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
                        <span class="material-symbols-outlined pointer-events-none absolute right-4 top-4 text-[#1b5e2066]">edit</span>
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
                            <span class="material-symbols-outlined">undo</span>
                            Undo Last Move
                        </button>

                        <button
                            id="reset-button"
                            type="button"
                            class="flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-[#e8f5e9] bg-white font-bold text-[#ba1a1a] shadow-sm transition-all hover:bg-red-50 active:scale-95"
                            aria-label="Reset route"
                        >
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>

                    <button
                        id="save-route-button"
                        type="button"
                        class="flex h-20 w-full items-center justify-center gap-3 rounded-2xl border-b-4 border-[#2e7d32] bg-[#1b5e20] font-['Plus_Jakarta_Sans'] text-xl font-black uppercase text-white shadow-2xl shadow-[#1b5e204d] transition-all active:scale-[0.98]"
                    >
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 1">check_circle</span>
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
                    <span class="material-symbols-outlined">my_location</span>
                </button>
                <button
                    id="layers-button"
                    type="button"
                    class="flex h-14 w-14 items-center justify-center rounded-full border border-[#e8f5e9] bg-white text-[#1b5e20] shadow-xl transition-transform active:scale-90"
                    aria-label="Switch map layer"
                >
                    <span class="material-symbols-outlined">layers</span>
                </button>
            </div>
        </section>

        <nav class="fixed bottom-0 z-50 flex h-24 w-full items-center justify-around rounded-t-[2rem] border-t border-[#e8f5e9] bg-white px-4 pb-safe shadow-[0_-8px_32px_rgba(0,0,0,0.08)]">
            <a href="#" class="-translate-y-2 flex scale-105 flex-col items-center justify-center rounded-2xl bg-[#1b5e20] px-8 py-3 text-white shadow-lg shadow-[#1b5e2033] transition-all">
                <span class="material-symbols-outlined mb-1" style="font-variation-settings: 'FILL' 1">polyline</span>
                <span class="text-[10px] font-black uppercase tracking-widest">Mark Road</span>
            </a>
            <a href="#" class="flex flex-col items-center justify-center px-4 py-2 text-[#12121299] transition-all hover:text-[#1b5e20] active:scale-90">
                <span class="material-symbols-outlined mb-1">history</span>
                <span class="text-[10px] font-bold uppercase tracking-widest">Log</span>
            </a>
            <a href="#" class="flex flex-col items-center justify-center px-4 py-2 text-[#12121299] transition-all hover:text-[#1b5e20] active:scale-90">
                <span class="material-symbols-outlined mb-1">settings</span>
                <span class="text-[10px] font-bold uppercase tracking-widest">Setup</span>
            </a>
        </nav>
    </main>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @vite('resources/js/pages/leaflet-tracker.js')
@endpush
