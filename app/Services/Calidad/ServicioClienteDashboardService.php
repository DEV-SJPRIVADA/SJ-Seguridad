<?php

namespace App\Services\Calidad;

use App\Models\ServicioClienteCaso;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

final class ServicioClienteDashboardService
{
    public function __construct(
        private readonly ServicioClienteDatatableService $datatable,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function metrics(array $filters): array
    {
        $query = ServicioClienteCaso::query();
        $this->datatable->applyFilters($query, $filters);
        $today = now()->toDateString();

        $total = (clone $query)->count();
        $abiertos = (clone $query)->where('eficacia', ServicioClienteCaso::EFICACIA_ABIERTO)->count();
        $enProceso = (clone $query)->where('eficacia', ServicioClienteCaso::EFICACIA_EN_PROCESO)->count();
        $cerrados = (clone $query)->where('eficacia', ServicioClienteCaso::EFICACIA_CERRADO)->count();
        $vencidos = (clone $query)
            ->where('eficacia', '!=', ServicioClienteCaso::EFICACIA_CERRADO)
            ->whereDate('fecha_plazo_respuesta', '<', $today)
            ->count();
        $promedio = (clone $query)->whereNotNull('dias_habiles')->avg('dias_habiles');

        return [
            'kpis' => [
                'total' => $total,
                'abiertos' => $abiertos,
                'en_proceso' => $enProceso,
                'cerrados' => $cerrados,
                'vencidos' => $vencidos,
                'promedio_dias' => $promedio === null ? null : round((float) $promedio, 1),
            ],
            'tipologia' => $this->countsByRelation($query, 'tipologia', 'servicio_cliente_tipologias'),
            'categoria' => $this->countsByRelation($query, 'categoria', 'servicio_cliente_categorias'),
            'eficacia' => $this->countsByEficacia($query),
            'fuente' => $this->countsByRelation($query, 'fuente', 'servicio_cliente_fuentes'),
            'recurrencia' => $this->recurrence($query, (string) ($filters['recurrencia'] ?? 'mes')),
        ];
    }

    /**
     * @param  Builder<ServicioClienteCaso>  $query
     * @return array{labels: list<string>, data: list<int>}
     */
    private function countsByRelation(Builder $query, string $relation, string $table): array
    {
        $builder = (clone $query)->reorder();
        $builder->getQuery()->columns = null;

        $rows = $builder
            ->join($table, $table.'.id', '=', 'servicio_cliente_casos.'.$this->foreignKey($relation))
            ->selectRaw($table.'.name as label, COUNT(servicio_cliente_casos.id) as total')
            ->groupBy($table.'.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('label')->map(fn ($label): string => (string) $label)->all(),
            'data' => $rows->pluck('total')->map(fn ($total): int => (int) $total)->all(),
        ];
    }

    private function foreignKey(string $relation): string
    {
        return match ($relation) {
            'tipologia' => 'tipologia_id',
            'categoria' => 'categoria_id',
            'fuente' => 'fuente_id',
            default => $relation.'_id',
        };
    }

    /**
     * @param  Builder<ServicioClienteCaso>  $query
     * @return array{labels: list<string>, data: list<int>}
     */
    private function countsByEficacia(Builder $query): array
    {
        $builder = (clone $query)->reorder();
        $builder->getQuery()->columns = null;

        $counts = $builder
            ->selectRaw('eficacia, COUNT(servicio_cliente_casos.id) as total')
            ->groupBy('eficacia')
            ->pluck('total', 'eficacia');

        $labels = [];
        $data = [];

        foreach (config('servicio_cliente.eficacia', []) as $key => $label) {
            $labels[] = (string) $label;
            $data[] = (int) ($counts[$key] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Clientes con 2 o más casos en cada cubo del grano elegido.
     *
     * @param  Builder<ServicioClienteCaso>  $query
     * @return array{labels: list<string>, data: list<int>, clientes: list<array{nombre: string, total: int}>}
     */
    private function recurrence(Builder $query, string $grain): array
    {
        if (! array_key_exists($grain, config('servicio_cliente.recurrencia', []))) {
            $grain = 'mes';
        }

        $rows = (clone $query)->get(['cliente_nombre', 'fecha_recepcion']);
        $buckets = [];
        $overall = [];

        foreach ($rows as $row) {
            $date = $row->fecha_recepcion instanceof Carbon ? $row->fecha_recepcion : Carbon::parse($row->fecha_recepcion);
            [$key, $label] = $this->bucket($date, $grain);
            $name = (string) $row->cliente_nombre;
            $buckets[$key]['label'] = $label;
            $buckets[$key]['clients'][$name] = ($buckets[$key]['clients'][$name] ?? 0) + 1;
            $overall[$name] = ($overall[$name] ?? 0) + 1;
        }

        ksort($buckets);

        $labels = [];
        $data = [];

        foreach ($buckets as $bucket) {
            $labels[] = $bucket['label'];
            $data[] = count(array_filter($bucket['clients'], fn (int $total): bool => $total >= 2));
        }

        $clientes = [];
        foreach ($overall as $nombre => $total) {
            if ($total >= 2) {
                $clientes[] = ['nombre' => $nombre, 'total' => $total];
            }
        }

        usort($clientes, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return [
            'labels' => $labels,
            'data' => $data,
            'clientes' => array_slice($clientes, 0, 15),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function bucket(Carbon $date, string $grain): array
    {
        return match ($grain) {
            'trimestre' => [$date->format('Y').'-T'.$date->quarter, $date->year.' T'.$date->quarter],
            'semestre' => [
                $date->format('Y').'-S'.($date->month <= 6 ? '1' : '2'),
                $date->year.' S'.($date->month <= 6 ? '1' : '2'),
            ],
            'anio' => [$date->format('Y'), (string) $date->year],
            default => [$date->format('Y-m'), $date->translatedFormat('M Y')],
        };
    }
}
