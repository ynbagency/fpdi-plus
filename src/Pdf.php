<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi;

use YnbAgency\Fpdi\Engine\Fpdi;
use YnbAgency\Fpdi\Engine\PdfParser\CrossReference\CrossReferenceException;
use YnbAgency\Fpdi\Engine\PdfParser\PdfParserException;
use YnbAgency\Fpdi\Engine\PdfParser\StreamReader;
use YnbAgency\Fpdi\Exception\UnsupportedPdfException;

/**
 * FPDI-Plus main class.
 *
 * Extends the FPDI import engine (which itself extends FPDF) and adds
 * higher-level, batch-oriented helpers for common PDF manipulation tasks:
 * merge, extract, split and watermark, plus in-memory (string) input and
 * string output.
 *
 * Base flow inherited from FPDI/FPDF:
 *   setSourceFile() -> importPage() -> AddPage() + useTemplate() -> Output()
 *
 * Limitations: the bundled parser cannot read encrypted PDFs or PDFs that use a
 * compressed cross-reference stream (common in PDF 1.5+ output from
 * Word/LibreOffice/Ghostscript). Those raise {@see UnsupportedPdfException}.
 *
 * Memory: the batch helpers (merge, extract, watermark, appendFile, appendString)
 * hold every imported page as a form XObject until save()/render() is called;
 * for very large inputs, process in chunks.
 *
 * Subclasses must keep a constructor compatible with FPDF's (all-optional
 * arguments) so that the static factories' `new static()` stays safe.
 *
 * The import/generation engine is a vendored fork of FPDI + FPDF under
 * {@see \YnbAgency\Fpdi\Engine}; see src/Engine for the retained upstream notices.
 *
 * @phpstan-consistent-constructor
 */
class Pdf extends Fpdi
{
    /**
     * Import every page of a source PDF in one call.
     *
     * Does not place the pages — it only imports them and returns the template
     * ids so the caller decides layout. Note that this holds one template per
     * page in memory; for very large PDFs, import selectively.
     *
     * @param string $file Path to the source PDF.
     * @return array<int, string> Map of 1-based page number => template id.
     * @throws UnsupportedPdfException If the file is missing/unreadable or unsupported.
     */
    public function importAllPages(string $file): array
    {
        $pageCount = $this->openFile($file);
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
        $pageCount = $this->openFile($file);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $this->placeCurrentSourcePage($pageNo);
        }

        return $pageCount;
    }

    /**
     * Append every page of an in-memory PDF (raw bytes) to this document.
     * Useful for uploaded files that never touch disk.
     *
     * @param string $content The raw PDF content.
     * @return int Number of pages appended.
     * @throws UnsupportedPdfException If the content is not a supported PDF.
     */
    public function appendString(string $content): int
    {
        $pageCount = $this->open(StreamReader::createByString($content));
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $this->placeCurrentSourcePage($pageNo);
        }

        return $pageCount;
    }

    /**
     * Append only the given pages of a source PDF, in the given order.
     *
     * @param string        $file  Path to the source PDF.
     * @param iterable<int> $pages 1-based page numbers to append.
     * @return int Number of pages appended.
     * @throws UnsupportedPdfException If the file is missing/unreadable or unsupported.
     * @throws \OutOfRangeException    If a page number is outside the source.
     */
    public function appendPages(string $file, iterable $pages): int
    {
        $pageCount = $this->openFile($file);
        $appended = 0;
        foreach ($pages as $pageNo) {
            if ($pageNo < 1 || $pageNo > $pageCount) {
                throw new \OutOfRangeException(
                    sprintf('Page %d is out of range; "%s" has %d page(s).', $pageNo, $file, $pageCount)
                );
            }
            $this->placeCurrentSourcePage($pageNo);
            $appended++;
        }

        return $appended;
    }

    /**
     * Return the finished PDF as a binary string (does not write to disk).
     */
    public function render(): string
    {
        $pdf = $this->Output('S');

        return is_string($pdf) ? $pdf : '';
    }

    /**
     * Write the finished PDF to a file.
     */
    public function save(string $path): void
    {
        $this->Output('F', $path);
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
        $pdf->save($outputPath);

        return $totalPages;
    }

    /**
     * Extract the given pages of a source PDF into a new file.
     *
     * @param string        $file       Path to the source PDF.
     * @param iterable<int> $pages      1-based page numbers to extract, in order.
     * @param string        $outputPath Destination path.
     * @return int Number of pages written.
     * @throws \InvalidArgumentException If no pages are selected.
     * @throws \OutOfRangeException      If a page number is outside the source.
     * @throws UnsupportedPdfException   If the source is missing/unreadable or unsupported.
     */
    public static function extract(string $file, iterable $pages, string $outputPath): int
    {
        $pdf = static::make();
        $written = $pdf->appendPages($file, $pages);
        if ($written === 0) {
            throw new \InvalidArgumentException('No pages selected to extract.');
        }
        $pdf->save($outputPath);

        return $written;
    }

    /**
     * Split a source PDF into one file per page.
     *
     * @param string $file          Path to the source PDF.
     * @param string $outputPattern printf pattern taking the page number,
     *                              e.g. "page-%02d.pdf".
     * @return int Number of files written (one per page).
     * @throws \InvalidArgumentException If the pattern has no integer placeholder.
     * @throws UnsupportedPdfException   If the source is missing/unreadable or unsupported.
     */
    public static function split(string $file, string $outputPattern): int
    {
        try {
            $probeOne = sprintf($outputPattern, 1);
            $probeTwo = sprintf($outputPattern, 2);
        } catch (\ArgumentCountError $e) {
            throw new \InvalidArgumentException(
                'Output pattern must contain exactly one integer placeholder, e.g. "page-%02d.pdf".',
                0,
                $e
            );
        }
        if ($probeOne === $probeTwo) {
            throw new \InvalidArgumentException(
                'Output pattern must contain a varying integer placeholder, e.g. "page-%02d.pdf".'
            );
        }

        $pageCount = static::make()->openFile($file);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $page = static::make();
            $page->appendPages($file, [$pageNo]);
            $page->save(sprintf($outputPattern, $pageNo));
        }

        return $pageCount;
    }

    /**
     * Stamp one page of a stamp PDF over every page of a source PDF, scaled to
     * cover each page. The stamp is drawn on top of the source content.
     *
     * @param string $source     Path to the PDF to stamp.
     * @param string $stampFile  Path to the PDF holding the stamp/watermark.
     * @param string $outputPath Destination path.
     * @param int    $stampPage  1-based page of $stampFile to use as the stamp.
     * @return int Number of pages written.
     * @throws \OutOfRangeException    If $stampPage is outside $stampFile.
     * @throws UnsupportedPdfException If either file is missing/unreadable or unsupported.
     */
    public static function watermark(string $source, string $stampFile, string $outputPath, int $stampPage = 1): int
    {
        $pdf = static::make();

        $stampCount = $pdf->openFile($stampFile);
        if ($stampPage < 1 || $stampPage > $stampCount) {
            throw new \OutOfRangeException(
                sprintf('Stamp page %d is out of range; "%s" has %d page(s).', $stampPage, $stampFile, $stampCount)
            );
        }
        $stampTemplate = $pdf->importPage($stampPage);

        $pageCount = $pdf->openFile($source);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $size = $pdf->placeCurrentSourcePage($pageNo);
            $pdf->useTemplate($stampTemplate, 0, 0, $size['width'], $size['height']);
        }
        $pdf->save($outputPath);

        return $pageCount;
    }

    /**
     * Factory used by the static helpers so late static binding lets subclasses
     * control instantiation (constructor args, overridden behaviour).
     */
    protected static function make(): static
    {
        return new static();
    }

    /**
     * Import the current source's page, add an output page of the same size and
     * orientation, and place the imported page on it.
     *
     * @return array{width: float, height: float, orientation: string} The placed page's size.
     * @throws UnsupportedPdfException If the page size cannot be determined.
     */
    private function placeCurrentSourcePage(int $pageNo): array
    {
        $templateId = $this->importPage($pageNo);
        $size = $this->getTemplateSize($templateId);
        if (!is_array($size)) {
            throw new UnsupportedPdfException(
                sprintf('Could not determine the size of page %d.', $pageNo)
            );
        }

        $rawWidth = $size['width'] ?? null;
        $rawHeight = $size['height'] ?? null;
        if (!is_numeric($rawWidth) || !is_numeric($rawHeight)) {
            throw new UnsupportedPdfException(
                sprintf('Could not determine the size of page %d.', $pageNo)
            );
        }
        $width = (float) $rawWidth;
        $height = (float) $rawHeight;
        $orientation = is_string($size['orientation'] ?? null)
            ? $size['orientation']
            : ($width > $height ? 'L' : 'P');

        $this->AddPage($orientation, [$width, $height]);
        $this->useTemplate($templateId);

        return ['width' => $width, 'height' => $height, 'orientation' => $orientation];
    }

    /**
     * Validate a path and open it as the source PDF.
     *
     * @return int The page count of the source.
     * @throws UnsupportedPdfException
     */
    private function openFile(string $file): int
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new UnsupportedPdfException(
                sprintf('Source PDF not found or not readable: "%s".', $file)
            );
        }

        return $this->open($file);
    }

    /**
     * Open a source (path or in-memory reader), translating parser failures
     * into a package-owned {@see UnsupportedPdfException}.
     *
     * @param string|StreamReader $source
     * @return int The page count of the source.
     * @throws UnsupportedPdfException
     */
    private function open(string|StreamReader $source): int
    {
        try {
            return $this->setSourceFile($source);
        } catch (CrossReferenceException $e) {
            throw new UnsupportedPdfException(
                'This PDF is encrypted or uses a compressed cross-reference stream, '
                . 'which the bundled parser does not support.',
                $e->getCode(),
                $e
            );
        } catch (PdfParserException $e) {
            throw new UnsupportedPdfException(
                sprintf('Could not parse the PDF: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
}
