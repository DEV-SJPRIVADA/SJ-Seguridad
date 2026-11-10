{{-- Tarjetas de resultado, semáforo, cumplimiento y mejora (cálculo en vivo). --}}
<div class="cursos-dashboard-page__kpi-grid planeacion-capture-metrics">
    <div class="panel cursos-dashboard-page__kpi">
        <p class="cursos-dashboard-page__kpi-label">Resultado</p>
        <p class="cursos-dashboard-page__kpi-value" x-text="formattedResult()">{{ $result !== null ? number_format((float) $result, 2, ',', '.') : '—' }}</p>
    </div>
    <div class="panel cursos-dashboard-page__kpi">
        <p class="cursos-dashboard-page__kpi-label">Semáforo</p>
        <p class="cursos-dashboard-page__kpi-value">
            <span
                class="status-pill"
                :class="complies ? 'status-pill--req-contratado' : 'status-pill--req-cancelada'"
                x-text="inputError ? '—' : (complies ? 'VERDE' : 'ROJO')"
            >{{ $complies ? 'VERDE' : 'ROJO' }}</span>
        </p>
    </div>
    <div class="panel cursos-dashboard-page__kpi">
        <p class="cursos-dashboard-page__kpi-label">Cumple</p>
        <p class="cursos-dashboard-page__kpi-value" x-text="inputError ? '—' : (complies ? 'Sí' : 'No')">{{ $complies ? 'Sí' : 'No' }}</p>
    </div>
    <div class="panel cursos-dashboard-page__kpi">
        <p class="cursos-dashboard-page__kpi-label">Mejora</p>
        <p class="cursos-dashboard-page__kpi-value" x-text="inputError ? '—' : (complies ? 'No' : 'Sí')">{{ $complies ? 'No' : 'Sí' }}</p>
    </div>
</div>
