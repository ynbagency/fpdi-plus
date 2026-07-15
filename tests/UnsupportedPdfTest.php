<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi\Tests;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use YnbAgency\Fpdi\Exception\ExceptionInterface;
use YnbAgency\Fpdi\Exception\UnsupportedPdfException;
use YnbAgency\Fpdi\Pdf;

/**
 * Covers the try/catch that translates parser failures into a package-owned
 * exception — the code most likely to fire on real-world input, and one FPDF
 * cannot generate a fixture for (so a small committed fixture is used).
 */
final class UnsupportedPdfTest extends PdfTestCase
{
    private const COMPRESSED_XREF = __DIR__ . '/resources/compressed-xref.pdf';

    public function testCompressedCrossReferencePdfIsRejected(): void
    {
        self::assertFileExists(self::COMPRESSED_XREF, 'fixture must exist');

        try {
            (new Pdf())->importAllPages(self::COMPRESSED_XREF);
            self::fail('Expected UnsupportedPdfException for a compressed-xref PDF.');
        } catch (UnsupportedPdfException $e) {
            // Package-owned type, and the original setasign cause is preserved.
            self::assertInstanceOf(ExceptionInterface::class, $e);
            self::assertInstanceOf(CrossReferenceException::class, $e->getPrevious());
            self::assertStringContainsString('fpdi-pdf-parser', $e->getMessage());
        }
    }

    public function testMalformedPdfIsRejected(): void
    {
        $path = $this->tempPath();
        file_put_contents($path, "%PDF-1.7\nnot a real pdf body\n");

        $this->expectException(UnsupportedPdfException::class);

        (new Pdf())->importAllPages($path);
    }

    public function testMergeSurfacesUnsupportedSource(): void
    {
        $good = $this->makeSourcePdf(1);

        $this->expectException(UnsupportedPdfException::class);

        Pdf::merge([$good, self::COMPRESSED_XREF], $this->tempPath());
    }
}
