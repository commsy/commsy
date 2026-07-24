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
 * Character and line-break constants formerly defined globally in
 * legacy/etc/cs_constants.php.
 *
 * The RFC character-class strings intentionally keep the exact escaping of
 * the legacy definitions. IMPORTANT: for preg functions use "§" as the
 * search-expression delimiter.
 */
final class Chars
{
    /** Line feed. */
    public const LF = "\n";
    /** HTML line break. */
    public const BR = '<br />';
    /** HTML line break followed by a line feed. */
    public const BRLF = "<br />\n";
    /** Tab. */
    public const TAB = "\t";

    /** Uppercase accented characters. */
    public const UC_CHARS = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ';
    /** Lowercase accented characters. */
    public const LC_CHARS = 'àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþ';
    /** All special (accented) characters including ß. */
    public const SPECIAL_CHARS = self::UC_CHARS.self::LC_CHARS.'ß';

    /** Characters allowed in URLs by RFC 1738, for use in a character class. */
    public const RFC1738_CHARS = "A-Za-z0-9\?:@&=/;_\.\+!\*'(,%\$~#-";

    /** Characters allowed in email addresses by RFC 2822, for use in a character class. */
    public const RFC2822_CHARS = "A-Za-z0-9!#\$%&'\*\+/=\?\^_`{\|}~-";
}
