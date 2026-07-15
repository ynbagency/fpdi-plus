<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi\Tests;

use PHPUnit\Framework\TestCase;
use YnbAgency\Fpdi\Pdf;

final class PdfTest extends TestCase
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

    public function testImportAllPagesReturnsOneTemplateIdPerPage(): void
    {
        $source = $this->makeSourcePdf(3);

        $pdf = new Pdf();
        $templateIds = $pdf->importAllPages($source);

        self::assertCount(3, $templateIds);
        self::assertSame([1, 2, 3], array_keys($templateIds));
    }

    public function testMergeCombinesAllPagesIntoValidPdf(): void
    {
        $first = $this->makeSourcePdf(2);
        $second = $this->makeSourcePdf(1);
        $output = $this->tempPath();

        $totalPages = Pdf::merge([$first, $second], $output);

        self::assertSame(3, $totalPages);
        self::assertFileExists($output);
        self::assertGreaterThan(0, (int) filesize($output));
        self::assertStringStartsWith('%PDF-', (string) file_get_contents($output));
    }

    /**
     * Generate a real multi-page source PDF using plain FPDF, so the suite
     * needs no committed binary fixtures.
     */
    private function makeSourcePdf(int $pages): string
    {
        $fpdf = new \FPDF();
        for ($i = 1; $i <= $pages; $i++) {
            $fpdf->AddPage();
            $fpdf->SetFont('Arial', 'B', 16);
            $fpdf->Cell(40, 10, 'Page ' . $i);
        }

        $path = $this->tempPath();
        $fpdf->Output('F', $path);

        return $path;
    }

    private function tempPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fpdiplus_');
        $this->tempFiles[] = $path;

        return $path;
    }
}
