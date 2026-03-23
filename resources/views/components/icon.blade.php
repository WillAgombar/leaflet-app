@props([
    'name',
])

<svg
    {{ $attributes->merge(['class' => 'inline-block h-5 w-5 shrink-0 align-middle']) }}
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    stroke="currentColor"
    stroke-width="1.9"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch($name)
        @case('map')
            <path d="M9 4.5 3 6.75v12L9 16.5l6 2.25 6-2.25v-12L15 6.75 9 4.5Z" />
            <path d="M9 4.5v12m6-9.75v12" />
            @break

        @case('account-circle')
            <circle cx="12" cy="12" r="9" />
            <circle cx="12" cy="9" r="2.25" />
            <path d="M7.5 16.5c1.3-1.5 2.8-2.25 4.5-2.25s3.2.75 4.5 2.25" />
            @break

        @case('edit')
            <path d="m4.5 19.5 3.75-.75L18 9l-3-3-9.75 9.75L4.5 19.5Z" />
            <path d="m13.5 6 3 3" />
            @break

        @case('undo')
            <path d="M9 7.5 4.5 12 9 16.5" />
            <path d="M4.5 12H13a5 5 0 0 1 5 5" />
            @break

        @case('delete')
            <path d="M4.5 7.5h15" />
            <path d="M9 7.5V6A1.5 1.5 0 0 1 10.5 4.5h3A1.5 1.5 0 0 1 15 6v1.5" />
            <path d="m6.75 7.5.75 10.5A1.5 1.5 0 0 0 9 19.5h6a1.5 1.5 0 0 0 1.5-1.5l.75-10.5" />
            <path d="M10.5 10.5v6m3-6v6" />
            @break

        @case('check-circle')
            <circle cx="12" cy="12" r="9" />
            <path d="m8.5 12.5 2.5 2.5 4.5-5" />
            @break

        @case('my-location')
            <circle cx="12" cy="12" r="6.5" />
            <circle cx="12" cy="12" r="2.25" />
            <path d="M12 3v2.25M12 18.75V21M3 12h2.25M18.75 12H21" />
            @break

        @case('layers')
            <path d="m12 4.5 8.25 4.5L12 13.5 3.75 9 12 4.5Z" />
            <path d="m3.75 12 8.25 4.5 8.25-4.5" />
            <path d="m3.75 15 8.25 4.5 8.25-4.5" />
            @break

        @case('polyline')
            <path d="M5 17 11 11l3 3 5-7" />
            <circle cx="5" cy="17" r="1.5" />
            <circle cx="11" cy="11" r="1.5" />
            <circle cx="14" cy="14" r="1.5" />
            <circle cx="19" cy="7" r="1.5" />
            @break

        @case('history')
            <path d="M4.5 12a7.5 7.5 0 1 0 2.2-5.3" />
            <path d="M4.5 6v3h3" />
            <path d="M12 8.5v4l2.5 1.5" />
            @break

        @case('settings')
            <circle cx="12" cy="12" r="3" />
            <path d="M12 2.75v2M12 19.25v2M2.75 12h2M19.25 12h2M5.9 5.9l1.4 1.4M16.7 16.7l1.4 1.4M5.9 18.1l1.4-1.4M16.7 7.3l1.4-1.4" />
            @break

        @case('campaign')
            <path d="M4.5 12V9.5c0-.6.4-1.2 1-1.3L15 6v10l-9.5-2.2c-.6-.1-1-.7-1-1.3Z" />
            <path d="M15 8.5 18.5 7v10L15 15.5" />
            <path d="m6.5 14 1.5 4h2" />
            @break

        @case('search')
            <circle cx="11" cy="11" r="6" />
            <path d="m20 20-4.5-4.5" />
            @break

        @case('group-off')
            <circle cx="9" cy="9" r="2.2" />
            <path d="M4.5 17c.8-2.2 2.4-3.3 4.5-3.3 1 0 1.9.2 2.7.7" />
            <circle cx="16.5" cy="10.5" r="1.8" />
            <path d="M13.8 17c.5-1.5 1.6-2.3 3.2-2.3.9 0 1.7.2 2.4.7" />
            <path d="m4 4 16 16" />
            @break

        @default
            <circle cx="12" cy="12" r="9" />
            <path d="M9.75 9.75h.01M14.25 9.75h.01M8.5 14.5c1 .9 2.2 1.35 3.5 1.35s2.5-.45 3.5-1.35" />
    @endswitch
</svg>
