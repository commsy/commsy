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

class cs_section_list extends cs_list
{
    /** constructor: cs_list
     * the only available constructor, initial values for internal variables.
     *
     * @author CommSy Development Group
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = 'section_list';
    }

    public function append($section): void
    {
        $count = is_countable($this->data) ? count($this->data) : 0;
        $pos = $count + 1;
        if ($section->getNumber() != $pos) {
            $section->setNumber($pos);
        }
        $this->data[$pos] = $section;
        ksort($this->data);
    }

    public function set(cs_section_item $section): void
    {
        $new_array = [];
        $counter = 1;
        $tmp_array = [];
        // if the section already exists in the array, resort array without section
        $section_id = $section->getItemID();
        if (!empty($section_id)) {
            foreach ($this->data as $section_item) {
                if ($section_item->getItemID() != $section->getItemID()) {
                    $section_item->setNumber($counter);
                    $tmp_array[$section_item->getNumber()] = $section_item;
                    ++$counter;
                }
            }
        } else {
            $tmp_array = $this->data;
        }
        // resort the sections ...
        foreach ($tmp_array as $section_item) {
            if ($section_item->getNumber() >= $section->getNumber()) {
                $section_item->setNumber($section_item->getNumber() + 1);
            }
            $new_array[$section_item->getNumber()] = $section_item;
        }
        // ...and put the new one in place
        $new_array[$section->getNumber()] = $section;
        $this->data = $new_array;
        ksort($this->data);
    }

    public function remove(int $pos): void
    {
        $counter = 1;
        $tmp_array = [];
        // resort array without section where number==$pos
        foreach ($this->data as $section_item) {
            if ($section_item->getNumber() != $pos) {
                $section_item->setNumber($counter);
                $tmp_array[$section_item->getNumber()] = $section_item;
                ++$counter;
            }
        }
        $this->data = $tmp_array;
    }
}
