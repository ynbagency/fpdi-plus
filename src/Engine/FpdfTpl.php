<?php

/**
 * This file is part of FPDI
 *
 * @package   YnbAgency\Fpdi\Engine
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace YnbAgency\Fpdi\Engine;

/**
 * Class FpdfTpl
 *
 * This class adds a templating feature to FPDF.
 */
class FpdfTpl extends Fpdf
{
    use FpdfTplTrait;
}
