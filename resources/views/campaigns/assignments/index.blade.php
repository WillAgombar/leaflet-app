@extends('layouts.mobile')

@section('title', 'Leaflet Tracker - Assignments')

@section('body-class', "overflow-x-hidden bg-[#f6fbf6] font-['Inter'] text-[#171d1a] selection:bg-[#a5d6a7] selection:text-[#1b5e20]")

@push('head')
    <style>
        .tracker-shadow {
            box-shadow: 0 16px 32px 0 rgba(18, 18, 18, 0.06);
        }

        .assignment-bg {
            background:
                radial-gradient(circle at 0% 0%, rgba(165, 214, 167, 0.35), transparent 46%),
                radial-gradient(circle at 100% 20%, rgba(200, 230, 201, 0.45), transparent 44%),
                linear-gradient(180deg, #f6fbf6 0%, #f1f8f1 100%);
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
    </style>
@endpush

@section('content')
    <main class="assignment-bg relative min-h-dvh w-full pb-32">
        <header class="fixed top-0 z-50 w-full border-b border-[#e8f5e9] bg-[#f6fbf6]/80 backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-md items-center justify-between px-6">
                <div class="flex items-center gap-3">
                    <x-icon name="campaign" class="h-6 w-6 text-[#1b5e20]" />
                    <h1 class="font-['Plus_Jakarta_Sans'] text-sm font-black uppercase tracking-wider text-[#1b5e20]">Assignments</h1>
                </div>
                <a href="{{ route('campaigns.index') }}" class="flex h-10 w-10 items-center justify-center rounded-full transition-colors hover:bg-[#dfe4df]/50">
                    <x-icon name="search" class="h-5 w-5 text-[#41493e]" />
                </a>
            </div>
        </header>

        <section class="mx-auto max-w-md px-6 pt-24 space-y-8">
            <section class="relative overflow-hidden rounded-2xl bg-[#1b5e20] p-8 text-white shadow-[0_24px_40px_rgba(18,18,18,0.12)]">
                <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-[#a5d6a7]/20 blur-3xl"></div>
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <span class="inline-flex rounded-full bg-[#a5d6a7] px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-[#0b3d14]">Active</span>
                            <h2 class="mt-3 font-['Plus_Jakarta_Sans'] text-2xl font-black tracking-tight">{{ $campaign->name }}</h2>
                            <p class="mt-2 text-sm text-[#c8e6c9]">Assign templates and track volunteer progress.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 border-t border-white/10 pt-6">
                        <div>
                            <p class="text-[10px] uppercase font-black tracking-[0.2em] opacity-70">Routes</p>
                            <p class="mt-2 text-2xl font-black">{{ $totalRoutes }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-black tracking-[0.2em] opacity-70">Assigned</p>
                            <p class="mt-2 text-2xl font-black">{{ $assignedRoutes + $inProgressRoutes }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-black tracking-[0.2em] opacity-70">Done</p>
                            <p class="mt-2 text-2xl font-black">{{ $completedRoutes }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('campaigns.map.templates', $campaign) }}"
                    class="flex-1 rounded-full bg-[#1b5e20] px-5 py-3 text-center text-xs font-black uppercase tracking-[0.2em] text-white shadow-[0_8px_16px_rgba(27,94,32,0.18)] transition-all active:scale-95"
                >
                    Create Template
                </a>
                <a
                    href="{{ route('campaigns.map.show', $campaign) }}"
                    class="flex-1 rounded-full border border-[#c0c9bb] bg-white px-5 py-3 text-center text-xs font-black uppercase tracking-[0.2em] text-[#41493e] transition-all active:scale-95"
                >
                    View Live Map
                </a>
            </div>

            @if (session('success'))
                <div class="rounded-xl bg-[#e8f5e9] px-4 py-3 text-sm font-semibold text-[#1b5e20]">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl bg-[#ffdad6] px-4 py-3 text-sm font-semibold text-[#93000a]">
                    {{ $errors->first() }}
                </div>
            @endif

            <nav class="flex gap-2 overflow-x-auto pb-2 no-scrollbar">
                <button data-filter="all" class="bg-[#1b5e20] text-white px-6 py-2.5 rounded-full text-xs font-black uppercase tracking-wider whitespace-nowrap">All</button>
                <button data-filter="unassigned" class="bg-white text-[#41493e] px-6 py-2.5 rounded-full text-xs font-black uppercase tracking-wider whitespace-nowrap border border-[#c0c9bb]">Unassigned</button>
                <button data-filter="assigned" class="bg-white text-[#41493e] px-6 py-2.5 rounded-full text-xs font-black uppercase tracking-wider whitespace-nowrap border border-[#c0c9bb]">Assigned</button>
                <button data-filter="in_progress" class="bg-white text-[#41493e] px-6 py-2.5 rounded-full text-xs font-black uppercase tracking-wider whitespace-nowrap border border-[#c0c9bb]">In Progress</button>
                <button data-filter="completed" class="bg-white text-[#41493e] px-6 py-2.5 rounded-full text-xs font-black uppercase tracking-wider whitespace-nowrap border border-[#c0c9bb]">Completed</button>
            </nav>

            <section class="space-y-4">
                @forelse($campaignRoutes as $campaignRoute)
                    @php
                        $assignment = $campaignRoute->assignment;
                        $status = $assignment->status ?? 'unassigned';
                        $statusLabel = match ($status) {
                            'assigned' => 'Assigned',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                            default => 'Unassigned',
                        };
                        $statusDot = match ($status) {
                            'assigned' => 'bg-[#1b6d24]',
                            'in_progress' => 'bg-[#0d47a1]',
                            'completed' => 'bg-[#005312]',
                            default => 'bg-[#717a6d]',
                        };
                        $assignedLabel = $assignment?->assigned_at?->diffForHumans() ?? 'Assigned N/A';
                        $assigneeName = $assignment?->user?->name ?? 'None';
                    @endphp
                    <article
                        data-route-card
                        data-status="{{ $status }}"
                        class="rounded-2xl bg-white p-6 shadow-[0_8px_28px_rgba(23,29,26,0.06)]"
                    >
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <h3 class="font-['Plus_Jakarta_Sans'] text-lg font-black text-[#171d1a]">{{ $campaignRoute->name }}</h3>
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full {{ $statusDot }}"></span>
                                    <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-[#41493e]">{{ $statusLabel }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#717a6d]">Assignee</p>
                                <p class="text-sm font-semibold text-[#1b5e20]">{{ $assigneeName }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-2 text-xs text-[#717a6d]">
                            <x-icon name="history" class="h-4 w-4" />
                            <span>{{ $assignedLabel }}</span>
                        </div>

                        <form method="POST" action="{{ route('campaigns.assignments.store', [$campaign, $campaignRoute]) }}" class="mt-4">
                            @csrf
                            <label for="assignee-{{ $campaignRoute->id }}" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-[#41493e]">
                                Assign to
                            </label>
                            <select
                                id="assignee-{{ $campaignRoute->id }}"
                                name="user_id"
                                class="h-12 w-full rounded-full border border-[#dfe4df] bg-[#f6fbf6] px-5 text-sm font-semibold text-[#171d1a] focus:border-[#1b5e20] focus:ring-0"
                            >
                                <option value="">Select volunteer</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('user_id', $assignment->user_id ?? '') == $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="mt-4 flex gap-3">
                                <button
                                    type="submit"
                                    class="flex-1 h-12 rounded-full bg-[#1b5e20] text-white text-sm font-black uppercase tracking-[0.2em] shadow-[0_8px_16px_rgba(0,69,13,0.15)] transition-all active:scale-95"
                                >
                                    {{ $assignment ? 'Reassign' : 'Assign' }}
                                </button>
                                <a
                                    href="{{ $assignment ? route('assignments.show', $assignment) : '#' }}"
                                    class="flex-1 h-12 rounded-full border border-[#c0c9bb] text-center text-sm font-black uppercase tracking-[0.2em] text-[#171d1a] flex items-center justify-center transition-all active:scale-95 {{ $assignment ? '' : 'pointer-events-none opacity-50' }}"
                                >
                                    View Route
                                </a>
                            </div>
                        </form>
                    </article>
                @empty
                    <article class="rounded-2xl border border-dashed border-[#c0c9bb] bg-white/70 p-8 text-center shadow-sm">
                        <x-icon name="group-off" class="mx-auto h-10 w-10 text-[#1b5e20]" />
                        <h3 class="mt-3 font-['Plus_Jakarta_Sans'] text-xl font-black text-[#1b5e20]">No Route Templates Yet</h3>
                        <p class="mt-2 text-sm text-[#444746]">Create a route template to assign it to volunteers.</p>
                    </article>
                @endforelse
            </section>
        </section>

        <x-mobile-bottom-nav
            active="campaigns"
            campaigns-href="{{ route('campaigns.index') }}"
            routes-href="{{ route('routes.index') }}"
            profile-href="{{ route('profile.show') }}"
        />
    </main>
@endsection

@push('scripts')
    <script>
        const filterButtons = document.querySelectorAll('[data-filter]');
        const routeCards = document.querySelectorAll('[data-route-card]');

        const setActiveFilter = (activeButton) => {
            filterButtons.forEach((button) => {
                if (button === activeButton) {
                    button.classList.add('bg-[#1b5e20]', 'text-white');
                    button.classList.remove('bg-white', 'text-[#41493e]');

                    return;
                }

                button.classList.remove('bg-[#1b5e20]', 'text-white');
                button.classList.add('bg-white', 'text-[#41493e]');
            });
        };

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.dataset.filter;
                setActiveFilter(button);

                routeCards.forEach((card) => {
                    if (filter === 'all') {
                        card.classList.remove('hidden');

                        return;
                    }

                    card.classList.toggle('hidden', card.dataset.status !== filter);
                });
            });
        });
    </script>
@endpush
