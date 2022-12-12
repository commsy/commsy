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
    private ?int $_design_folder = null;
    private array $_class_loaded_array = [];

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
        if (!isset($this->_class_loaded_array[$name])) {
            $this->includeClass($name);
            $this->_class_loaded_array[$name] = true;
        }

        return new $this->_class_array[$name]['name']($params);
    }

    public function includeClass($name)
    {
        if (!empty($this->_class_array[$name]['switchable'])
             and $this->_class_array[$name]['switchable']
             and !empty($this->_design_folder)
             and !empty($this->_class_array[$name]['folder'])
             and !mb_stristr($this->_class_array[$name]['folder'], '/'.$this->_design_folder.'/')
        ) {
            $this->_class_array[$name]['folder'] .= $this->_design_folder.'/';
        }
        if (empty($this->_class_array[$name]['folder'])) {
            trigger_error('don\'t know where class '.$name.' is', E_USER_ERROR);
        } elseif (empty($this->_class_array[$name]['filename'])) {
            trigger_error('don\'t know the filename of '.$name, E_USER_ERROR);
        } elseif (!file_exists(realpath(__DIR__).'/../'.$this->_class_array[$name]['folder'].$this->_class_array[$name]['filename'])) {
            trigger_error('file '.$this->_class_array[$name]['folder'].$this->_class_array[$name]['filename'].' does not exist', E_USER_ERROR);
        } else {
            include_once realpath(__DIR__).'/../'.$this->_class_array[$name]['folder'].$this->_class_array[$name]['filename'];
        }
    }
}
