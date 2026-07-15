<?php

/**
 * This file is part of FPDI
 *
 * @package   YnbAgency\Fpdi\Engine
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace YnbAgency\Fpdi\Engine\PdfParser;

use YnbAgency\Fpdi\Engine\FpdiException;

/**
 * Exception for the pdf parser class
 */
class PdfParserException extends FpdiException
{
    /**
     * @var int
     */
    const NOT_IMPLEMENTED = 0x0001;

    /**
     * @var int
     */
    const IMPLEMENTED_IN_FPDI_PDF_PARSER = 0x0002;

    /**
     * @var int
     */
    const INVALID_DATA_TYPE = 0x0003;

    /**
     * @var int
     */
    const FILE_HEADER_NOT_FOUND = 0x0004;

    /**
     * @var int
     */
    const PDF_VERSION_NOT_FOUND = 0x0005;

    /**
     * @var int
     */
    const INVALID_DATA_SIZE = 0x0006;
}
