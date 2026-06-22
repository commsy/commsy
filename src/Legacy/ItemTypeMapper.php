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

namespace App\Legacy;

use App\Utils\StringCase;

/**
 * Maps legacy module names and database table names to CommSy item type strings.
 *
 * Interim Legacy->App bridge: holds the bodies of the former global functions
 * Module2Type()/DBTable2Type() from legacy/functions/misc_functions.php so that
 * file can be removed. The returned strings are the values of the legacy
 * CS_*_TYPE constants, inlined here so the mapper stays self-contained.
 */
final class ItemTypeMapper
{
    public static function fromModule(string $module): string
    {
        return match (StringCase::toLower($module)) {
            'topics' => 'topic',
            'announcement' => 'announcement',
            'dates' => 'date',
            default => StringCase::toLower($module),
        };
    }

    public static function fromDbTable(string $table): string
    {
        return match (StringCase::toLower($table)) {
            'annotations' => 'annotation',
            'dates' => 'date',
            'discussionarticles' => 'discarticle',
            'discussions' => 'discussion',
            'labels' => 'label',
            'materials' => 'material',
            'files' => 'file',
            'todos' => 'todo',
            'links' => 'link',
            'link_items' => 'link_item',
            'item_link_file' => 'link_item_file',
            default => StringCase::toLower($table),
        };
    }
}
