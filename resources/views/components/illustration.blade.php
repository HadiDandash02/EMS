{{-- Inline SVG artwork: a task board with an approved card. No external images needed. --}}
<svg viewBox="0 0 520 400" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }} role="img" aria-label="Task board illustration">
    <defs>
        <linearGradient id="il-a" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#818cf8"/><stop offset="1" stop-color="#a78bfa"/></linearGradient>
        <linearGradient id="il-b" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#34d399"/><stop offset="1" stop-color="#2dd4bf"/></linearGradient>
    </defs>
    <circle cx="420" cy="70" r="120" fill="#6366f1" fill-opacity=".18"/>
    <circle cx="70" cy="340" r="90" fill="#8b5cf6" fill-opacity=".18"/>

    {{-- main window --}}
    <rect x="60" y="50" width="400" height="290" rx="22" fill="#fff" fill-opacity=".96"/>
    <rect x="60" y="50" width="400" height="46" rx="22" fill="#e0e7ff"/>
    <rect x="60" y="74" width="400" height="22" fill="#e0e7ff"/>
    <circle cx="90" cy="73" r="6" fill="#fca5a5"/><circle cx="110" cy="73" r="6" fill="#fcd34d"/><circle cx="130" cy="73" r="6" fill="#6ee7b7"/>

    {{-- columns --}}
    <rect x="82" y="116" width="110" height="200" rx="14" fill="#f1f5f9"/>
    <rect x="205" y="116" width="110" height="200" rx="14" fill="#f1f5f9"/>
    <rect x="328" y="116" width="110" height="200" rx="14" fill="#f1f5f9"/>
    <rect x="94" y="128" width="44" height="8" rx="4" fill="#94a3b8"/>
    <rect x="217" y="128" width="54" height="8" rx="4" fill="#94a3b8"/>
    <rect x="340" y="128" width="50" height="8" rx="4" fill="#94a3b8"/>

    {{-- cards --}}
    <rect x="94" y="150" width="86" height="52" rx="10" fill="#fff"/><rect x="104" y="162" width="52" height="7" rx="3.5" fill="#cbd5e1"/><rect x="104" y="176" width="34" height="6" rx="3" fill="#e2e8f0"/><circle cx="165" cy="188" r="7" fill="url(#il-a)"/>
    <rect x="94" y="212" width="86" height="52" rx="10" fill="#fff"/><rect x="104" y="224" width="46" height="7" rx="3.5" fill="#cbd5e1"/><rect x="104" y="238" width="30" height="6" rx="3" fill="#e2e8f0"/><circle cx="165" cy="250" r="7" fill="#fbbf24"/>
    <rect x="217" y="150" width="86" height="52" rx="10" fill="#fff"/><rect x="227" y="162" width="56" height="7" rx="3.5" fill="#cbd5e1"/><rect x="227" y="176" width="36" height="6" rx="3" fill="#e2e8f0"/><circle cx="288" cy="188" r="7" fill="#60a5fa"/>
    <rect x="340" y="150" width="86" height="52" rx="10" fill="url(#il-b)"/><rect x="350" y="162" width="50" height="7" rx="3.5" fill="#fff" fill-opacity=".85"/><rect x="350" y="176" width="32" height="6" rx="3" fill="#fff" fill-opacity=".6"/>
    <circle cx="411" cy="188" r="9" fill="#fff"/><path d="M406.5 188l3 3 5.5-6" stroke="#10b981" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>

    {{-- floating badge --}}
    <g>
        <rect x="318" y="268" width="150" height="58" rx="16" fill="#fff" style="filter:drop-shadow(0 10px 18px rgba(79,70,229,.25))"/>
        <circle cx="347" cy="297" r="15" fill="url(#il-b)"/><path d="M340 297l5 5 9-10" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <rect x="372" y="285" width="70" height="8" rx="4" fill="#334155"/><rect x="372" y="301" width="48" height="7" rx="3.5" fill="#cbd5e1"/>
    </g>
</svg>
