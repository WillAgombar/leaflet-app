@extends('layouts.mobile')

@section('title', 'Leaflet Tracker - ' . $campaign->name)

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
            <div class="mx-auto flex h-16 w-full max-w-md items-center gap-3 px-6">
                <a href="{{ route('campaigns.index') }}" class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#1b5e20] shadow-sm transition-all active:scale-95">
                    <x-icon name="arrow-left" class="h-5 w-5" />
                </a>
                <h1 class="font-['Plus_Jakarta_Sans'] text-sm font-black uppercase tracking-wider text-[#1b5e20] truncate">{{ $campaign->name }}</h1>
            </div>
        </header>

        <section class="mx-auto max-w-md px-6 pt-24 space-y-8">
            <div class="rounded-2xl bg-white p-6 shadow-[0_10px_28px_rgba(23,29,26,0.06)]">
                <h2 class="font-['Plus_Jakarta_Sans'] text-2xl font-black text-[#1b5e20]">{{ $campaign->name }}</h2>
                <p class="mt-2 text-sm text-[#41493e]">{{ $campaign->description ?? 'No description available.' }}</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a
                        href="{{ route('campaigns.map.show', $campaign) }}"
                        class="inline-flex h-11 items-center justify-center rounded-full border border-[#1b5e20] bg-transparent px-5 text-xs font-black uppercase tracking-[0.2em] text-[#1b5e20] transition-all active:scale-95 hover:bg-[#e8f5e9]"
                    >
                        View Map
                    </a>
                </div>
                
                @if(session('success'))
                    <div class="mt-4 rounded-xl bg-[#e8f5e9] p-4 text-sm font-semibold text-[#1b5e20]">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mt-4 rounded-xl bg-[#ffebee] p-4 text-sm font-semibold text-[#c62828]">
                        {{ session('error') }}
                    </div>
                @endif
            </div>

            <div>
                <h3 class="font-['Plus_Jakarta_Sans'] text-lg font-black text-[#1b5e20]">Campaign Routes</h3>
                <p class="mt-1 text-sm text-[#41493e]">Select an available route to volunteer for it.</p>
            </div>

            <div class="space-y-4">
                @forelse($campaign->campaignRoutes as $route)
                    <article class="rounded-2xl bg-white p-5 shadow-[0_8px_24px_rgba(23,29,26,0.06)]">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="font-['Plus_Jakarta_Sans'] text-lg font-black text-[#171d1a]">
                                    {{ $route->name ?? 'Route' }}
                                </h4>
                            </div>
                            @if($route->assignment)
                                <span class="rounded-full bg-[#f1f8f1] px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-[#2e7d32]">
                                    @if($route->assignment->user_id === auth()->id())
                                        Assigned to You
                                    @else
                                        Assigned ({{ $route->assignment->user->name ?? 'User' }})
                                    @endif
                                </span>
                            @else
                                <span class="rounded-full bg-[#fff8e1] px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-[#f57f17]">
                                    Available
                                </span>
                            @endif
                        </div>

                        <div class="mt-4 flex">
                            @if($route->assignment)
                                @if($route->assignment->user_id === auth()->id())
                                    <a
                                        href="{{ route('assignments.show', $route->assignment) }}"
                                        class="flex-1 rounded-full border border-[#1b5e20] px-4 py-3 text-center text-xs font-black uppercase tracking-[0.2em] text-[#1b5e20] transition-all active:scale-95 hover:bg-[#e8f5e9]"
                                    >
                                        Open Assignment
                                    </a>
                                @else
                                    <button
                                        disabled
                                        class="flex-1 rounded-full bg-[#e0e0e0] px-4 py-3 text-center text-xs font-black uppercase tracking-[0.2em] text-[#9e9e9e] cursor-not-allowed"
                                    >
                                        Already Assigned
                                    </button>
                                @endif
                            @else
                                <form action="{{ route('campaigns.routes.volunteer', [$campaign, $route]) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="w-full rounded-full bg-[#1b5e20] px-4 py-3 text-center text-xs font-black uppercase tracking-[0.2em] text-white shadow-[0_8px_16px_rgba(27,94,32,0.18)] transition-all active:scale-95"
                                        onclick="return confirm('Are you sure you want to volunteer for this route?');"
                                    >
                                        Volunteer
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <article class="rounded-2xl border border-dashed border-[#c0c9bb] bg-white/70 p-6 text-center">
                        <p class="text-sm font-semibold text-[#41493e]">No routes available for this campaign yet.</p>
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
