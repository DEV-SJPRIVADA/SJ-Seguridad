<?php

namespace Tests\Feature\PurchaseRequests;

use App\Models\PurchaseRequest;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseRequestExcelExporter;
use App\Services\Supplies\SupplyPurchaseReportExporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class FoAd44ExportAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function captureStreamedBinary(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }

    public function test_purchase_config_has_marzo_2026_version_01_metadata(): void
    {
        $this->assertSame('FO-AD-44', config('purchase-requests.form_code'));
        $this->assertSame('01', config('purchase-requests.form_version'));
        $this->assertSame('Marzo 2026', config('purchase-requests.form_period'));
        $this->assertSame('S/A', config('purchase-requests.photo_placeholder'));
    }

    public function test_supply_config_has_marzo_2026_version_01_metadata(): void
    {
        $this->assertSame('FO-AD-44', config('supplies.report.form_code'));
        $this->assertSame('01', config('supplies.report.version'));
        $this->assertSame('Marzo 2026', config('supplies.report.period'));
        $this->assertSame('S/A', config('supplies.report.photo_placeholder'));
    }

    public function test_purchase_excel_embeds_photo_and_official_headers(): void
    {
        Storage::fake('public');

        $requester = $this->purchaseRequester();
        $director = $this->director();
        $purchaseRequest = PurchaseRequest::query()->create([
            'numero_solicitud' => 4401,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_PENDIENTE,
        ]);

        $photo = UploadedFile::fake()->image('monitor.jpg', 80, 60);
        $fotoPath = $photo->store('purchase-request-items', 'public');

        $purchaseRequest->items()->create([
            'orden' => 1,
            'cantidad' => 2,
            'descripcion' => 'Monitor LED',
            'referencia' => 'MON-001',
            'utilizacion' => 'Oficina',
            'ubicacion' => 'Cali',
            'foto_path' => $fotoPath,
        ]);

        $purchaseRequest->load('items');

        $response = app(PurchaseRequestExcelExporter::class)->toDownloadResponse($purchaseRequest);
        $this->assertSame(200, $response->getStatusCode());

        $temp = tempnam(sys_get_temp_dir(), 'foad44').'.xlsx';
        file_put_contents($temp, $this->captureStreamedBinary($response));

        try {
            $sheet = IOFactory::load($temp)->getActiveSheet();

            $this->assertSame('FO-AD-44', (string) $sheet->getCell('F1')->getValue());
            $this->assertSame('Marzo 2026', (string) $sheet->getCell('F2')->getValue());
            $this->assertSame('Versión 01', (string) $sheet->getCell('F3')->getValue());
            $this->assertSame('Página 1 de 1', (string) $sheet->getCell('F4')->getValue());

            $this->assertSame('Cantidad', (string) $sheet->getCell('A6')->getValue());
            $this->assertSame('Foto', (string) $sheet->getCell('B6')->getValue());
            $this->assertSame('Descripción', (string) $sheet->getCell('C6')->getValue());
            $this->assertSame('Referencia', (string) $sheet->getCell('D6')->getValue());
            $this->assertSame('Utilización', (string) $sheet->getCell('E6')->getValue());
            $this->assertSame('Ubicación', (string) $sheet->getCell('F6')->getValue());

            $this->assertSame(2, (int) $sheet->getCell('A7')->getValue());
            $this->assertSame('Monitor LED', (string) $sheet->getCell('C7')->getValue());
            $this->assertNotSame('N/A', (string) $sheet->getCell('B7')->getValue());
            $this->assertGreaterThan(0, $sheet->getDrawingCollection()->count());
        } finally {
            @unlink($temp);
        }
    }

    public function test_purchase_excel_uses_sa_placeholder_when_photo_missing(): void
    {
        $requester = $this->purchaseRequester();
        $director = $this->director();
        $purchaseRequest = PurchaseRequest::query()->create([
            'numero_solicitud' => 4402,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_PENDIENTE,
        ]);

        $purchaseRequest->items()->create([
            'orden' => 1,
            'cantidad' => 1,
            'descripcion' => 'Sin foto',
            'referencia' => 'REF-0',
            'utilizacion' => 'Uso',
            'ubicacion' => 'Sede',
            'foto_path' => null,
        ]);

        $purchaseRequest->load('items');

        $response = app(PurchaseRequestExcelExporter::class)->toDownloadResponse($purchaseRequest);
        $temp = tempnam(sys_get_temp_dir(), 'foad44').'.xlsx';
        file_put_contents($temp, $this->captureStreamedBinary($response));

        try {
            $sheet = IOFactory::load($temp)->getActiveSheet();
            $this->assertSame('S/A', (string) $sheet->getCell('B7')->getValue());
            $this->assertNotSame('N/A', (string) $sheet->getCell('B7')->getValue());
        } finally {
            @unlink($temp);
        }
    }

    public function test_purchase_pdf_blade_shows_official_columns_and_metadata(): void
    {
        $requester = $this->purchaseRequester();
        $director = $this->director();
        $purchaseRequest = PurchaseRequest::query()->create([
            'numero_solicitud' => 4403,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_PENDIENTE,
        ]);

        $purchaseRequest->items()->create([
            'orden' => 1,
            'cantidad' => 3,
            'descripcion' => 'Silla ergonómica',
            'referencia' => 'SIL-01',
            'utilizacion' => 'Puesto',
            'ubicacion' => 'Bogotá',
        ]);

        $html = view('pdf.purchase-request-solicitud', [
            'purchaseRequest' => $purchaseRequest->load(['user', 'aprobador', 'items', 'procesadoComprasPor']),
            'itemPhotos' => [],
            'generatedAt' => now(),
            'formCode' => config('purchase-requests.form_code'),
            'formVersion' => config('purchase-requests.form_version'),
            'formPeriod' => config('purchase-requests.form_period'),
            'photoPlaceholder' => config('purchase-requests.photo_placeholder'),
            'reportTitle' => config('purchase-requests.report_title'),
            'pageLabel' => 'Página 1 de 1',
        ])->render();

        $this->assertStringContainsString('FO-AD-44', $html);
        $this->assertStringContainsString('Marzo 2026', $html);
        $this->assertStringContainsString('Versión 01', $html);
        $this->assertStringContainsString('Página 1 de 1', $html);
        $this->assertStringContainsString('Cantidad', $html);
        $this->assertStringContainsString('Descripción', $html);
        $this->assertStringContainsString('Utilización', $html);
        $this->assertStringContainsString('Ubicación', $html);
        $this->assertStringContainsString('Silla ergonómica', $html);
        $this->assertStringContainsString('S/A', $html);
    }

    public function test_supply_excel_has_official_headers_and_sa_photo_placeholder(): void
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'must_change_password' => false,
        ]);
        $user->givePermissionTo('supply.tab.my_requests');

        $request = SupplyRequest::create([
            'user_id' => $user->id,
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => 'aprobada_calidad',
        ]);

        $request->items()->create([
            'supply_product_id' => $product->id,
            'requested_quantity' => 4,
            'approved_quantity' => 4,
            'is_not_in_catalog' => false,
        ]);

        $exporter = app(SupplyPurchaseReportExporter::class);
        $rows = $exporter->buildMergedRowsForRequest($request);
        $response = $exporter->toDownloadResponseForRequest($request, $rows);

        $temp = tempnam(sys_get_temp_dir(), 'foad44s').'.xlsx';
        file_put_contents($temp, $this->captureStreamedBinary($response));

        try {
            $sheet = IOFactory::load($temp)->getActiveSheet();

            $this->assertSame('FO-AD-44', (string) $sheet->getCell('F1')->getValue());
            $this->assertSame('Marzo 2026', (string) $sheet->getCell('F2')->getValue());
            $this->assertSame('Versión 01', (string) $sheet->getCell('F3')->getValue());
            $this->assertSame('Página 1 de 1', (string) $sheet->getCell('F4')->getValue());

            $this->assertSame('Cantidad', (string) $sheet->getCell('A6')->getValue());
            $this->assertSame('Foto', (string) $sheet->getCell('B6')->getValue());
            $this->assertSame('Descripción', (string) $sheet->getCell('C6')->getValue());
            $this->assertSame('Utilización', (string) $sheet->getCell('E6')->getValue());
            $this->assertSame('Ubicación', (string) $sheet->getCell('F6')->getValue());
            $this->assertSame('S/A', (string) $sheet->getCell('B7')->getValue());
            $this->assertStringNotContainsStringIgnoringCase('Insertar Foto', (string) $sheet->getCell('B6')->getValue());
        } finally {
            @unlink($temp);
        }
    }

    public function test_supply_pdf_blade_shows_official_columns_and_metadata(): void
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'must_change_password' => false,
        ]);

        $supplyRequest = SupplyRequest::create([
            'user_id' => $user->id,
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => 'aprobada_calidad',
        ]);

        $supplyRequest->items()->create([
            'supply_product_id' => $product->id,
            'requested_quantity' => 2,
            'approved_quantity' => 2,
            'is_not_in_catalog' => false,
        ]);

        $lineItems = app(SupplyPurchaseReportExporter::class)
            ->buildMergedRowsForRequest($supplyRequest)
            ->values()
            ->map(fn (array $row): array => $row + ['photo_data_uri' => null])
            ->all();

        $html = view('pdf.supply-request-solicitud', [
            'supplyRequest' => $supplyRequest->load(['user', 'items.product', 'qualityReviewer', 'purchasingManager']),
            'lineItems' => $lineItems,
            'generatedAt' => now(),
            'formCode' => config('supplies.report.form_code'),
            'formVersion' => config('supplies.report.version'),
            'formPeriod' => config('supplies.report.period'),
            'photoPlaceholder' => config('supplies.report.photo_placeholder'),
            'reportTitle' => config('supplies.report.title'),
            'pageLabel' => 'Página 1 de 1',
        ])->render();

        $this->assertStringContainsString('FO-AD-44', $html);
        $this->assertStringContainsString('Marzo 2026', $html);
        $this->assertStringContainsString('Versión 01', $html);
        $this->assertStringContainsString('Página 1 de 1', $html);
        $this->assertStringContainsString('Cantidad', $html);
        $this->assertStringContainsString('Descripción', $html);
        $this->assertStringContainsString('Utilización', $html);
        $this->assertStringContainsString('Ubicación', $html);
        $this->assertStringContainsString('S/A', $html);
    }

    private function purchaseRequester(): User
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo(['purchase.tab.create', 'purchase.tab.my_requests', 'view.board.operaciones.solicitudes_compra']);

        return $user;
    }

    private function director(): User
    {
        $user = User::factory()->create([
            'email' => 'director-foad44@test.local',
            'must_change_password' => false,
        ]);
        $user->assignRole('director');

        return $user;
    }
}
