@extends('theme::layout')

@section('title', 'New Message · '.config('app.name'))

@push('scripts')
    @vite('resources/js/markdown-editor.js')
@endpush

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Messages', 'url' => route('messages.index')],
        ['label' => 'New Message'],
    ]])

    <header class="mb-8">
        <p class="vx-meta mb-2">Compose</p>
        <h1 class="vx-display text-4xl font-semibold tracking-tight vx-heading">New Message</h1>
    </header>

    <form method="POST" action="{{ route('messages.store') }}" class="space-y-6 max-w-2xl">
        @csrf

        <div>
            <label class="vx-meta mb-2 block">To</label>
            @if($to)
                <div class="flex items-center gap-2 p-3 border vx-hairline rounded-lg bg-[color:var(--surface)]">
                    <img src="{{ $to->avatar_url }}" alt="" class="w-7 h-7 rounded-full" />
                    <span class="vx-display font-medium vx-heading">{{ $to->name }}</span>
                    <input type="hidden" name="recipient_id" value="{{ $to->id }}" />
                </div>
            @else
                <input name="recipient_id" type="number" placeholder="User ID" required class="vx-input" />
                <p class="vx-meta normal-case tracking-normal text-[0.7rem] mt-1">Tip: open a user's profile and click "Send Message" to skip this step.</p>
            @endif
            @error('recipient_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="vx-meta mb-2 block">Message</label>
            <textarea name="body" rows="8" data-markdown required class="vx-input">{{ old('body') }}</textarea>
            @error('body')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('messages.index') }}" class="vx-btn-secondary">Cancel</a>
            <button type="submit" class="vx-btn-primary">Send</button>
        </div>
    </form>
@endsection
