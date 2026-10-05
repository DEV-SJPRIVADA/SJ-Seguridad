<?php

namespace Tests\Unit\Support;

use App\Support\WordTempDirectory;
use Tests\TestCase;

class WordTempDirectoryTest extends TestCase
{
    public function test_resolves_writable_directory(): void
    {
        $path = WordTempDirectory::path();

        $this->assertDirectoryExists($path);
        $this->assertTrue(is_writable($path));

        $work = WordTempDirectory::uniqueDir('test-');
        $this->assertDirectoryExists($work);
        $this->assertTrue(is_writable($work));

        @rmdir($work);
    }
}
