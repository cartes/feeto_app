<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        @php
            $tenant = \App\Models\Tenant::current();
        @endphp
        @if ($tenant)
            <x-mail::header :url="route('taller.landing', ['tenantBySlug' => $tenant->slug])">
                {{ $tenant->name }}
            </x-mail::header>
        @else
            <x-mail::header :url="config('app.url')">
                {{ config('app.name') }}
            </x-mail::header>
        @endif
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
