<?php

namespace Tests\Feature\Integrations;

use App\Models\EmployeeFichaProfile;
use App\Services\Integrations\EmployeeSyncService;
use Tests\TestCase;

class EmployeePayloadTest extends TestCase
{
    private function profile(array $attributes = []): EmployeeFichaProfile
    {
        return new EmployeeFichaProfile(array_merge([
            'document_number' => '1020304050',
            'document_type' => 'C',
            'first_name' => 'JUAN',
            'second_name' => 'CARLOS',
            'first_surname' => 'PEREZ',
            'second_surname' => 'GOMEZ',
            'sex' => 'M',
            'birth_date' => '1990-05-04',
            'address' => 'CALLE 1 # 2-3',
            'residence_city_code' => '76001',
            'work_city_code' => '76001',
            'phone' => '3001234567',
            'email' => 'juan@example.com',
            'hire_date' => '2020-01-15',
            'contract_type_name' => 'A TERMINO FIJO',
            'position_code' => '009',
            'position_name' => 'GUARDA',
            'salary' => '2456300.00',
            'employment_status' => 'activo',
            'termination_date' => '9999-12-31',
            'emergency_contact_name' => 'NO DEBE VIAJAR',
            'emergency_contact_phone' => '3000000000',
            'department_area' => 'OPERATIVO',
        ], $attributes));
    }

    public function test_payload_respeta_la_whitelist(): void
    {
        $payload = (new EmployeeSyncService)->payloadFor($this->profile());

        $this->assertIsArray($payload);

        $expected = [
            'id', 'document_number', 'document_type', 'first_name', 'last_name', 'gender',
            'birth_date', 'address', 'residence_municipality_code', 'residence_department_code',
            'municipality_code', 'work_department_code', 'phone', 'email', 'hired_at',
            'contract_type', 'job_title', 'position_code', 'base_salary', 'is_active', 'termination_at',
        ];

        $this->assertSame($expected, array_keys($payload));
        $this->assertArrayNotHasKey('emergency_contact_name', $payload);
        $this->assertArrayNotHasKey('emergency_contact_phone', $payload);
        $this->assertArrayNotHasKey('department_area', $payload);
        $this->assertArrayNotHasKey('employee_scope', $payload);
    }

    public function test_mapea_nombres_y_apellidos_concatenados(): void
    {
        $payload = (new EmployeeSyncService)->payloadFor($this->profile());

        $this->assertSame('JUAN CARLOS', $payload['first_name']);
        $this->assertSame('PEREZ GOMEZ', $payload['last_name']);
        $this->assertSame('1020304050', $payload['document_number']);
        $this->assertSame('76001', $payload['residence_municipality_code']);
        $this->assertNull($payload['residence_department_code']);
        $this->assertSame('2456300.00', $payload['base_salary']);
        $this->assertSame('termino_fijo', $payload['contract_type']);
        $this->assertSame('masculino', $payload['gender']);
        $this->assertTrue($payload['is_active']);
        $this->assertNull($payload['termination_at']);
    }

    public function test_trunca_nombres_a_cien_caracteres(): void
    {
        $payload = (new EmployeeSyncService)->payloadFor($this->profile([
            'first_name' => str_repeat('A', 80),
            'second_name' => str_repeat('B', 80),
            'first_surname' => str_repeat('C', 80),
            'second_surname' => str_repeat('D', 80),
        ]));

        $this->assertSame(100, mb_strlen($payload['first_name']));
        $this->assertSame(100, mb_strlen($payload['last_name']));
    }

    public function test_employment_status_define_is_active(): void
    {
        $service = new EmployeeSyncService;

        $activo = $service->payloadFor($this->profile(['employment_status' => 'activo']));
        $inactivo = $service->payloadFor($this->profile([
            'employment_status' => 'desvinculado',
            'termination_date' => '2024-02-01',
        ]));

        $this->assertTrue($activo['is_active']);
        $this->assertFalse($inactivo['is_active']);
        $this->assertSame('2024-02-01', $inactivo['termination_at']);
    }

    public function test_document_types_viajan_crudos_porque_el_consumidor_los_colapsa(): void
    {
        $service = new EmployeeSyncService;

        $this->assertSame('C', $service->payloadFor($this->profile(['document_type' => 'C']))['document_type']);
        $this->assertSame('TARJETA EXTRANJERIA', $service->payloadFor($this->profile(['document_type' => ' tarjeta   extranjeria ']))['document_type']);
        $this->assertNull($service->payloadFor($this->profile(['document_type' => null]))['document_type']);
    }

    public function test_genero_y_ciudades_invalidas_quedan_en_null(): void
    {
        $payload = (new EmployeeSyncService)->payloadFor($this->profile([
            'sex' => 'X',
            'residence_city_code' => 'ABC',
            'work_city_code' => '',
        ]));

        $this->assertNull($payload['gender']);
        $this->assertNull($payload['residence_municipality_code']);
        $this->assertNull($payload['municipality_code']);
    }

    public function test_perfil_sin_documento_no_genera_payload(): void
    {
        $this->assertNull((new EmployeeSyncService)->payloadFor($this->profile(['document_number' => 'ABC'])));
    }
}
