<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi;

use setasign\Fpdi\Fpdi;

/**
 * FPDI-Plus main class.
 *
 * Extends the FPDI import engine (which itself extends FPDF) and adds
 * higher-level, batch-oriented helpers for common PDF manipulation tasks.
 *
 * Base flow inherited from FPDI/FPDF:
 *   setSourceFile() -> importPage() -> AddPage() + useTemplate() -> Output()
 *
 * @see https://github.com/Setasign/FPDI
 */
class Pdf extends Fpdi
{
    /**
     * Import every page of a source PDF in one call.
     *
     * Does not place the pages — it only imports them and returns the template
     * ids so the caller decides layout. Template ids are valid for the source
     * file that was active when they were created.
     *
     * @param string $file Path to the source PDF.
     * @return array<int, int> Map of 1-based page number => template id.
     */
    public function importAllPages(string $file): array
    {
        $pageCount = $this->setSourceFile($file);
        $templateIds = [];
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateIds[$pageNo] = $this->importPage($pageNo);
        }

        return $templateIds;
    }

    /**
     * Merge every page of each given source PDF into a single output file,
     * preserving each source page's own size and orientation.
     *
     * @param string[] $files       Ordered list of source PDF paths.
     * @param string   $outputPath  Destination path for the merged PDF.
     * @return int Total number of pages written.
     */
    public static function merge(array $files, string $outputPath): int
    {
        $pdf = new self();
        $totalPages = 0;

        foreach ($files as $file) {
            $pageCount = $pdf->setSourceFile($file);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
            $totalPages += $pageCount;
        }

        $pdf->Output('F', $outputPath);

        return $totalPages;
    }
}
