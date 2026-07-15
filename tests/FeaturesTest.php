<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi\Tests;

use YnbAgency\Fpdi\Pdf;

final class FeaturesTest extends PdfTestCase
{
    public function testExtractWritesOnlySelectedPagesInOrder(): void
    {
        $source = $this->makeSourcePdf(5);
        $output = $this->tempPath();

        $written = Pdf::extract($source, [4, 2], $output);

        self::assertSame(2, $written);
        self::assertSame(2, $this->pageCountOf($output));
        self::assertStringStartsWith('%PDF-', $this->readBytes($output));
    }

    public function testExtractRejectsOutOfRangePage(): void
    {
        $source = $this->makeSourcePdf(3);

        $this->expectException(\OutOfRangeException::class);

        Pdf::extract($source, [1, 9], $this->tempPath());
    }

    public function testExtractRejectsEmptySelection(): void
    {
        $source = $this->makeSourcePdf(3);

        $this->expectException(\InvalidArgumentException::class);

        Pdf::extract($source, [], $this->tempPath());
    }

    public function testSplitWritesOneFilePerPage(): void
    {
        $source = $this->makeSourcePdf(3);
        $pattern = $this->tempPath() . '-%d.pdf';

        $count = Pdf::split($source, $pattern);

        self::assertSame(3, $count);
        for ($pageNo = 1; $pageNo <= 3; $pageNo++) {
            $file = $this->track(sprintf($pattern, $pageNo));
            self::assertFileExists($file);
            self::assertSame(1, $this->pageCountOf($file));
        }
    }

    public function testSplitRejectsPatternWithoutPlaceholder(): void
    {
        $source = $this->makeSourcePdf(2);

        $this->expectException(\InvalidArgumentException::class);

        Pdf::split($source, $this->tempPath() . '-no-placeholder.pdf');
    }

    public function testWatermarkStampsEveryPage(): void
    {
        $source = $this->makeSourcePdf(3);
        $stamp = $this->makeSourcePdf(1);
        $output = $this->tempPath();

        $written = Pdf::watermark($source, $stamp, $output);

        self::assertSame(3, $written);
        self::assertSame(3, $this->pageCountOf($output));
        self::assertStringStartsWith('%PDF-', $this->readBytes($output));
    }

    public function testWatermarkRejectsOutOfRangeStampPage(): void
    {
        $source = $this->makeSourcePdf(2);
        $stamp = $this->makeSourcePdf(1);

        $this->expectException(\OutOfRangeException::class);

        Pdf::watermark($source, $stamp, $this->tempPath(), 5);
    }

    public function testAppendStringImportsInMemoryPdf(): void
    {
        $source = $this->makeSourcePdf(2);

        $pdf = new Pdf();
        $appended = $pdf->appendString($this->readBytes($source));

        self::assertSame(2, $appended);
        self::assertStringStartsWith('%PDF-', $pdf->render());
    }

    public function testAppendPagesAppendsSubsetInGivenOrder(): void
    {
        $source = $this->makeSourcePdf(4);

        $pdf = new Pdf();
        $appended = $pdf->appendPages($source, [3, 1]);

        self::assertSame(2, $appended);
        $output = $this->tempPath();
        $pdf->save($output);
        self::assertSame(2, $this->pageCountOf($output));
    }

    public function testRenderReturnsNonEmptyPdfString(): void
    {
        $source = $this->makeSourcePdf(1);

        $pdf = new Pdf();
        $pdf->appendFile($source);
        $rendered = $pdf->render();

        self::assertStringStartsWith('%PDF-', $rendered);
        self::assertGreaterThan(0, strlen($rendered));
    }
}
