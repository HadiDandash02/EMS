{{--
    Banner shown on top of a page when the SERVER rejected a submitted form (Laravel validation).
    The browser's own "please fill out this field" bubbles are switched off (<form novalidate>), so the
    rules are enforced - and explained - by the back end only. Messages: lang/en/validation.php, lang/ar/validation.php.
--}}
@if ($errors->any())
    <div role="alert" class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.3 3.9L2.4 17.6A2 2 0 004.1 20.6h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
        <div>
            <p class="text-sm font-semibold">{{ __('Please complete the required fields and fix the highlighted problems, then try again.') }}</p>
            <ul class="mt-1 list-disc ps-5 text-sm">
                @foreach (collect($errors->all())->unique()->take(6) as $message)<li>{{ $message }}</li>@endforeach
            </ul>
        </div>
    </div>
@endif
