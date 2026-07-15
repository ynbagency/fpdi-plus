<?php

declare(strict_types=1);

namespace YnbAgency\Fpdi\Tests\Fixtures;

use YnbAgency\Fpdi\Pdf;

/**
 * Test double that records whether {@see Pdf::merge()} instantiated it through
 * the late-static-bound factory. Used to prove merge() uses `new static()`.
 */
final class RecordingPdf extends Pdf
{
    public static bool $madeViaOverride = false;

    protected static function make(): static
    {
        self::$madeViaOverride = true;

        return new static();
    }
}
