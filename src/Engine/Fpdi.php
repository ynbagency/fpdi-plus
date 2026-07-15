<?php

/**
 * This file is part of FPDI
 *
 * @package   YnbAgency\Fpdi\Engine
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace YnbAgency\Fpdi\Engine;

use YnbAgency\Fpdi\Engine\PdfParser\CrossReference\CrossReferenceException;
use YnbAgency\Fpdi\Engine\PdfParser\PdfParserException;
use YnbAgency\Fpdi\Engine\PdfParser\Type\PdfIndirectObject;
use YnbAgency\Fpdi\Engine\PdfParser\Type\PdfNull;

/**
 * Class Fpdi
 *
 * This class let you import pages of existing PDF documents into a reusable structure for FPDF.
 */
class Fpdi extends FpdfTpl
{
    use FpdiTrait;
    use FpdfTrait;

    /**
     * FPDI version
     *
     * @string
     */
    const VERSION = '2.6.8';
}
