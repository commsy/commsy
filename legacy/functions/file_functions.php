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

/** writes a content in a file
 *  this method writes the string content in the file named filename.
 *
 *  @param string content which should be written in the file
 *  @param string filename  is the filename
 *
 * @author CommSy Development Group
 */
function write2File($content, $filename)
{
    $messagefile = fopen($filename, 'w');
    fwrite($messagefile, $content);
    fclose($messagefile);
}

/**
 * Concatenates two file paths.
 *
 * @param $path1 the left part of the new path
 * @param $path2 the right part of the new path
 *
 * @return returns the concatenated path as a string
 *
 * @author  rickert
 */
function concatPath($path1, $path2)
{
    $newpath = '';
    $p1hasSlash = false;
    $p2hasSlash = false;

    if (0 != strlen($path2)) {
        if (strlen($path1) == (mb_strrpos($path1, '/') + 1)) {
            $p1hasSlash = true;
        }
        if (false === mb_strpos($path2, '/')) {
            $p2hasSlash = false;
        } elseif (0 == mb_strpos($path2, '/')) {
            $p2hasSlash = true;
        }
        if (!$p1hasSlash) {
            $path1 = $path1.'/';
        }
        if ($p2hasSlash) {
            $path2 = substr($path2, 1, strlen($path2));
        }
    }
    $newpath = $path1.$path2;

    return $newpath;
}

function getFilesize($file)
{
    $size = filesize($file);

    if ($size < 1000) {
        return number_format($size, 0, ',', '.').' Bytes';
    } elseif ($size < 1_000_000) {
        return number_format($size / 1024, 0, ',', '.').' kB';
    } else {
        return number_format($size / 1_048_576, 0, ',', '.').' MB';
    }
}
