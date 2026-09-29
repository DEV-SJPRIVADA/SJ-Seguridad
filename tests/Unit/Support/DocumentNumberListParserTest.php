<?php

namespace Tests\Unit\Support;

use App\Support\DocumentNumberListParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentNumberListParserTest extends TestCase
{
    #[Test]
    public function it_parses_comma_newline_and_semicolon_separators(): void
    {
        $parser = new DocumentNumberListParser;

        $docs = $parser->parse("111\n222,333;444");

        $this->assertSame(['111', '222', '333', '444'], $docs);
    }

    #[Test]
    public function it_deduplicates_by_normalized_digits_and_caps_at_max(): void
    {
        $parser = new DocumentNumberListParser;

        $docs = $parser->parse('1.111,1111,2222,3333', 2);

        $this->assertCount(2, $docs);
        $this->assertTrue($parser->matches('1111', $docs));
        $this->assertFalse($parser->matches('3333', $docs));
    }

    #[Test]
    public function it_builds_lookup_and_query_values(): void
    {
        $parser = new DocumentNumberListParser;
        $docs = $parser->fromInput(['10.20', '30']);

        $this->assertContains('1020', $parser->lookupValues($docs));
        $this->assertSame('10.20,30', $parser->toQueryValue($docs));
    }
}
