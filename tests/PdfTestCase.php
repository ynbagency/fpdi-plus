<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi\Tests;

use PHPUnit\Framework\TestCase;
use YnbAgency\Fpdi\Pdf;

/**
 * Base test case with helpers for generating and inspecting real PDFs, so the
 * suite needs no committed binary fixtures for the supported (FPDF-generatable)
 * cases.
 */
abstract class PdfTestCase extends TestCase
{
    /** @var string[] Paths to clean up after each test. */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    /** Register a path for cleanup and return it. */
    protected function track(string $path): string
    {
        $this->tempFiles[] = $path;

        return $path;
    }

    protected function tempPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fpdiplus_');
        if ($path === false) {
            self::fail('Unable to create a temporary file.');
        }

        return $this->track($path);
    }

    /** A registered temp path that is guaranteed not to exist. */
    protected function missingPath(): string
    {
        $path = $this->tempPath();
        @unlink($path);

        return $path;
    }

    /**
     * Generate a real source PDF using plain FPDF.
     */
    protected function makeSourcePdf(int $pages, string $orientation = 'P', string $size = 'A4'): string
    {
        $fpdf = new \YnbAgency\Fpdi\Engine\Fpdf($orientation, 'mm', $size);
        for ($i = 1; $i <= $pages; $i++) {
            $fpdf->AddPage();
            $fpdf->SetFont('Arial', 'B', 16);
            $fpdf->Cell(40, 10, 'Page ' . $i);
        }

        $path = $this->tempPath();
        $fpdf->Output('F', $path);

        return $path;
    }

    /**
     * Generate a source PDF whose pages have deliberately distinct sizes, so
     * page selection and ordering can be asserted by reading sizes back.
     *
     * @param array<int, array{0: string, 1: string}> $specs [orientation, size] per page.
     */
    protected function makeMixedSizePdf(array $specs): string
    {
        $fpdf = new \YnbAgency\Fpdi\Engine\Fpdf('P', 'mm', 'A4');
        foreach ($specs as $i => [$orientation, $size]) {
            $fpdf->AddPage($orientation, $size);
            $fpdf->SetFont('Arial', 'B', 16);
            $fpdf->Cell(40, 10, 'Page ' . ($i + 1));
        }

        $path = $this->tempPath();
        $fpdf->Output('F', $path);

        return $path;
    }

    protected function pageCountOf(string $file): int
    {
        return (new Pdf())->setSourceFile($file);
    }

    protected function readBytes(string $path): string
    {
        $bytes = file_get_contents($path);
        if ($bytes === false) {
            self::fail(sprintf('Could not read "%s".', $path));
        }

        return $bytes;
    }

    /**
     * Read a page's size back from a rendered PDF.
     *
     * @return array{width: float, height: float, orientation: string}
     */
    protected function pageSize(Pdf $pdf, int $pageNo): array
    {
        $size = $pdf->getTemplateSize($pdf->importPage($pageNo));
        if (!is_array($size)) {
            self::fail('Expected a template size array.');
        }

        $width = $size['width'] ?? null;
        $height = $size['height'] ?? null;
        $orientation = $size['orientation'] ?? null;
        if (!is_numeric($width) || !is_numeric($height) || !is_string($orientation)) {
            self::fail('Unexpected template size shape.');
        }

        return ['width' => (float) $width, 'height' => (float) $height, 'orientation' => $orientation];
    }
}
