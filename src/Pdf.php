<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\PdfParserException;
use YnbAgency\Fpdi\Exception\UnsupportedPdfException;

/**
 * FPDI-Plus main class.
 *
 * Extends the FPDI import engine (which itself extends FPDF) and adds
 * higher-level, batch-oriented helpers for common PDF manipulation tasks.
 *
 * Base flow inherited from FPDI/FPDF:
 *   setSourceFile() -> importPage() -> AddPage() + useTemplate() -> Output()
 *
 * Limitations: the bundled (free) FPDI parser cannot read encrypted PDFs or
 * PDFs that use a compressed cross-reference stream (common in PDF 1.5+ output
 * from Word/LibreOffice/Ghostscript). Those raise {@see UnsupportedPdfException};
 * install setasign/fpdi-pdf-parser to handle them.
 *
 * Subclasses must keep a constructor compatible with FPDF's (all-optional
 * arguments) so that {@see merge()}'s `new static()` stays safe.
 *
 * @phpstan-consistent-constructor
 * @see https://github.com/Setasign/FPDI
 */
class Pdf extends Fpdi
{
    /**
     * Import every page of a source PDF in one call.
     *
     * Does not place the pages — it only imports them and returns the template
     * ids so the caller decides layout. Template ids are valid for the source
     * file that was active when they were created. Note that this holds one
     * template per page in memory; for very large PDFs, import selectively.
     *
     * @param string $file Path to the source PDF.
     * @return array<int, string> Map of 1-based page number => template id.
     * @throws UnsupportedPdfException If the file is missing/unreadable or unsupported.
     */
    public function importAllPages(string $file): array
    {
        $pageCount = $this->openSource($file);
        $templateIds = [];
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateIds[$pageNo] = $this->importPage($pageNo);
        }

        return $templateIds;
    }

    /**
     * Append every page of a source PDF to this document, preserving each
     * page's own size and orientation.
     *
     * @param string $file Path to the source PDF.
     * @return int Number of pages appended.
     * @throws UnsupportedPdfException If the file is missing/unreadable or unsupported.
     */
    public function appendFile(string $file): int
    {
        $pageCount = $this->openSource($file);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $this->importPage($pageNo);
            $size = $this->getTemplateSize($templateId);
            if (!is_array($size)) {
                throw new UnsupportedPdfException(
                    sprintf('Could not determine the size of page %d in "%s".', $pageNo, $file)
                );
            }
            $this->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $this->useTemplate($templateId);
        }

        return $pageCount;
    }

    /**
     * Merge every page of each given source PDF into a single output file,
     * preserving each source page's own size and orientation.
     *
     * @param string[] $files      Ordered, non-empty list of source PDF paths.
     * @param string   $outputPath Destination path for the merged PDF.
     * @return int Total number of pages written.
     * @throws \InvalidArgumentException If $files is empty.
     * @throws UnsupportedPdfException   If any source is missing/unreadable or unsupported.
     */
    public static function merge(array $files, string $outputPath): int
    {
        if ($files === []) {
            throw new \InvalidArgumentException('No files to merge.');
        }

        $pdf = static::make();
        $totalPages = 0;
        foreach ($files as $file) {
            $totalPages += $pdf->appendFile($file);
        }

        $pdf->Output('F', $outputPath);

        return $totalPages;
    }

    /**
     * Factory used by {@see merge()} so late static binding lets subclasses
     * control instantiation (constructor args, overridden behaviour).
     */
    protected static function make(): static
    {
        return new static();
    }

    /**
     * Validate and open a source PDF, translating parser failures into a
     * package-owned {@see UnsupportedPdfException}.
     *
     * @return int The page count of the source.
     * @throws UnsupportedPdfException
     */
    private function openSource(string $file): int
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new UnsupportedPdfException(
                sprintf('Source PDF not found or not readable: "%s".', $file)
            );
        }

        try {
            return $this->setSourceFile($file);
        } catch (CrossReferenceException $e) {
            throw new UnsupportedPdfException(
                sprintf(
                    'Cannot read "%s": the bundled FPDI parser does not support this PDF '
                    . '(it is encrypted or uses a compressed cross-reference stream). '
                    . 'Install setasign/fpdi-pdf-parser to handle it.',
                    $file
                ),
                $e->getCode(),
                $e
            );
        } catch (PdfParserException $e) {
            throw new UnsupportedPdfException(
                sprintf('Cannot parse "%s": %s', $file, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
}
