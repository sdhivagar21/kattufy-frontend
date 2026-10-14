{{-- Messages carried across a redirect: success, status (neutral) and error. --}}
@foreach (['success' => 'success', 'status' => 'info', 'error' => 'danger'] as $key => $tone)
    @if (session($key))
        <x-alert :tone="$tone" class="mb-6" role="{{ $tone === 'danger' ? 'alert' : 'status' }}" dismissible>
            {{ session($key) }}
        </x-alert>
    @endif
@endforeach
