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

/** like array_merge only for multible arrays
 * returns merged array.
 *
 * @param $array1 array
 * @param $array2 array
 */
function multi_array_merge(array $array1, array $array2): array
{
    foreach ($array2 as $key => $value) {
        if (is_array($value)) {
            if (empty($array1[$key])) {
                $array1[$key] = $value;
            } else {
                $array1[$key] = multi_array_merge($array1[$key], $value);
            }
        } else {
            $array1[$key] = $value;
        }
    }

    return $array1;
}

/**
 * Converts the module name into an item type.
 */
function Module2Type($module): string
{
    $module = cs_strtolower($module);
    if ('topics' == $module) {
        $type = CS_TOPIC_TYPE;
    } elseif (CS_ANNOUNCEMENT_TYPE == $module) {
        $type = CS_ANNOUNCEMENT_TYPE;
    } elseif ('dates' == $module) {
        $type = CS_DATE_TYPE;
    } else {
        $type = $module;
    }

    return $type;
}

/**
 * Converts the table name into an item type.
 */
function DBTable2Type($table): string
{
    $table = cs_strtolower($table);
    if ('annotations' == $table) {
        $type = CS_ANNOTATION_TYPE;
    } elseif ('dates' == $table) {
        $type = CS_DATE_TYPE;
    } elseif ('discussionarticles' == $table) {
        $type = CS_DISCARTICLE_TYPE;
    } elseif ('discussions' == $table) {
        $type = CS_DISCUSSION_TYPE;
    } elseif ('labels' == $table) {
        $type = CS_LABEL_TYPE;
    } elseif ('materials' == $table) {
        $type = CS_MATERIAL_TYPE;
    } elseif ('files' == $table) {
        $type = CS_FILE_TYPE;
    } elseif ('todos' == $table) {
        $type = CS_TODO_TYPE;
    } elseif ('links' == $table) {
        $type = CS_LINK_TYPE;
    } elseif ('link_items' == $table) {
        $type = CS_LINKITEM_TYPE;
    } elseif ('item_link_file' == $table) {
        $type = CS_LINKITEMFILE_TYPE;
    } else {
        $type = $table;
    }

    return $type;
}
