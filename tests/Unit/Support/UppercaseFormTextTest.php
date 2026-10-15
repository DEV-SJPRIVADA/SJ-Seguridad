<?php

namespace Tests\Unit\Support;

use App\Support\UppercaseFormText;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UppercaseFormTextTest extends TestCase
{
    #[Test]
    public function it_uppercases_nested_text_and_skips_email_codes_and_status(): void
    {
        $result = UppercaseFormText::apply([
            'full_name' => 'Pérez Ana',
            'email' => 'ana.perez@example.com',
            'status' => 'solicitada',
            'sex' => 'masculino',
            'position_code' => 'vig01',
            'birth_date' => '1995-04-15',
            'payroll_extra' => [
                'military_book' => 'Libreta Abc',
                'ccf_code' => 'ccf1',
            ],
        ]);

        $this->assertSame('PÉREZ ANA', $result['full_name']);
        $this->assertSame('ana.perez@example.com', $result['email']);
        $this->assertSame('solicitada', $result['status']);
        $this->assertSame('masculino', $result['sex']);
        $this->assertSame('vig01', $result['position_code']);
        $this->assertSame('1995-04-15', $result['birth_date']);
        $this->assertSame('LIBRETA ABC', $result['payroll_extra']['military_book']);
        $this->assertSame('ccf1', $result['payroll_extra']['ccf_code']);
    }
}
