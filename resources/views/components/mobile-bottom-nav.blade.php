@props([
    'active' => 'campaigns',
    'campaignsHref' => '#',
    'routesHref' => '#',
    'profileHref' => '#',
])

@php
    $isCampaignsActive = $active === 'campaigns';
    $isRoutesActive = $active === 'routes';
    $isProfileActive = $active === 'profile';
@endphp

<nav class="fixed bottom-0 z-50 flex h-24 w-full items-center justify-around rounded-t-[2rem] border-t border-[#e8f5e9] bg-white px-4 pb-safe shadow-[0_-8px_32px_rgba(0,0,0,0.08)]">
    <a
        href="{{ $campaignsHref }}"
        data-mobile-nav-link
        class="{{ $isCampaignsActive ? '-translate-y-2 scale-105 rounded-2xl bg-[#1b5e20] px-8 py-3 text-white shadow-lg shadow-[#1b5e2033]' : 'px-4 py-2 text-[#12121299] hover:text-[#1b5e20]' }} flex flex-col items-center justify-center transition-all active:scale-90"
    >
        <x-icon name="campaign" class="mb-1 h-5 w-5" />
        <span class="text-[10px] {{ $isCampaignsActive ? 'font-black' : 'font-bold' }} uppercase tracking-widest">Campaigns</span>
    </a>

    <a
        href="{{ $routesHref }}"
        data-mobile-nav-link
        class="{{ $isRoutesActive ? '-translate-y-2 scale-105 rounded-2xl bg-[#1b5e20] px-8 py-3 text-white shadow-lg shadow-[#1b5e2033]' : 'px-4 py-2 text-[#12121299] hover:text-[#1b5e20]' }} flex flex-col items-center justify-center transition-all active:scale-90"
    >
        <x-icon name="polyline" class="mb-1 h-5 w-5" />
        <span class="text-[10px] {{ $isRoutesActive ? 'font-black' : 'font-bold' }} uppercase tracking-widest">My Routes</span>
    </a>

    <a
        href="{{ $profileHref }}"
        data-mobile-nav-link
        class="{{ $isProfileActive ? '-translate-y-2 scale-105 rounded-2xl bg-[#1b5e20] px-8 py-3 text-white shadow-lg shadow-[#1b5e2033]' : 'px-4 py-2 text-[#12121299] hover:text-[#1b5e20]' }} flex flex-col items-center justify-center transition-all active:scale-90"
    >
        <x-icon name="account-circle" class="mb-1 h-5 w-5" />
        <span class="text-[10px] {{ $isProfileActive ? 'font-black' : 'font-bold' }} uppercase tracking-widest">Profile</span>
    </a>
</nav>
