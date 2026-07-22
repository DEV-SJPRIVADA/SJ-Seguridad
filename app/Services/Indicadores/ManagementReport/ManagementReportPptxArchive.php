<?php

namespace App\Services\Indicadores\ManagementReport;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

class ManagementReportPptxArchive
{
    public function extract(string $templatePath): string
    {
        $workDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fo-gi-39-'.uniqid('', true);
        if (! mkdir($workDir) && ! is_dir($workDir)) {
            throw new RuntimeException('No se pudo crear el directorio temporal del informe.');
        }

        $zip = new ZipArchive;
        if ($zip->open($templatePath) !== true) {
            throw new RuntimeException('No se pudo abrir la plantilla PPTX.');
        }

        if (! $zip->extractTo($workDir)) {
            $zip->close();
            $this->deleteDirectory($workDir);
            throw new RuntimeException('No se pudo extraer la plantilla PPTX.');
        }

        $zip->close();

        return $workDir;
    }

    public function pack(string $workDir): string
    {
        $outputPath = tempnam(sys_get_temp_dir(), 'fo-gi-39-out-');
        if ($outputPath === false) {
            throw new RuntimeException('No se pudo preparar el archivo de salida.');
        }

        @unlink($outputPath);
        $outputPath .= '.pptx';

        $zip = new ZipArchive;
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el PPTX de salida.');
        }

        /** @var SplFileInfo $file */
        foreach ($this->filesInDirectory($workDir) as $file) {
            $relative = substr($file->getPathname(), strlen($workDir) + 1);
            $relative = str_replace('\\', '/', $relative);
            $zip->addFile($file->getPathname(), $relative);
        }

        $zip->close();

        return $outputPath;
    }

    public function read(string $workDir, string $relativePath): string
    {
        $path = $this->path($workDir, $relativePath);
        if (! is_file($path)) {
            throw new RuntimeException('No se encontro '.$relativePath.' en la plantilla.');
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('No se pudo leer '.$relativePath.'.');
        }

        return $content;
    }

    public function write(string $workDir, string $relativePath, string $content): void
    {
        $path = $this->path($workDir, $relativePath);
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException('No se pudo crear '.$directory.'.');
        }

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('No se pudo escribir '.$relativePath.'.');
        }
    }

    public function exists(string $workDir, string $relativePath): bool
    {
        return is_file($this->path($workDir, $relativePath));
    }

    public function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($directory);
    }

    private function path(string $workDir, string $relativePath): string
    {
        return $workDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * @return iterable<int, SplFileInfo>
     */
    private function filesInDirectory(string $directory): iterable
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );
    }
}
