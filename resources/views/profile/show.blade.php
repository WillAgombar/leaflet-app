@extends('layouts.mobile')

@section('title', 'Leaflet Tracker - Profile')

@section('body-class', "overflow-x-hidden bg-[#f6fbf6] font-['Inter'] text-[#171d1a] selection:bg-[#a5d6a7] selection:text-[#1b5e20]")

@push('head')
    <style>
        .tracker-shadow {
            box-shadow: 0 16px 32px 0 rgba(18, 18, 18, 0.06);
        }

        .profile-bg {
            background:
                radial-gradient(circle at 0% 0%, rgba(165, 214, 167, 0.35), transparent 46%),
                radial-gradient(circle at 100% 20%, rgba(200, 230, 201, 0.45), transparent 44%),
                linear-gradient(180deg, #f6fbf6 0%, #f1f8f1 100%);
        }
    </style>
@endpush

@section('content')
    <main class="profile-bg relative min-h-dvh w-full pb-32">
        <header class="fixed top-0 z-50 w-full border-b border-[#e8f5e9] bg-[#f6fbf6]/80 backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-md items-center justify-between px-6">
                <div class="flex items-center gap-3">
                    <x-icon name="account-circle" class="h-6 w-6 text-[#1b5e20]" />
                    <h1 class="font-['Plus_Jakarta_Sans'] text-sm font-black uppercase tracking-wider text-[#1b5e20]">Profile</h1>
                </div>
                <span class="text-[11px] font-black uppercase tracking-[0.2em] text-[#717a6d]">Beta</span>
            </div>
        </header>

        <section class="mx-auto max-w-md px-6 pt-24 space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-[0_10px_28px_rgba(23,29,26,0.06)]">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[#e8f5e9] text-[#1b5e20] font-['Plus_Jakarta_Sans'] text-2xl font-black">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="font-['Plus_Jakarta_Sans'] text-xl font-black text-[#1b5e20]">
                            {{ auth()->user()->name ?? 'Guest User' }}
                        </h2>
                        <p class="text-sm text-[#41493e]">
                            {{ auth()->user()->email ?? 'Sign in to sync your routes.' }}
                        </p>
                    </div>
                </div>
                <div class="mt-5 rounded-xl bg-[#f6fbf6] px-4 py-3 text-xs font-semibold text-[#41493e]">
                    Your account keeps your assigned routes and completed work in sync across devices.
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl bg-[#f0f5f0] p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#717a6d]">Assigned</p>
                    <p class="mt-3 font-['Plus_Jakarta_Sans'] text-2xl font-black text-[#1b5e20]">{{ $assignedCount ?? 0 }}</p>
                </div>
                <div class="rounded-2xl bg-[#f0f5f0] p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#717a6d]">Completed</p>
                    <p class="mt-3 font-['Plus_Jakarta_Sans'] text-2xl font-black text-[#1b5e20]">{{ $completedCount ?? 0 }}</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-[0_10px_28px_rgba(23,29,26,0.06)]">
                <h3 class="font-['Plus_Jakarta_Sans'] text-lg font-black text-[#1b5e20]">Quick Actions</h3>
                <div class="mt-4 flex flex-col gap-3">
                    <a
                        href="{{ route('routes.index') }}"
                        class="inline-flex h-12 items-center justify-center rounded-full border border-[#c0c9bb] bg-white text-xs font-black uppercase tracking-[0.2em] text-[#41493e] transition-all active:scale-95"
                    >
                        View My Routes
                    </a>
                    <a
                        href="{{ route('campaigns.index') }}"
                        class="inline-flex h-12 items-center justify-center rounded-full bg-[#1b5e20] text-xs font-black uppercase tracking-[0.2em] text-white shadow-[0_8px_16px_rgba(27,94,32,0.18)] transition-all active:scale-95"
                    >
                        Browse Campaigns
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex h-12 w-full items-center justify-center rounded-full border border-[#c0c9bb] bg-white text-xs font-black uppercase tracking-[0.2em] text-[#41493e] transition-all active:scale-95"
                        >
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <x-mobile-bottom-nav
            active="profile"
            campaigns-href="{{ route('campaigns.index') }}"
            routes-href="{{ route('routes.index') }}"
            profile-href="{{ route('profile.show') }}"
        />
    </main>
@endsection
