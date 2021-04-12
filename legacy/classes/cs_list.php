<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

/** class for lists of commsy items (objects)
 * this class implements a list of objects. An object is a commsy item
 */
class cs_list implements IteratorAggregate
{
    /**
     * string - containing the type of the list resp. the type of the elements
     */
    protected $type;

    /**
     * array - containing the elements of the list
     */
    protected $data = [];

    /** constructor: cs_list
     * the only available constructor, initial values for internal variables
     */
    public function __construct()
    {
        $this->type = 'list';
    }

    /** is the type of the list = $type ?
     * this method returns a boolean expressing if type of the list is $type or not
     *
     * @param string type string to compare with type of list ($type)
     *
     * @return boolean   true - type of this list is $type
     *                   false - type of this list is not $type
     */
    public function isA($type): bool
    {
        return $this->type == $type;
    }

    /** reset internal variables
     *
     * this method resets the list
     */
    public function reset(): void
    {
        reset($this->data);
    }

    /** reset internal cursor
     *
     * this method resets the cursor
     */
    public function resetCursor()
    {
        reset($this->data);
    }

    /** get next element
     * this method returns the next element from the internal array
     *
     * @return object|false cs_item returns an object with the information about the next element
     */
    public function getNext()
    {
        return next($this->data);
    }

    /** add an element
     * this method adds a new element to the list
     *
     * @param object cs_item a commsy item (object)
     */
    public function add($item): void
    {
        $this->data[] = $item;
    }

    /** get first element
     * this method returns the first element from the internal array
     *
     * @return object|false cs_item an commsy item with the information about the first element
     */
    public function getFirst()
    {
        $this->reset();
        return current($this->data);
    }

    public function getSubList($position, $length): cs_list
    {
        $sub_list = new cs_list();
        $subdata_array = array_slice($this->data, $position, $length);
        foreach ($subdata_array as $subdata_item) {
            $sub_list->add($subdata_item);
        }
        return $sub_list;
    }

    /** get last element
     * this method returns the last element from the internal array
     *
     * @return object|false cs_item an commsy item with the information about the last element
     */
    public function getLast()
    {
        return end($this->data);
    }


    /** add a list of commsy items to this list
     * this method adds a list of commsy items to this list, like array_merge
     *
     * @param object cs_list a list of commsy items (object)
     */
    public function addList($list): void
    {
        // performance ??? (TBD)
        $item = $list->getFirst();
        while ($item) {
            $this->add($item);
            $item = $list->getNext();
        }
    }

    /** count list
     * this method returns the number of elements
     *
     * @return integer number of elements within the list
     */
    public function getCount(): int
    {
        return count($this->data);
    }

    /** is list empty
     * this method returns a boolean: true if list is empty
     *
     * @return boolean list empty?
     */
    public function isEmpty(): bool
    {
        return $this->getCount() === 0;
    }

    /** is list not empty
     * this method returns a boolean: true if list is not empty
     *
     * @return boolean list not empty?
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /** sort list
     * this method sort list by $sort_by
     *
     * @param string sort_by keyword for sorting list
     */
    public function sortby($sort_by): void
    {
        // prepare temp array to sort
        if (count($this->data) > 1) {
            $old_list = $this->data;
            $temp_array = array();
            for ($i = 0; $i < count($old_list); $i++) {
                $temp_array2['position'] = $i;
                if ($sort_by == 'name') {
                    $temp_array2[$sort_by] = $this->translateUmlaute($old_list[$i]->getName());
                } elseif ($sort_by == 'lastname') {
                    $temp_array2[$sort_by] = $this->translateUmlaute($old_list[$i]->getLastname());
                } elseif ($sort_by == 'modification_date') {
                    $temp_array2[$sort_by] = $old_list[$i]->getModificationDate();
                } elseif ($sort_by == 'title') {
                    $temp_array2[$sort_by] = $this->translateUmlaute($old_list[$i]->getTitle());
                } elseif ($sort_by == 'sorting') {
                    $temp_array2[$sort_by] = $old_list[$i]->getSortingFieldContent();
                } elseif ($sort_by == 'filename') {
                    $temp_array2[$sort_by] = $old_list[$i]->getDisplayName();
                } elseif ($sort_by == 'date') {
                    $temp_array2[$sort_by] = $old_list[$i]->getDateTime_start() . $old_list[$i]->getDateTime_end();
                } elseif ($sort_by == 'treePosition') {
                    $temp_array2[$sort_by] = $old_list[$i]->getPosition();
                } else {
                    throw new LogicException("not implemented");
                }
                $temp_array[] = $temp_array2;
            }

            // sort temp array
            usort($temp_array, function ($a, $b) use ($sort_by) {
                return strnatcasecmp($a[$sort_by], $b[$sort_by]);
            });

            // create sorted list array
            unset($this->data);
            $this->data = array();
            for ($i = 0; $i < count($temp_array); $i++) {
                $this->data[$i] = $old_list[$temp_array[$i]['position']];
            }
        }
    }

    /** reverse list elements
     * this method reverse the list
     */
    public function reverse(): void
    {
        $this->data = array_reverse($this->data);
    }

    /** list unique
     * this method is like array_unique
     */
    public function unique(): void
    {
        if (count($this->data) > 1) {
            $a = $this->data;
            $r = array();
            for ($i = 0; $i < count($a); $i++) {
                if (!in_array($a[$i], $r)) {
                    $r[] = $a[$i];
                }
            }
            $this->data = $r;
        }
    }

    public function removeElement($item): void
    {
        foreach ($this->data as $pos => $list_item) {
            if ($list_item->getItemID() == $item->getItemID() and $list_item->getVersionID() == $item->getVersionID()) {
                array_splice($this->data, $pos, 1);
            }
        }
    }

    public function getElement($item): object
    {
        $return_item = new cs_list();
        foreach ($this->data as $pos => $list_item) {
            if ($list_item->getItemID() == $item->getItemID() and $list_item->getVersionID() == $item->getVersionID()) {
                $return_item = $list_item;
            }
        }
        return $return_item;
    }

    public function get(int $pos): object
    {
        return $this->data[$pos];
    }

    function inList($item): bool
    {
        if (isset($item)
            and $item->getItemID() > 0
        ) {
            foreach ($this->data as $pos => $list_item) {
                if ($list_item->getItemID() == $item->getItemID() and $list_item->getVersionID() == $item->getVersionID()) {
                    return true;
                }
            }
        }

        return false;
    }

    function to_array(): array
    {
        return $this->data;
    }

    public function getIDArray(): array
    {
        $retour = array();
        $item = $this->getFirst();
        while ($item) {
            if (method_exists($item, 'getItemID')) {
                $retour[] = $item->getItemID();
            }
            $item = $this->getNext();
        }

        return $retour;
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->to_array());
    }

    private function translateUmlaute(string $value): string
    {
        $value = str_replace('Ä', 'Azzz', $value);
        $value = str_replace('Ö', 'Ozzz', $value);
        $value = str_replace('Ü', 'Uzzz', $value);
        $value = str_replace('ä', 'azzz', $value);
        $value = str_replace('ö', 'ozzz', $value);
        $value = str_replace('ü', 'uzzz', $value);
        return $value;
    }
}
