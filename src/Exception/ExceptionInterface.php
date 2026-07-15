<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi\Exception;

/**
 * Marker interface implemented by every exception this package throws.
 *
 * Catch this to handle any fpdi-plus failure without coupling to the concrete
 * exception classes or to the underlying setasign/FPDI internals.
 */
interface ExceptionInterface extends \Throwable
{
}
