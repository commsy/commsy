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

/**
 * Generates a xml string out of an array.
 * This function is used at the management of the extras field in the mysql-database.
 *
 * Because of a problem with the XML parser with < and >, we have to convert these characters
 * to their html-entitys (gt; and &lt;). These characters are stored encoded in the DB!
 * We change them back, when we get the XML-string out ouf the DB. See: XML2Array.
 * This happens only in extra-fields.
 *
 * @return returns a string
 */
function array2XML($array)
{
    $xml = '';
    if (!empty($array) and is_array($array)) {
        $keys = array_keys($array);
        foreach ($keys as $key) {
            if (is_array($array[$key])) {
                if (count($array[$key]) > 0) {
                    $data = array2XML($array[$key]);
                } else {
                    $data = '';
                }
            } else {
                $data = $array[$key];
                // convert > and < to their html entities (gt; and &lt;)
                if (strstr((string) $data, '<')) {
                    $data = mb_ereg_replace('<', '%CS_LT;', (string) $data);
                }
                if (strstr((string) $data, '>')) {
                    $data = mb_ereg_replace('>', '%CS_GT;', (string) $data);
                }
                if (strstr((string) $data, '&')) {
                    $data = mb_ereg_replace('&', '%CS_AND;', (string) $data);
                }
            }

            // xml_parser have problems with integers as keys,
            // so we set an XML_ before integers and delete it,
            // when we get is out of the databes. See: XML2Array
            if (is_int($key)) {
                $key = 'XML_'.$key;
            }
            $xml .= '<'.mb_strtoupper($key, 'UTF-8').'>'.$data.'</'.mb_strtoupper($key, 'UTF-8').'>'."\n";
        }
    }

    return $xml;
}

function XMLToArray($xml)
{
    if ($xml instanceof SimpleXMLElement) {
        $children = $xml->children();
        $return = null;
    } else {
        $xml = simplexml_load_string((string) $xml);
        if ($xml instanceof SimpleXMLElement) {
            $children = $xml->children();
        } else {
            $children = [];
        }
        $return = null;
    }
    foreach ($children as $element => $value) {
        if (strstr((string) $element, 'XML_')) {
            $element_begin = mb_substr((string) $element, 0, 4);
            if ($element_begin = 'XML_') {
                $element = mb_substr((string) $element, 4);
            }
        }
        if ($value instanceof SimpleXMLElement) {
            $counter = 0;
            foreach ($value->children() as $children) {
                ++$counter;
            }
            if ($counter > 0) {
                $return[$element] = XMLToArray($value);
            } else {
                if (!empty($element) and 'extras' == $element) {
                    $value = unserialize(mb_convert_encoding((string) $value, 'ISO-8859-1'));
                } elseif (isset($value)) {
                    // convert > and < to their html entities (gt; and &lt;)
                    if (strstr($value, '%CS_AND;')) {
                        $value = mb_ereg_replace('%CS_AND;', '&', $value);
                    }
                    if (strstr($value, '%CS_LT;')) {
                        $value = mb_ereg_replace('%CS_LT;', '<', $value);
                    }
                    if (strstr($value, '%CS_GT;')) {
                        $value = mb_ereg_replace('%CS_GT;', '>', $value);
                    }
                    $value = mb_convert_encoding($value, 'ISO-8859-1'); // needed for PHP5
                } else {
                    $value = '';
                }
                if (!isset($return[$element])) {
                    if (is_array($value)) {
                        $return[$element] = $value;
                    } else {
                        $return[$element] = (string) $value;
                    }
                } else {
                    if (!is_array($return[$element])) {
                        $return[$element] = [$return[$element], (string) $value];
                    } else {
                        $return[$element][] = (string) $value;
                    }
                }
            }
        }
    }
    if (is_array($return)) {
        return $return;
    } else {
        return false;
    }
}

/**
 * Generates an array out of an xml-string.
 * This function is used at the management of the extras field in the mysql-database
 * Uses expat XML parser / PHP XML-functions.
 *
 * @return returns an array
 */
function XML2Array($text)
{
    $text = '<SAVE>'.$text.'</SAVE>';
    $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1'); // needed for PHP5
    $result = XMLToArray($text);

    return $result;
}

/** like array_merge only for multible arrays
 * returns merged array.
 *
 * @param $array1 array
 * @param $array2 array
 *
 * @return  $array1 array merged array
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

function getmicrotime(): float
{
    [$usec, $sec] = explode(' ', microtime());

    return (float) $usec + (float) $sec;
}

/** converts the module name into item type.
 *
 * @return string item type
 */
function Module2Type($module)
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

/** converts the table name into item type.
 *
 * @return string item type
 */
function DBTable2Type($table)
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

// Function to recursively add a directory,
// subdirectories and files to a zip archive
function addFolderToZip($dir, $zipArchive, $zipdir = '')
{
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            // Add the directory
            if ('.' !== $dir) {
                $zipArchive->addEmptyDir($dir);
            }

            // Loop through all the files
            while (($file = readdir($dh)) !== false) {
                if ('.' !== $dir) {
                    $file_path = $dir.DIRECTORY_SEPARATOR.$file;
                } else {
                    $file_path = $file;
                }
                if (!empty($zipdir)) {
                    $zip_path = $zipdir.DIRECTORY_SEPARATOR.$file;
                } else {
                    $zip_path = $file_path;
                }

                // If it's a folder, run the function again!
                if (!is_file($file_path)) {
                    // Skip parent and root directories
                    if (('.' !== $file) and ('..' !== $file)) {
                        addFolderToZip($file_path, $zipArchive, $zip_path);
                    }
                } else {
                    // Add the files
                    $result = $zipArchive->addFile($file_path, $zip_path);
                }
            }
        }
    }

    return $zipArchive;
}
