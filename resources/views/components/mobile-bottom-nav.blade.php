@props([
    'active' => 'mark-road',
    'markRoadHref' => '#',
    'logHref' => '#',
    'setupHref' => '#',
])

@php
    $isMarkRoadActive = $active === 'mark-road';
    $isLogActive = $active === 'log';
    $isSetupActive = $active === 'setup';
@endphp

<nav class="fixed bottom-0 z-50 flex h-24 w-full items-center justify-around rounded-t-[2rem] border-t border-[#e8f5e9] bg-white px-4 pb-safe shadow-[0_-8px_32px_rgba(0,0,0,0.08)]">
    <a
        href="{{ $markRoadHref }}"
        data-mobile-nav-link
        class="{{ $isMarkRoadActive ? '-translate-y-2 scale-105 rounded-2xl bg-[#1b5e20] px-8 py-3 text-white shadow-lg shadow-[#1b5e2033]' : 'px-4 py-2 text-[#12121299] hover:text-[#1b5e20]' }} flex flex-col items-center justify-center transition-all active:scale-90"
    >
        <x-icon name="polyline" class="mb-1 h-5 w-5" />
        <span class="text-[10px] {{ $isMarkRoadActive ? 'font-black' : 'font-bold' }} uppercase tracking-widest">Mark Road</span>
    </a>

    <a
        href="{{ $logHref }}"
        data-mobile-nav-link
        class="{{ $isLogActive ? '-translate-y-2 scale-105 rounded-2xl bg-[#1b5e20] px-8 py-3 text-white shadow-lg shadow-[#1b5e2033]' : 'px-4 py-2 text-[#12121299] hover:text-[#1b5e20]' }} flex flex-col items-center justify-center transition-all active:scale-90"
    >
        <x-icon name="history" class="mb-1 h-5 w-5" />
        <span class="text-[10px] {{ $isLogActive ? 'font-black' : 'font-bold' }} uppercase tracking-widest">Log</span>
    </a>

    <a
        href="{{ $setupHref }}"
        data-mobile-nav-link
        class="{{ $isSetupActive ? '-translate-y-2 scale-105 rounded-2xl bg-[#1b5e20] px-8 py-3 text-white shadow-lg shadow-[#1b5e2033]' : 'px-4 py-2 text-[#12121299] hover:text-[#1b5e20]' }} flex flex-col items-center justify-center transition-all active:scale-90"
    >
        <x-icon name="settings" class="mb-1 h-5 w-5" />
        <span class="text-[10px] {{ $isSetupActive ? 'font-black' : 'font-bold' }} uppercase tracking-widest">Setup</span>
    </a>
</nav>
