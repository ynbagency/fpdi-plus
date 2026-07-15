# Changelog

All notable changes to this project are documented here.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `Pdf::appendFile()` — append every page of a source PDF to the current
  document, preserving each page's size and orientation.
- `Exception\UnsupportedPdfException` and `Exception\ExceptionInterface` — a
  package-owned exception surface. Missing/unreadable files and PDFs the bundled
  FPDI parser cannot read (encrypted or compressed cross-reference streams) now
  raise `UnsupportedPdfException` instead of leaking setasign internals or PHP
  warnings.

### Changed
- `Pdf::merge()` now instantiates via `new static()` (late static binding), so
  subclasses stay in control.
- `Pdf::merge()` throws `InvalidArgumentException` on an empty file list instead
  of silently writing a blank one-page PDF.

## [0.1.0]

### Added
- Initial release: `Pdf` extending `setasign\Fpdi\Fpdi` with `importAllPages()`
  and static `merge()`, built on FPDI + FPDF.

[Unreleased]: https://github.com/ynbagency/fpdi-plus/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/ynbagency/fpdi-plus/releases/tag/v0.1.0
