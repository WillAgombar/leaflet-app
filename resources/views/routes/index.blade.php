@extends('layouts.mobile')

@section('title', 'Leaflet Tracker - My Routes')

@section('body-class', "overflow-x-hidden bg-[#f6fbf6] font-['Inter'] text-[#171d1a] selection:bg-[#a5d6a7] selection:text-[#1b5e20]")

@push('head')
    <style>
        .tracker-shadow {
            box-shadow: 0 16px 32px 0 rgba(18, 18, 18, 0.06);
        }

        .routes-bg {
            background:
                radial-gradient(circle at 0% 0%, rgba(165, 214, 167, 0.35), transparent 46%),
                radial-gradient(circle at 100% 20%, rgba(200, 230, 201, 0.45), transparent 44%),
                linear-gradient(180deg, #f6fbf6 0%, #f1f8f1 100%);
        }
    </style>
@endpush

@section('content')
    <main class="routes-bg relative min-h-dvh w-full pb-32">
        <header class="fixed top-0 z-50 w-full border-b border-[#e8f5e9] bg-[#f6fbf6]/80 backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-md items-center justify-between px-6">
                <div class="flex items-center gap-3">
                    <x-icon name="polyline" class="h-6 w-6 text-[#1b5e20]" />
                    <h1 class="font-['Plus_Jakarta_Sans'] text-sm font-black uppercase tracking-wider text-[#1b5e20]">My Routes</h1>
                </div>
            </div>
        </header>

        <section class="mx-auto max-w-md px-6 pt-24 space-y-8">
            <div class="rounded-2xl bg-white p-6 shadow-[0_10px_28px_rgba(23,29,26,0.06)]">
                <h2 class="font-['Plus_Jakarta_Sans'] text-2xl font-black text-[#1b5e20]">Ready to head out?</h2>
                <p class="mt-2 text-sm text-[#41493e]">Choose a campaign to complete an assigned route or add your own.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a
                        href="{{ route('campaigns.index') }}"
                        class="inline-flex h-11 items-center justify-center rounded-full bg-[#1b5e20] px-5 text-xs font-black uppercase tracking-[0.2em] text-white shadow-[0_8px_16px_rgba(27,94,32,0.18)] transition-all active:scale-95"
                    >
                        View Campaigns
                    </a>
                </div>
            </div>

            <div>
                <h3 class="font-['Plus_Jakarta_Sans'] text-lg font-black text-[#1b5e20]">Assigned Routes</h3>
                <p class="mt-1 text-sm text-[#41493e]">These are tied to campaigns and ready for you to work on.</p>
            </div>

            <div class="space-y-4">
                @forelse(($assignedRoutes ?? []) as $assignment)
                    <article class="rounded-2xl bg-white p-5 shadow-[0_8px_24px_rgba(23,29,26,0.06)]">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#717a6d]">Campaign</p>
                                <h4 class="mt-2 font-['Plus_Jakarta_Sans'] text-lg font-black text-[#171d1a]">
                                    {{ $assignment->campaignRoute->campaign->name ?? 'Campaign' }}
                                </h4>
                                <p class="mt-1 text-sm text-[#41493e]">
                                    {{ $assignment->campaignRoute->name ?? 'Route' }}
                                </p>
                            </div>
                            <span class="rounded-full bg-[#e8f5e9] px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-[#1b5e20]">
                                {{ $assignment->status === 'in_progress' ? 'In Progress' : 'Assigned' }}
                            </span>
                        </div>

                        <div class="mt-4 flex">
                            <a
                                href="{{ route('assignments.show', $assignment) }}"
                                class="flex-1 rounded-full bg-[#1b5e20] px-4 py-3 text-center text-xs font-black uppercase tracking-[0.2em] text-white shadow-[0_8px_16px_rgba(27,94,32,0.18)] transition-all active:scale-95"
                            >
                                Open Route
                            </a>
                        </div>
                    </article>
                @empty
                    <article class="rounded-2xl border border-dashed border-[#c0c9bb] bg-white/70 p-6 text-center">
                        <p class="text-sm font-semibold text-[#41493e]">No assigned routes yet.</p>
                    </article>
                @endforelse
            </div>

            <div>
                <h3 class="font-['Plus_Jakarta_Sans'] text-lg font-black text-[#1b5e20]">Completed</h3>
                <p class="mt-1 text-sm text-[#41493e]">Recently finished routes linked to campaigns.</p>
            </div>

            <div class="space-y-4">
                @forelse(($completedRoutes ?? []) as $assignment)
                    <article class="rounded-2xl bg-white p-5 shadow-[0_8px_24px_rgba(23,29,26,0.06)]">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#717a6d]">Campaign</p>
                                <h4 class="mt-2 font-['Plus_Jakarta_Sans'] text-lg font-black text-[#171d1a]">
                                    {{ $assignment->campaignRoute->campaign->name ?? 'Campaign' }}
                                </h4>
                                <p class="mt-1 text-sm text-[#41493e]">
                                    {{ $assignment->campaignRoute->name ?? 'Route' }}
                                </p>
                            </div>
                            <span class="rounded-full bg-[#f1f8f1] px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-[#2e7d32]">
                                Completed
                            </span>
                        </div>
                        <p class="mt-3 text-xs font-semibold text-[#717a6d]">
                            Completed {{ $assignment->completed_at?->diffForHumans() ?? 'recently' }}
                        </p>
                    </article>
                @empty
                    <article class="rounded-2xl border border-dashed border-[#c0c9bb] bg-white/70 p-6 text-center">
                        <p class="text-sm font-semibold text-[#41493e]">No completed routes yet.</p>
                    </article>
                @endforelse
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
