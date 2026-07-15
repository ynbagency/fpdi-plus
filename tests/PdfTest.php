<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi\Tests;

use YnbAgency\Fpdi\Exception\UnsupportedPdfException;
use YnbAgency\Fpdi\Pdf;
use YnbAgency\Fpdi\Tests\Fixtures\RecordingPdf;

final class PdfTest extends PdfTestCase
{
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
        self::assertStringStartsWith('%PDF-', $this->readBytes($output));
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
}
