@props(['type' => 'doughnut', 'labels' => [], 'values' => [], 'colors' => null, 'label' => '', 'height' => 'h-64', 'empty' => null, 'horizontal' => false])
{{--
    One chart = one component. Uses Chart.js, loaded on demand (only dashboard pages download it).
    <x-chart type="doughnut|bar|line" :labels="[...]" :values="[...]" :colors="[...]" label="Tasks" />
    Colours: pass `colors` (one per slice/bar) or leave null for the brand colour. Looks (card, title) come from <x-card>.
--}}
@php $hasData = array_sum($values) > 0; @endphp
@if (! $hasData)
    <div class="flex {{ $height }} items-center justify-center rounded-xl bg-slate-50 text-sm text-slate-400">{{ $empty ?? __('No data yet') }}</div>
@else
    <div class="relative {{ $height }}" wire:ignore
         x-data="{
             async init() {
                 const Chart = await window.loadChart();
                 const type = @js($type), horizontal = @js((bool) $horizontal), colors = @js($colors);
                 const dark = document.documentElement.dataset.theme === 'dark';
                 const text = dark ? '#b3bfd2' : '#64748b', grid = dark ? '#2a3856' : '#e2e8f0';
                 const brand = '#6366f1';
                 const circular = type === 'doughnut' || type === 'pie';
                 const dataset = { label: @js($label), data: @js($values),
                     backgroundColor: circular || type === 'bar' ? (colors ?? brand) : 'rgba(99,102,241,.15)',
                     borderColor: type === 'line' ? brand : (circular ? (dark ? '#111b33' : '#ffffff') : undefined),
                     borderWidth: circular ? 2 : (type === 'line' ? 2 : 0), borderRadius: type === 'bar' ? 6 : 0,
                     fill: type === 'line', tension: .35, pointRadius: type === 'line' ? 0 : undefined, pointHoverRadius: 4, maxBarThickness: 38 };
                 new Chart(this.$refs.c, {
                     type, data: { labels: @js($labels), datasets: [dataset] },
                     options: { responsive: true, maintainAspectRatio: false, indexAxis: horizontal ? 'y' : 'x', cutout: type === 'doughnut' ? '62%' : undefined,
                         plugins: { legend: { display: circular, position: 'bottom', labels: { color: text, usePointStyle: true, boxWidth: 8, padding: 14 } } },
                         scales: circular ? {} : { x: { grid: { display: horizontal, color: grid }, ticks: { color: text, maxRotation: 0, autoSkipPadding: 12 } },
                                                  y: { beginAtZero: true, grid: { color: grid, display: !horizontal }, ticks: { color: text, precision: 0 } } } }
                 });
             }
         }">
        <canvas x-ref="c" role="img" aria-label="{{ $label }}"></canvas>
    </div>
@endif
