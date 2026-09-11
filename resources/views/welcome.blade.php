@extends('layouts.public')

@section('title', 'Find a place to call home · Cavite Rentals')

@section('content')
    <section class="relative overflow-hidden rounded-[2rem] bg-slate-950 px-6 py-16 sm:px-12 lg:px-16 lg:py-24">
        <div class="absolute -right-20 -top-28 h-80 w-80 rounded-full bg-rose-500/20 blur-3xl"></div>
        <div class="absolute -bottom-24 left-1/3 h-72 w-72 rounded-full bg-blue-500/20 blur-3xl"></div>
        <div class="relative max-w-3xl">
            <p class="eyebrow text-rose-300 mb-4">A better way to rent in Cavite</p>
            <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white text-balance">Find a place that feels like home.</h1>
            <p class="mt-5 max-w-2xl text-base sm:text-lg leading-8 text-slate-300">Discover rental homes, boarding houses, and dormitories across Cavite. Compare your options, schedule viewings, and move forward with confidence.</p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                <a href="{{ route('properties.index') }}" class="btn-primary btn-tenant">Explore properties <span aria-hidden="true">→</span></a>
                @guest<a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-bold text-white hover:bg-white/15 transition">List your property</a>@endguest
            </div>
        </div>
    </section>

    <section class="py-12 sm:py-16">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-7">
            <div><p class="eyebrow text-rose-600 mb-2">Simple from search to move-in</p><h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-950">Everything you need to rent smarter</h2></div>
            <a href="{{ route('properties.index') }}" class="text-sm font-bold text-rose-600 hover:underline">Browse all properties →</a>
        </div>
        <div class="grid gap-5 md:grid-cols-3">
            <div class="surface-card p-6"><div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-xl">⌕</div><h3 class="font-extrabold text-slate-950">Search with clarity</h3><p class="mt-2 text-sm leading-6 text-slate-500">Filter by location, property type, and monthly budget to narrow down the right fit.</p></div>
            <div class="surface-card p-6"><div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-xl">✓</div><h3 class="font-extrabold text-slate-950">Compare with confidence</h3><p class="mt-2 text-sm leading-6 text-slate-500">Save favorites, compare listings, and request a viewing without losing track.</p></div>
            <div class="surface-card p-6"><div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-xl">↗</div><h3 class="font-extrabold text-slate-950">Manage in one place</h3><p class="mt-2 text-sm leading-6 text-slate-500">Tenants and owners get focused dashboards for applications, contracts, payments, and maintenance.</p></div>
        </div>
    </section>
@endsection
