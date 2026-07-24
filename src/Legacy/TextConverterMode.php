<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Legacy;

/**
 * Numeric mode selectors for the legacy text converter
 * ({@see \misc_text_converter}), formerly defined globally in
 * legacy/etc/cs_constants.php.
 *
 * `AS_*` modes encode from the internal representation into a target format;
 * `FROM_*` modes decode an incoming value; `NONE` passes text through
 * unchanged.
 */
final class TextConverterMode
{
    public const AS_HTML_SHORT = 2;
    public const AS_FORM = 4;
    public const AS_DB = 5;
    public const AS_FILE = 6;
    public const AS_MAIL = 7;
    public const AS_RSS = 8;
    public const NONE = 10;
    public const FROM_DB = 12;
    public const FROM_FILE = 13;
    public const FROM_GET = 14;
}
