<?php

/**
 * This file is part of FPDI
 *
 * @package   YnbAgency\Fpdi\Engine
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace YnbAgency\Fpdi\Engine\PdfParser\Filter;

use YnbAgency\Fpdi\Engine\PdfParser\PdfParserException;

/**
 * Exception for filters
 */
class FilterException extends PdfParserException
{
    const UNSUPPORTED_FILTER = 0x0201;

    const NOT_IMPLEMENTED = 0x0202;
}
