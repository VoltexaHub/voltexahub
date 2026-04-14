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

    <h1 class="text-2xl font-semibold text-gray-900 mb-4">New Message</h1>

    <form method="POST" action="{{ route('messages.store') }}"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-4 max-w-2xl"
          x-data>
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
            @if($to)
                <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded px-3 py-2">
                    <img src="{{ $to->avatar_url }}" alt="" class="w-6 h-6 rounded-full" />
                    <span class="font-medium text-gray-800">{{ $to->name }}</span>
                    <input type="hidden" name="recipient_id" value="{{ $to->id }}" />
                </div>
            @else
                <input name="recipient_id" type="number" placeholder="User ID" required
                       class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
                <p class="text-xs text-gray-500 mt-1">Tip: open a user's profile and click the "Send Message" button to skip this step.</p>
            @endif
            @error('recipient_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
            <textarea name="body" rows="8" data-markdown required
                      class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('body') }}</textarea>
            @error('body')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('messages.index') }}" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700">Send</button>
        </div>
    </form>
@endsection
