<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi\Tests;

use PHPUnit\Framework\TestCase;
use YnbAgency\Fpdi\Exception\UnsupportedPdfException;
use YnbAgency\Fpdi\Pdf;
use YnbAgency\Fpdi\Tests\Fixtures\RecordingPdf;

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
        self::assertContainsOnly('string', $templateIds);
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
     * The whole point of merge(): each source page keeps its own size and
     * orientation. Without this, a regression that squashed every page to one
     * size would still pass the page-count/header checks above.
     */
    public function testMergePreservesPerPageSize(): void
    {
        $portraitA4 = $this->makeSourcePdf(1, 'P', 'A4');   // 210 x 297 mm
        $landscapeA5 = $this->makeSourcePdf(1, 'L', 'A5');  // 210 x 148 mm
        $output = $this->tempPath();

        self::assertSame(2, Pdf::merge([$portraitA4, $landscapeA5], $output));

        $reader = new Pdf();
        self::assertSame(2, $reader->setSourceFile($output));

        $page1 = $this->pageSize($reader, 1);
        $page2 = $this->pageSize($reader, 2);

        self::assertSame('P', $page1['orientation']);
        self::assertSame('L', $page2['orientation']);
        // Portrait A4 is taller than landscape A5 — proves sizes were not unified.
        self::assertGreaterThan($page2['height'], $page1['height']);
    }

    public function testMergeThrowsOnEmptyFileList(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Pdf::merge([], $this->tempPath());
    }

    public function testImportAllPagesThrowsOnMissingFile(): void
    {
        $this->expectException(UnsupportedPdfException::class);

        (new Pdf())->importAllPages($this->missingPath());
    }

    public function testMergeThrowsOnMissingFile(): void
    {
        $this->expectException(UnsupportedPdfException::class);

        Pdf::merge([$this->missingPath()], $this->tempPath());
    }

    public function testMergeInstantiatesViaLateStaticBinding(): void
    {
        RecordingPdf::$madeViaOverride = false;
        $source = $this->makeSourcePdf(1);

        RecordingPdf::merge([$source], $this->tempPath());

        self::assertTrue(
            RecordingPdf::$madeViaOverride,
            'merge() must build the document via new static() so subclasses stay in control.'
        );
    }

    /**
     * Generate a real source PDF using plain FPDF, so the suite needs no
     * committed binary fixtures.
     */
    private function makeSourcePdf(int $pages, string $orientation = 'P', string $size = 'A4'): string
    {
        $fpdf = new \FPDF($orientation, 'mm', $size);
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
     * Read a page's size back from a rendered PDF.
     *
     * @return array{width: float, height: float, orientation: string}
     */
    private function pageSize(Pdf $pdf, int $pageNo): array
    {
        $size = $pdf->getTemplateSize($pdf->importPage($pageNo));
        if (!is_array($size)) {
            self::fail('Expected a template size array.');
        }
        /** @var array{width: float, height: float, orientation: string} $size */
        return $size;
    }

    private function tempPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fpdiplus_');
        if ($path === false) {
            self::fail('Unable to create a temporary file.');
        }
        $this->tempFiles[] = $path;

        return $path;
    }

    /** A registered temp path that is guaranteed not to exist. */
    private function missingPath(): string
    {
        $path = $this->tempPath();
        @unlink($path);

        return $path;
    }
}
