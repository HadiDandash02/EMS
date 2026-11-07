@props(['items' => []])
{{-- Caption/value pairs: <x-info-list :items="[__('Department') => $name, ...]" />. Look = .info-label / .info-value. --}}
<dl {{ $attributes->class('space-y-4') }}>
    @foreach ($items as $label => $value)
        <div>
            <dt class="info-label">{{ $label }}</dt>
            <dd class="info-value">{{ $value }}</dd>
        </div>
    @endforeach
</dl>
