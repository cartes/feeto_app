368=5
<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        @php
            $tenant = \App\Models\Tenant::current();
        @endphp
        @if ($tenant)
            <x-mail::header :url="route('taller.landing', ['tenantBySlug' => $tenant->slug])">
                @if ($tenant->logoUrl())
                    <img src="{{ $tenant->logoUrl() }}" class="logo" alt="{{ $tenant->name }}"
                        style="max-height: 60px; width: auto; object-fit: contain;">
                @else
                    {{ $tenant->name }}
                @endif
            </x-mail::header>
        @else
            <x-mail::header :url="config('app.url')">
                <img src="{{ asset('images/taller-flow-isotipo.png') }}" class="logo" alt="{{ config('app.name') }}" style="max-height: 40px; width: auto; object-fit: contain;">
            </x-mail::header>
        @endif
    </x-slot:header>

    {{-- Body --}}
    {!! $slot !!}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {!! $subcopy !!}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
