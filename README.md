# fpdi-plus

[![CI](https://github.com/ynbagency/fpdi-plus/actions/workflows/ci.yml/badge.svg)](https://github.com/ynbagency/fpdi-plus/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/ynbagency/fpdi-plus)](https://packagist.org/packages/ynbagency/fpdi-plus)
[![PHP](https://img.shields.io/packagist/php-v/ynbagency/fpdi-plus)](https://packagist.org/packages/ynbagency/fpdi-plus)
[![License](https://img.shields.io/packagist/l/ynbagency/fpdi-plus)](LICENSE)

A small, **self-contained** PDF import and manipulation toolkit. It bundles a
namespaced fork of [FPDI](https://github.com/Setasign/FPDI) and
[FPDF](http://www.fpdf.org/) (under `YnbAgency\Fpdi\Engine`) and adds higher-level,
batch-oriented helpers — merge, extract, split, watermark, in-memory input and
string output — so common tasks are one call. **No external PDF dependencies.**

> Independent project — **not** affiliated with or endorsed by Setasign or the FPDF
> author. The bundled FPDI (MIT) and FPDF (permissive) code keeps its original
> license notices; see [LICENSE](LICENSE) and `src/Engine/`.

## Install

```bash
composer require ynbagency/fpdi-plus
```

Requires **PHP ≥ 8.1** and `ext-zlib` (standard). Nothing is pulled from Setasign —
the FPDI + FPDF engine is bundled. `ext-gd` is only needed if you embed raster images.

## Quick start

`YnbAgency\Fpdi\Pdf` **is** an FPDI instance — every FPDI/FPDF method is available,
plus the helpers below.

### Merge several PDFs (each page keeps its own size)

```php
use YnbAgency\Fpdi\Pdf;

$pages = Pdf::merge(['a.pdf', 'b.pdf', 'c.pdf'], 'merged.pdf');
echo "Wrote {$pages} pages\n";
```

### Extract a subset of pages

```php
Pdf::extract('report.pdf', [1, 3, 4], 'excerpt.pdf');   // pages, in this order
```

### Split into one file per page

```php
$count = Pdf::split('report.pdf', 'report-page-%02d.pdf');   // report-page-01.pdf, ...
```

### Watermark / stamp every page

```php
// Stamp page 1 of stamp.pdf over every page of report.pdf, scaled to fit.
Pdf::watermark('report.pdf', 'stamp.pdf', 'stamped.pdf');
```

### In-memory input and string output (no temp files)

```php
$pdf = new Pdf();
$pdf->appendString($uploadedBytes);   // append pages from raw PDF bytes
$pdf->appendPages('cover.pdf', [1]);  // then a cover page
return $pdf->render();                // get the PDF as a string
```

### Lower-level building blocks

```php
$pdf = new Pdf();
$pdf->appendFile('a.pdf');                 // append all pages, sizes preserved
$ids = $pdf->importAllPages('b.pdf');      // import only; you place them
$pdf->save('out.pdf');                     // or ->render() for a string
```

## Error handling

```php
use YnbAgency\Fpdi\Exception\ExceptionInterface;
use YnbAgency\Fpdi\Exception\UnsupportedPdfException;

try {
    Pdf::merge($files, 'out.pdf');
} catch (UnsupportedPdfException $e) {
    // missing/unreadable file, or a PDF the bundled parser can't read
    // $e->getPrevious() holds the original engine exception, if any
} catch (ExceptionInterface $e) {
    // any other fpdi-plus failure
}
```

- `merge([])` and `extract(..., [], ...)` throw `InvalidArgumentException`.
- Out-of-range page numbers throw `OutOfRangeException`.

## Limitations

The bundled parser cannot read:

- **encrypted** PDFs, or
- PDFs with a **compressed cross-reference stream** (common in PDF 1.5+ output from
  Word, LibreOffice, Ghostscript, …).

Both raise `UnsupportedPdfException`. See [SECURITY.md](SECURITY.md) for guidance on
untrusted input (memory use, decompression-bomb risk).

## The FPDI base flow

```
setSourceFile()  ->  importPage($n)  ->  AddPage() + useTemplate($id, $x, $y, $w)  ->  Output()
```

- Units default to mm; origin is top-left, y grows down.
- Template ids are strings, valid for the source file active when created.
- For very large PDFs, avoid holding every page in memory at once.

## Development

```bash
composer install
composer test        # phpunit
composer analyse     # phpstan (level max)
composer cs          # php-cs-fixer (dry-run)
composer check       # cs + analyse + test
composer coverage    # phpunit with a coverage report (needs xdebug or pcov)
```

## Maintenance

The FPDI + FPDF engine under `src/Engine/` is a **vendored fork, not a dependency** —
so it does **not** receive upstream fixes automatically. Security and bug fixes from
FPDI/FPDF must be tracked and ported into `src/Engine/` by hand. This is the deliberate
trade-off for having no external PDF dependencies.

## License

MIT — see [LICENSE](LICENSE). The bundled fork of FPDI (MIT) and FPDF (permissive)
retains its upstream copyright and license notices in `src/Engine/`
(`LICENSE-FPDI.txt`, `LICENSE-FPDF.txt`).
