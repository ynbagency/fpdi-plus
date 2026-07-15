<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi\Exception;

/**
 * Thrown when a source PDF cannot be read: the file is missing/unreadable, or
 * the bundled parser does not support it (e.g. encrypted, or a compressed
 * cross-reference stream as produced by many PDF 1.5+ generators).
 *
 * The original engine exception, when there is one, is available via
 * {@see \Throwable::getPrevious()}.
 */
class UnsupportedPdfException extends \RuntimeException implements ExceptionInterface
{
}
