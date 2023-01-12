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

class cs_class_factory
{
    private $_class_array = [
        'misc_text_converter' => [
            'name' => 'misc_text_converter',
            'filename' => 'misc_text_converter.php',
            'folder' => 'classes/',
            'switchable' => false,
        ],
    ];

    public function __construct()
    {
    }

    public function getClass($name, $params = [])
    {
        return new $this->_class_array[$name]['name']($params);
    }
}
