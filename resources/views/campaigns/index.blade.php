@extends('layouts.mobile')

@section('title', 'Leaflet Tracker - Campaigns')

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
                <h1 class="font-['Plus_Jakarta_Sans'] text-xl font-black uppercase tracking-tight text-[#1b5e20]">Campaigns</h1>
            </div>
            <button type="button" class="text-[#12121299] transition-colors duration-200 hover:text-[#1b5e20] active:scale-95" aria-label="Search campaigns">
                <x-icon name="search" class="h-6 w-6" />
            </button>
        </header>

        <section class="mx-auto flex w-full max-w-2xl flex-col gap-4 px-6 pt-24">
            <div class="rounded-2xl border border-[#e8f5e9] bg-white/95 p-5 shadow-xl backdrop-blur-xl tracker-shadow">
                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#1b5e20]">Available Now</p>
                <h2 class="mt-3 font-['Plus_Jakarta_Sans'] text-2xl font-black leading-tight text-[#1b5e20]">Choose a Campaign</h2>
                <p class="mt-2 text-sm leading-relaxed text-[#444746]">Select a campaign to start marking routes for your assigned area.</p>
                @if($isAdmin ?? false)
                    <a
                        href="{{ route('campaigns.create') }}"
                        class="mt-4 inline-flex h-11 items-center justify-center rounded-xl border-b-2 border-[#2e7d32] bg-[#1b5e20] px-5 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-[#1b5e2033] transition-all active:scale-95"
                    >
                        Create Campaign
                    </a>
                @endif
            </div>

            <div class="space-y-4">
                @forelse(($campaigns ?? []) as $campaign)
                    <article class="rounded-2xl border border-[#dcedc8] bg-white p-5 shadow-[0_10px_24px_rgba(27,94,32,0.08)]">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate font-['Plus_Jakarta_Sans'] text-xl font-extrabold text-[#1b5e20]">{{ $campaign['name'] ?? 'Untitled Campaign' }}</h3>
                                <p class="mt-1 text-sm text-[#444746]">{{ $campaign['location'] ?? 'Location pending' }}</p>
                            </div>
                            <span class="rounded-full bg-[#e8f5e9] px-3 py-1 text-[11px] font-black uppercase tracking-wider text-[#1b5e20]">
                                {{ $campaign['status'] ?? 'Active' }}
                            </span>
                        </div>

                        <p class="mt-4 text-sm leading-relaxed text-[#444746]">{{ $campaign['description'] ?? 'No description provided yet.' }}</p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-full border border-[#dcedc8] bg-[#f8fdf8] px-3 py-1 text-xs font-bold text-[#2e7d32]">
                                {{ $campaign->map_routes_count }} routes
                            </span>
                            <span class="rounded-full border border-[#dcedc8] bg-[#f8fdf8] px-3 py-1 text-xs font-bold text-[#2e7d32]">
                                {{ $campaign->volunteers_count }} volunteers
                            </span>
                        </div>

                        @if($isAdmin ?? false)
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a
                                    href="{{ route('campaigns.map.templates', $campaign) }}"
                                    class="inline-flex h-10 items-center justify-center rounded-xl border-2 border-[#e8f5e9] bg-white px-4 text-xs font-black uppercase tracking-wide text-[#1b5e20] transition-all active:scale-95"
                                >
                                    Template Mode
                                </a>
                                <a
                                    href="{{ route('campaigns.assignments.index', $campaign) }}"
                                    class="inline-flex h-10 items-center justify-center rounded-xl border-2 border-[#e8f5e9] bg-white px-4 text-xs font-black uppercase tracking-wide text-[#1b5e20] transition-all active:scale-95"
                                >
                                    Assignments
                                </a>
                            </div>
                        @endif

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a
                                href="{{ route('campaigns.show', $campaign) }}"
                                class="inline-flex h-11 items-center justify-center rounded-xl border-b-2 border-[#2e7d32] bg-[#1b5e20] px-5 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-[#1b5e2033] transition-all active:scale-95"
                            >
                                Select Campaign
                            </a>
                            @if($isAdmin ?? false)
                                <a
                                    href="{{ route('campaigns.create', ['duplicate_from' => $campaign->id]) }}"
                                    class="inline-flex h-11 items-center justify-center rounded-xl border-2 border-[#1b5e20] bg-white px-5 text-sm font-black uppercase tracking-wide text-[#1b5e20] shadow-sm transition-all active:scale-95"
                                >
                                    Duplicate
                                </a>
                                <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="inline-flex h-11 items-center justify-center rounded-xl border-b-2 border-[#b71c1c] bg-[#d32f2f] px-5 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-[#d32f2f33] transition-all active:scale-95"
                                        onclick="return confirm('Are you sure you want to delete this campaign?');"
                                    >
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <article class="rounded-2xl border border-dashed border-[#c4c6cf] bg-white/80 p-8 text-center shadow-sm">
                        <x-icon name="group-off" class="mx-auto h-10 w-10 text-[#1b5e20]" />
                        <h3 class="mt-3 font-['Plus_Jakarta_Sans'] text-xl font-black text-[#1b5e20]">No Campaigns Yet</h3>
                        <p class="mt-2 text-sm text-[#444746]">Campaigns will appear here once they are published.</p>
                    </article>
                @endforelse
            </div>
        </section>

        <x-mobile-bottom-nav
            active="campaigns"
            campaigns-href="{{ route('campaigns.index') }}"
            routes-href="{{ route('routes.index') }}"
            profile-href="{{ route('profile.show') }}"
        />
    </main>
@endsection
