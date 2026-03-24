@extends('layouts.mobile')

@section('title', 'Leaflet Tracker - Create Campaign')

@section('body-class', "overflow-x-hidden bg-white font-['Inter'] text-[#121212] selection:bg-[#a5d6a7] selection:text-[#1b5e20]")

@push('head')
    <style>
        .tracker-shadow {
            box-shadow: 0 16px 32px 0 rgba(18, 18, 18, 0.06);
        }

        .campaign-bg {
            background:
                radial-gradient(circle at 0% 0%, rgba(165, 214, 167, 0.35), transparent 46%),
                radial-gradient(circle at 100% 20%, rgba(200, 230, 201, 0.45), transparent 44%),
                linear-gradient(180deg, #ffffff 0%, #f8fdf8 100%);
        }
    </style>
@endpush

@section('content')
    <main class="campaign-bg relative min-h-dvh w-full pb-32">
        <header class="fixed top-0 z-50 flex w-full items-center justify-between border-b border-[#e8f5e9] bg-white/90 px-6 py-4 backdrop-blur-md tracker-shadow">
            <div class="flex items-center gap-3">
                <x-icon name="campaign" class="h-6 w-6 text-[#1b5e20]" />
                <h1 class="font-['Plus_Jakarta_Sans'] text-xl font-black uppercase tracking-tight text-[#1b5e20]">Create Campaign</h1>
            </div>
            <a href="{{ route('campaigns.index') }}" class="text-[#12121299] transition-colors duration-200 hover:text-[#1b5e20] active:scale-95" aria-label="Back to campaigns">
                <x-icon name="history" class="h-6 w-6" />
            </a>
        </header>

        <section class="mx-auto flex w-full max-w-2xl flex-col gap-4 px-6 pt-24">
            <div class="rounded-2xl border border-[#e8f5e9] bg-white/95 p-5 shadow-xl backdrop-blur-xl tracker-shadow">
                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#1b5e20]">Campaign Setup</p>
                <h2 class="mt-3 font-['Plus_Jakarta_Sans'] text-2xl font-black leading-tight text-[#1b5e20]">Start a New Campaign</h2>
                <p class="mt-2 text-sm leading-relaxed text-[#444746]">Set campaign details so volunteers can select and start marking routes.</p>
            </div>

            <form class="rounded-2xl border border-[#dcedc8] bg-white p-5 shadow-[0_10px_24px_rgba(27,94,32,0.08)]" action="{{ route('campaigns.store') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label for="campaign-name" class="mb-2 block text-[11px] font-black uppercase tracking-[0.14em] text-[#1b5e20]">Campaign Name</label>
                        <input
                            id="campaign-name"
                            name="name"
                            type="text"
                            placeholder="e.g. Winchester Spring Cleanup"
                            value="{{ old('name') }}"
                            class="h-12 w-full rounded-xl border border-[#dcedc8] bg-[#f8fdf8] px-4 font-semibold text-[#121212] placeholder:text-[#72777599] focus:border-[#1b5e2033] focus:outline-none"
                        >
                        @error('name')
                            <p class="mt-2 text-xs font-semibold text-[#ba1a1a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="campaign-description" class="mb-2 block text-[11px] font-black uppercase tracking-[0.14em] text-[#1b5e20]">Description</label>
                        <textarea
                            id="campaign-description"
                            name="description"
                            rows="4"
                            placeholder="What is this campaign for?"
                            class="w-full rounded-xl border border-[#dcedc8] bg-[#f8fdf8] px-4 py-3 font-semibold text-[#121212] placeholder:text-[#72777599] focus:border-[#1b5e2033] focus:outline-none"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-xs font-semibold text-[#ba1a1a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="campaign-start" class="mb-2 block text-[11px] font-black uppercase tracking-[0.14em] text-[#1b5e20]">Start Date</label>
                            <input
                                id="campaign-start"
                                name="start_date"
                                type="date"
                                value="{{ old('start_date') }}"
                                class="h-12 w-full rounded-xl border border-[#dcedc8] bg-[#f8fdf8] px-4 font-semibold text-[#121212] focus:border-[#1b5e2033] focus:outline-none"
                            >
                            @error('start_date')
                                <p class="mt-2 text-xs font-semibold text-[#ba1a1a]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="campaign-end" class="mb-2 block text-[11px] font-black uppercase tracking-[0.14em] text-[#1b5e20]">End Date</label>
                            <input
                                id="campaign-end"
                                name="end_date"
                                type="date"
                                value="{{ old('end_date') }}"
                                class="h-12 w-full rounded-xl border border-[#dcedc8] bg-[#f8fdf8] px-4 font-semibold text-[#121212] focus:border-[#1b5e2033] focus:outline-none"
                            >
                            @error('end_date')
                                <p class="mt-2 text-xs font-semibold text-[#ba1a1a]">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button
                        type="submit"
                        class="inline-flex h-11 items-center justify-center rounded-xl border-b-2 border-[#2e7d32] bg-[#1b5e20] px-5 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-[#1b5e2033] transition-all active:scale-95"
                    >
                        Save Campaign
                    </button>

                    <a
                        href="{{ route('campaigns.index') }}"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-[#dcedc8] bg-white px-5 text-sm font-black uppercase tracking-wide text-[#1b5e20] transition-all active:scale-95"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </section>

        <x-mobile-bottom-nav
            active="log"
            mark-road-href="{{ route('map-routes.show') }}"
            log-href="{{ route('campaigns.index') }}"
            setup-href="#"
        />
    </main>
@endsection
