# Security Policy

## Supported versions

The latest released `0.x` line receives security fixes. Older tags do not.

## Reporting a vulnerability

Please report vulnerabilities privately — do **not** open a public issue.

- Preferred: open a [GitHub security advisory](https://github.com/ynbagency/fpdi-plus/security/advisories/new).
- Or email **ynb.agency@gmail.com** with details and a reproduction.

We aim to acknowledge within a few business days.

## Handling untrusted PDFs

This library parses PDFs you pass to it, and PDF parsing is an attack surface.
If any input can come from an untrusted source (uploads, third parties):

- **Cap input size.** FPDF buffers the *entire* output document in memory before
  writing, and a small compressed stream can inflate massively on decompression
  (a "decompression bomb"). Enforce a maximum input size and a PHP `memory_limit`
  appropriate to your workload.
- **Run with resource limits.** Bound `max_execution_time` and memory; process in
  an isolated worker where possible.
- **Scan uploads** (e.g. with an antivirus such as ClamAV) before processing.
- **Do not trust filenames or paths** derived from user input.

Encrypted PDFs and PDFs with compressed cross-reference streams are rejected with
`YnbAgency\Fpdi\Exception\UnsupportedPdfException` by the bundled parser; handling
them requires the commercial `setasign/fpdi-pdf-parser` add-on.
