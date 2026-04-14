@extends('theme::layout')

@php
    $code ??= 500;
    $eyebrow ??= 'Error';
    $headline ??= 'Something went wrong.';
    $lede ??= null;
@endphp

@section('title', $code.' · '.$headline)

@section('content')
    <div class="py-12 md:py-20 max-w-2xl">
        <p class="vx-meta mb-3">{{ $eyebrow }}</p>

        <div class="flex items-baseline gap-6 mb-6">
            <span class="vx-display font-semibold leading-none tracking-tight text-[clamp(5rem,14vw,9rem)]"
                  style="font-family:'Fraunces',Georgia,serif;color:var(--accent);font-feature-settings:'ss01';">
                {{ $code }}
            </span>
            <span class="flex-1 h-px" style="background:var(--border)"></span>
        </div>

        <h1 class="vx-display text-3xl md:text-4xl font-semibold tracking-tight vx-heading mb-4">
            {{ $headline }}
        </h1>

        @if($lede)
            <p class="vx-muted text-base leading-relaxed max-w-prose">{{ $lede }}</p>
        @endif

        <div class="mt-10 flex flex-wrap gap-3">
            @hasSection('actions')
                @yield('actions')
            @else
                <a href="{{ url('/') }}" class="vx-btn-primary">Back to the Hub</a>
                <a href="javascript:history.back()" class="vx-btn-secondary">Go back</a>
            @endif
        </div>
    </div>
@endsection
