<div {{ $attributes->merge(['class' => 'brand-logo']) }}>
    <svg viewBox="0 0 120 120" aria-hidden="true">
        <defs>
            <linearGradient id="sj-ring" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#d9d9d9" />
                <stop offset="50%" stop-color="#ffffff" />
                <stop offset="100%" stop-color="#9ca3af" />
            </linearGradient>
            <linearGradient id="sj-shield" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#1984c7" />
                <stop offset="100%" stop-color="#20214f" />
            </linearGradient>
        </defs>

        <circle cx="60" cy="60" r="54" fill="url(#sj-ring)" stroke="#737373" stroke-width="3" />
        <circle cx="60" cy="60" r="42" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2" />
        <path d="M60 25 85 38v20c0 18-10 33-25 40-15-7-25-22-25-40V38l25-13Z" fill="url(#sj-shield)" stroke="#20214f" stroke-width="2.5" />
        <text x="60" y="58" text-anchor="middle" font-size="30" font-weight="700" fill="#e2e8f0" font-family="Arial, Helvetica, sans-serif">SJ</text>
        <text x="60" y="87" text-anchor="middle" font-size="8" font-weight="700" fill="#1f2937" letter-spacing="1.2" font-family="Arial, Helvetica, sans-serif">SEGURIDAD</text>
    </svg>

    <span class="brand-logo__text">
        <span class="brand-logo__title">
            <span class="brand-logo__mark">SJ</span>
            <span class="brand-logo__name">Seguridad</span>
        </span>
        <span class="brand-logo__subtitle">
            Privada Ltda
        </span>
    </span>
</div>
