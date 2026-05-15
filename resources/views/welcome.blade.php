<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ __('Free medical outreach — lifePointe Greater Lekki. Saturday 16 May 2026 at Synlab Sangotedo.') }}">

        <title>{{ __('Free Medical Outreach') }} — {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans text-brand-ink">
        <div class="min-h-screen bg-white">
            <div
                class="pointer-events-none fixed inset-0 opacity-[0.35]"
                style="background-image: linear-gradient(to right, #e8e8e8 1px, transparent 1px), linear-gradient(to bottom, #e8e8e8 1px, transparent 1px); background-size: 28px 28px;"
                aria-hidden="true"
            ></div>

            <div class="relative">
                <header class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-6 sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}" class="flex items-center gap-3">
                        <img
                            src="{{ asset('images/app-logo.png') }}"
                            alt="{{ __('lifePointe Greater Lekki') }}"
                            class="h-12 w-auto sm:h-14"
                        />
                    </a>
                    @if (Route::has('login'))
                        <div class="flex items-center gap-2">
                            @auth
                                <a
                                    href="{{ url('/dashboard') }}"
                                    class="inline-flex min-h-11 items-center rounded-md bg-brand-secondary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-secondary-hover focus:outline-none focus:ring-2 focus:ring-brand-secondary focus:ring-offset-2"
                                >
                                    {{ __('Staff dashboard') }}
                                </a>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="inline-flex min-h-11 items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2"
                                >
                                    {{ __('Staff login') }}
                                </a>
                            @endauth
                        </div>
                    @endif
                </header>

                <main class="mx-auto max-w-6xl px-4 pb-16 sm:px-6 lg:px-8">
                    <section class="text-center">
                        <div class="flex flex-col items-center justify-center gap-4 sm:flex-row sm:gap-6">
                            <div
                                class="flex h-28 w-28 shrink-0 items-center justify-center rounded-full bg-[#C8102E] shadow-lg sm:h-32 sm:w-32"
                                aria-hidden="true"
                            >
                                <span class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ __('FREE') }}</span>
                            </div>
                            <h1 class="text-4xl font-extrabold uppercase leading-tight tracking-tight text-[#C8102E] sm:text-5xl lg:text-6xl">
                                {{ __('Medical Outreach') }}
                            </h1>
                        </div>
                    </section>

                    <section class="mt-10 grid gap-8 border-y border-[#C8102E]/20 py-8 sm:grid-cols-2 sm:gap-0 sm:divide-x sm:divide-[#C8102E]/20">
                        <div class="sm:px-8">
                            <h2 class="text-lg font-bold uppercase tracking-wide text-brand-secondary">{{ __('Focus tests') }}</h2>
                            <p class="mt-2 text-base text-gray-700">
                                {{ __('Hypertension, blood glucose, BMI, HIV, dental and eye check') }}
                            </p>
                        </div>
                        <div class="sm:px-8">
                            <h2 class="text-lg font-bold uppercase tracking-wide text-brand-secondary">{{ __('Interventions') }}</h2>
                            <p class="mt-2 text-base text-gray-700">
                                {{ __('Medical consultations, wellness counselling') }}
                            </p>
                        </div>
                    </section>

                    <section class="mt-10 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-lg bg-brand-secondary px-5 py-6 text-center text-white shadow-md">
                            <p class="text-xs font-semibold uppercase tracking-widest opacity-90">{{ __('Saturday') }}</p>
                            <p class="mt-1 text-5xl font-extrabold leading-none">16</p>
                            <p class="mt-1 text-lg font-medium">{{ __('May, 2026') }}</p>
                        </div>
                        <div class="flex flex-col justify-center rounded-lg bg-brand-secondary px-5 py-6 text-white shadow-md">
                            <div class="flex items-start gap-3">
                                <svg class="mt-0.5 h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <p class="text-left text-sm font-medium leading-snug sm:text-base">
                                    <span class="font-bold">Synlab,</span>
                                    {{ __('Km 26, DAT Mall, Opp. Emperor Estate, Lekki-Epe Exp way, Shoprite Bus Stop, Sangotedo') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center rounded-lg bg-brand-secondary px-5 py-6 text-center text-white shadow-md">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-2xl font-extrabold tracking-tight">{{ __('10:00 AM') }}</p>
                            <p class="text-sm font-semibold uppercase tracking-wide">{{ __('Prompt') }}</p>
                        </div>
                    </section>

                    <section class="mt-12">
                        <img
                            src="{{ asset('images/outreach-flyer-2026.png') }}"
                            alt="{{ __('Free medical outreach event flyer — lifePointe Greater Lekki, 16 May 2026') }}"
                            class="mx-auto w-full max-w-4xl rounded-lg border border-gray-200 shadow-lg"
                            width="1200"
                            height="auto"
                            loading="lazy"
                        />
                    </section>

                    <section class="mt-12 rounded-xl border border-brand-surface-border bg-brand-surface px-6 py-8 text-center">
                        <h2 class="text-xl font-bold text-brand-ink">{{ __('At the venue') }}</h2>
                        <p class="mx-auto mt-3 max-w-2xl text-base text-brand-text">
                            {{ __('Walk in on the day with a valid ID. Our team will register you, take your vitals, and guide you through the services you need. All care at this outreach is free.') }}
                        </p>
                    </section>

                    <section class="mt-12">
                        <div class="rounded-t-lg bg-[#C8102E] px-4 py-3 text-center">
                            <h2 class="text-sm font-bold uppercase tracking-widest text-white">{{ __('With support from') }}</h2>
                        </div>
                        <div class="rounded-b-lg border border-t-0 border-gray-200 bg-gray-50 px-4 py-8">
                            <ul class="mx-auto flex max-w-4xl flex-wrap items-center justify-center gap-x-8 gap-y-4 text-center text-sm font-semibold text-gray-700">
                                <li>Benson Adeyemi Foundation</li>
                                <li>Tope Adeboyejo Foundation</li>
                                <li>Medplus</li>
                                <li>Synlab</li>
                                <li>Cedarcare Hospital</li>
                                <li>EZ Health</li>
                                <li>Hilton Dental</li>
                                <li>Pistis Foundation</li>
                            </ul>
                        </div>
                    </section>
                </main>

                <footer class="border-t border-gray-200 bg-white py-8 text-center text-sm text-gray-500">
                    <p>&copy; {{ now()->year }} {{ __('lifePointe Greater Lekki') }}</p>
                </footer>
            </div>
        </div>
    </body>
</html>
