<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi\Tests;

use YnbAgency\Fpdi\Pdf;

final class FeaturesTest extends PdfTestCase
{
    public function testExtractWritesOnlySelectedPagesInOrder(): void
    {
        // Distinct sizes so selection AND order are actually observable.
        $source = $this->makeMixedSizePdf([
            ['P', 'A4'],     // page 1 -> ~297 mm tall
            ['L', 'A5'],     // page 2
            ['P', 'A3'],     // page 3 -> ~420 mm tall (tallest)
            ['L', 'Letter'], // page 4
        ]);
        $output = $this->tempPath();

        $written = Pdf::extract($source, [3, 1], $output);

        self::assertSame(2, $written);
        self::assertSame(2, $this->pageCountOf($output));

        $reader = new Pdf();
        $reader->setSourceFile($output);
        $out1 = $this->pageSize($reader, 1); // must be source page 3 (A3)
        $out2 = $this->pageSize($reader, 2); // must be source page 1 (A4)

        self::assertGreaterThan(400.0, $out1['height'], 'first output page should be the A3 (page 3)');
        self::assertGreaterThan(290.0, $out2['height']);
        self::assertLessThan(310.0, $out2['height'], 'second output page should be the A4 (page 1)');
        self::assertGreaterThan($out2['height'], $out1['height'], 'order [3,1] must be preserved');
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

    public function testSplitRejectsLiteralPercentPattern(): void
    {
        // "%%d" is a literal "%d" — it would collide every page onto one file.
        $source = $this->makeSourcePdf(2);

        $this->expectException(\InvalidArgumentException::class);

        Pdf::split($source, $this->tempPath() . '-%%d.pdf');
    }

    public function testSplitRejectsMultiPlaceholderPattern(): void
    {
        $source = $this->makeSourcePdf(2);

        $this->expectException(\InvalidArgumentException::class);

        Pdf::split($source, $this->tempPath() . '-%d-%d.pdf');
    }

    public function testWatermarkStampsEveryPage(): void
    {
        $source = $this->makeSourcePdf(3);
        $stamp = $this->makeSourcePdf(1);

        $plain = $this->tempPath();
        Pdf::merge([$source], $plain);

        $stamped = $this->tempPath();
        $written = Pdf::watermark($source, $stamp, $stamped);

        self::assertSame(3, $written);
        self::assertSame(3, $this->pageCountOf($stamped));

        // The stamp must actually be placed: the watermarked output carries more
        // form XObjects than a plain copy of the same source.
        $plainForms = substr_count($this->readBytes($plain), '/Subtype /Form');
        $stampedForms = substr_count($this->readBytes($stamped), '/Subtype /Form');
        self::assertGreaterThan($plainForms, $stampedForms, 'watermark must add the stamp XObject');
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
        $source = $this->makeMixedSizePdf([
            ['P', 'A4'],  // page 1
            ['L', 'A5'],  // page 2
            ['P', 'A3'],  // page 3 (tallest)
        ]);

        $pdf = new Pdf();
        $appended = $pdf->appendPages($source, [3, 1]);
        self::assertSame(2, $appended);

        $output = $this->tempPath();
        $pdf->save($output);
        self::assertSame(2, $this->pageCountOf($output));

        $reader = new Pdf();
        $reader->setSourceFile($output);
        $out1 = $this->pageSize($reader, 1); // source page 3 (A3)
        $out2 = $this->pageSize($reader, 2); // source page 1 (A4)
        self::assertGreaterThan($out2['height'], $out1['height'], 'order [3,1] must be preserved');
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
