export const BRAND_BLUE = '#1984c7';
export const BRAND_NAVY = '#20214f';
export const DONUT_COLORS = ['#0369a1', '#15803d', '#92400e', '#64748b'];
export const STATUS_DONUT_COLORS = ['#0369a1', '#92400e', '#15803d', '#be123c', '#64748b'];

export const STATUS_GREEN = '#15803d';
export const STATUS_BLUE = '#1984c7';
export const STATUS_SKY = '#0369a1';
export const STATUS_ORANGE = '#ea580c';
export const STATUS_RED = '#be123c';
export const STATUS_NEUTRAL = '#64748b';

/**
 * Colores de donut alineados a pastillas de etapa / flujo (PedidoEtapa y estados clásicos).
 *
 * @param {string[]} labels
 * @returns {string[]}
 */
export function colorsForWorkflowStatusLabels(labels) {
    return labels.map((label) => {
        const normalized = String(label || '').toLowerCase();

        if (normalized.includes('rechaz') || normalized.includes('devuel')) {
            return STATUS_RED;
        }

        if (normalized.includes('sin compra')) {
            return STATUS_NEUTRAL;
        }

        if (normalized.includes('cerrad') || normalized.includes('complet')) {
            return STATUS_GREEN;
        }

        if (normalized.includes('aprob') && !normalized.includes('autoriz')) {
            return STATUS_GREEN;
        }

        if (normalized.includes('con orden') || normalized.includes('curso')) {
            return STATUS_BLUE;
        }

        if (normalized.includes('por comprar') || normalized.includes('pendient')) {
            return STATUS_ORANGE;
        }

        if (normalized.includes('autoriz')) {
            return STATUS_SKY;
        }

        return STATUS_NEUTRAL;
    });
}

export const sharedChart = {
    chart: {
        fontFamily: 'inherit',
        toolbar: { show: false },
        animations: { enabled: false },
        zoom: { enabled: false },
    },
    dataLabels: { enabled: false },
    grid: {
        borderColor: '#e2e8f0',
        strokeDashArray: 3,
    },
};
