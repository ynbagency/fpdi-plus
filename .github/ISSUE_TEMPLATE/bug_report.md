---
name: Bug report
about: Report a problem with fpdi-plus
labels: bug
---

**What happened**
A clear description of the bug.

**To reproduce**
Minimal code sample and, if possible, a sample PDF (or how it was generated).

```php
// ...
```

**Expected behaviour**

**Environment**
- fpdi-plus version:
- PHP version:
- setasign/fpdi + setasign/fpdf versions:

**Notes**
Encrypted PDFs and PDFs with compressed cross-reference streams are unsupported
by the bundled parser (they raise `UnsupportedPdfException`) — please confirm
this isn't the cause before filing.
