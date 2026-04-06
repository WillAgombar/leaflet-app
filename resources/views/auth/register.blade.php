@extends('layouts.mobile')

@section('title', 'Leaflet Tracker - Register')

@section('body-class', "overflow-x-hidden bg-[#f6fbf6] font-['Inter'] text-[#171d1a] selection:bg-[#a5d6a7] selection:text-[#1b5e20]")

@push('head')
    <style>
        .tracker-shadow {
            box-shadow: 0 16px 32px 0 rgba(18, 18, 18, 0.06);
        }

        .auth-bg {
            background:
                radial-gradient(circle at 0% 0%, rgba(165, 214, 167, 0.35), transparent 46%),
                radial-gradient(circle at 100% 20%, rgba(200, 230, 201, 0.45), transparent 44%),
                linear-gradient(180deg, #f6fbf6 0%, #f1f8f1 100%);
        }
    </style>
@endpush

@section('content')
    <main class="auth-bg relative min-h-dvh w-full pb-20">
        <header class="fixed top-0 z-50 w-full border-b border-[#e8f5e9] bg-[#f6fbf6]/80 backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-md items-center justify-between px-6">
                <div class="flex items-center gap-3">
                    <x-icon name="account-circle" class="h-6 w-6 text-[#1b5e20]" />
                    <h1 class="font-['Plus_Jakarta_Sans'] text-sm font-black uppercase tracking-wider text-[#1b5e20]">Register</h1>
                </div>
                <span class="text-[11px] font-black uppercase tracking-[0.2em] text-[#717a6d]">Secure Access</span>
            </div>
        </header>

        <section class="mx-auto max-w-md px-6 pt-24 space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-[0_10px_28px_rgba(23,29,26,0.06)]">
                <h2 class="font-['Plus_Jakarta_Sans'] text-2xl font-black text-[#1b5e20]">Create your account</h2>
                <p class="mt-2 text-sm text-[#41493e]">Join the team and start tracking routes.</p>
            </div>

            <form method="POST" action="/register" class="rounded-2xl bg-white p-6 shadow-[0_10px_28px_rgba(23,29,26,0.06)]">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label for="register-name" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-[#41493e]">Full Name</label>
                        <input
                            id="register-name"
                            name="name"
                            type="text"
                            autocomplete="name"
                            placeholder="Alex Rivers"
                            class="h-12 w-full rounded-full border border-[#dfe4df] bg-[#f6fbf6] px-5 text-sm font-semibold text-[#171d1a] placeholder:text-[#717a6d] focus:border-[#1b5e20] focus:ring-0"
                        >
                        @error('name')
                            <p class="mt-2 text-xs font-semibold text-[#ba1a1a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="register-email" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-[#41493e]">Email</label>
                        <input
                            id="register-email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                            class="h-12 w-full rounded-full border border-[#dfe4df] bg-[#f6fbf6] px-5 text-sm font-semibold text-[#171d1a] placeholder:text-[#717a6d] focus:border-[#1b5e20] focus:ring-0"
                        >
                        @error('email')
                            <p class="mt-2 text-xs font-semibold text-[#ba1a1a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="register-password" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-[#41493e]">Password</label>
                        <input
                            id="register-password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="••••••••"
                            class="h-12 w-full rounded-full border border-[#dfe4df] bg-[#f6fbf6] px-5 text-sm font-semibold text-[#171d1a] placeholder:text-[#717a6d] focus:border-[#1b5e20] focus:ring-0"
                        >
                        @error('password')
                            <p class="mt-2 text-xs font-semibold text-[#ba1a1a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="register-password-confirmation" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-[#41493e]">Confirm Password</label>
                        <input
                            id="register-password-confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            placeholder="••••••••"
                            class="h-12 w-full rounded-full border border-[#dfe4df] bg-[#f6fbf6] px-5 text-sm font-semibold text-[#171d1a] placeholder:text-[#717a6d] focus:border-[#1b5e20] focus:ring-0"
                        >
                        @error('password_confirmation')
                            <p class="mt-2 text-xs font-semibold text-[#ba1a1a]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <button
                    type="submit"
                    class="mt-6 flex h-12 w-full items-center justify-center rounded-full bg-[#1b5e20] text-xs font-black uppercase tracking-[0.2em] text-white shadow-[0_8px_16px_rgba(27,94,32,0.18)] transition-all active:scale-95"
                >
                    Create Account
                </button>
            </form>

            <div class="rounded-2xl bg-white p-6 text-center shadow-[0_10px_28px_rgba(23,29,26,0.06)]">
                <p class="text-sm text-[#41493e]">Already have an account?</p>
                <a href="{{ route('login') }}" class="mt-3 inline-flex h-11 items-center justify-center rounded-full border border-[#c0c9bb] px-6 text-xs font-black uppercase tracking-[0.2em] text-[#1b5e20] transition-all active:scale-95">
                    Sign In
                </a>
            </div>
        </section>
    </main>
@endsection
