# Changelog

All notable changes to this project are documented here.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-07-15

### Changed
- **Self-contained: removed the `setasign/fpdi` and `setasign/fpdf` dependencies.**
  A namespaced fork of both is now bundled under `src/Engine/`
  (`YnbAgency\Fpdi\Engine`), so the package has no external PDF dependencies —
  only PHP and `ext-zlib`. `Pdf` now extends `YnbAgency\Fpdi\Engine\Fpdi`.
- `UnsupportedPdfException` message no longer references the commercial
  fpdi-pdf-parser add-on (it does not apply to the fork).

### Notes
- Trade-off: FPDI/FPDF upstream fixes are no longer received via `composer update`
  and must be ported into `src/Engine/` by hand (see README → Maintenance).
- Upstream license notices for FPDI (MIT) and FPDF (permissive) are retained per
  file and in `src/Engine/LICENSE-FPDI.txt` / `LICENSE-FPDF.txt`.

## [0.1.0] - 2026-07-15

Initial release. `YnbAgency\Fpdi\Pdf` extends `setasign\Fpdi\Fpdi` and adds:

### Added
- `merge()` — combine several PDFs into one, preserving each page's size/orientation.
- `extract()` / `appendPages()` — select a subset of pages, in a given order.
- `split()` — write one file per page via a printf pattern.
- `watermark()` — stamp a page over every page of a source, scaled to fit.
- `appendFile()` / `appendString()` — append all pages of a PDF from a path or from
  raw in-memory bytes (uploads with no temp file).
- `importAllPages()` — import every page and return the template ids.
- `render()` / `save()` — output as a string or to a file.
- `Exception\UnsupportedPdfException` + `Exception\ExceptionInterface` — a
  package-owned exception surface. Missing/unreadable files and PDFs the bundled
  FPDI parser cannot read (encrypted or compressed cross-reference streams) raise
  `UnsupportedPdfException` with the original setasign exception as the previous.

### Notes
- Static factories use late static binding (`new static()`), so subclasses stay in
  control.
- Tooling: PHPUnit, PHPStan (level max), PHP-CS-Fixer (PSR-12); CI across PHP
  8.1–8.5 on Linux and Windows, plus lowest-deps and quality/coverage jobs.

[Unreleased]: https://github.com/ynbagency/fpdi-plus/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/ynbagency/fpdi-plus/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/ynbagency/fpdi-plus/releases/tag/v0.1.0
