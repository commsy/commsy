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

/** class for lists of commsy items (objects)
 * this class implements a list of objects. An object is a commsy item.
 */
class cs_list implements IteratorAggregate
{
    /**
     * string - containing the type of the list resp. the type of the elements.
     */
    protected $type;

    /**
     * array - containing the elements of the list.
     */
    protected array $data = [];

    /** constructor: cs_list
     * the only available constructor, initial values for internal variables.
     */
    public function __construct()
    {
        $this->type = 'list';
    }

    /** is the type of the list = $type ?
     * this method returns a boolean expressing if type of the list is $type or not.
     *
     * @param string type string to compare with type of list ($type)
     *
     * @return bool true - type of this list is $type
     *              false - type of this list is not $type
     */
    public function isA($type): bool
    {
        return $this->type == $type;
    }

    /** reset internal variables.
     *
     * this method resets the list
     */
    public function reset(): void
    {
        reset($this->data);
    }

    /** reset internal cursor.
     *
     * this method resets the cursor
     */
    public function resetCursor()
    {
        reset($this->data);
    }

    /** get next element
     * this method returns the next element from the internal array.
     *
     * @return object|false cs_item returns an object with the information about the next element
     */
    public function getNext(): object|false
    {
        return next($this->data);
    }

    /** add an element
     * this method adds a new element to the list.
     *
     * @param object cs_item a commsy item (object)
     */
    public function add($item): void
    {
        $this->data[] = $item;
    }

    /** get first element
     * this method returns the first element from the internal array.
     *
     * @return object|false cs_item an commsy item with the information about the first element
     */
    public function getFirst(): object|false
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
     * this method returns the last element from the internal array.
     *
     * @return object|false cs_item an commsy item with the information about the last element
     */
    public function getLast(): object|false
    {
        return end($this->data);
    }

    /** add a list of commsy items to this list
     * this method adds a list of commsy items to this list, like array_merge.
     *
     * @param cs_list $list cs_list a list of commsy items (object)
     */
    public function addList(cs_list $list): void
    {
        array_push($this->data, ...$list);
    }

    /** count list
     * this method returns the number of elements.
     *
     * @return int number of elements within the list
     */
    public function getCount(): int
    {
        return count($this->data);
    }

    /** is list empty
     * this method returns a boolean: true if list is empty.
     *
     * @return bool list empty?
     */
    public function isEmpty(): bool
    {
        return 0 === $this->getCount();
    }

    /** is list not empty
     * this method returns a boolean: true if list is not empty.
     *
     * @return bool list not empty?
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /** sort list
     * this method sort list by $sort_by.
     *
     * @param string sort_by keyword for sorting list
     */
    public function sortby($sort_by): void
    {
        $temp_array2 = [];
        // prepare temp array to sort
        if (count($this->data) > 1) {
            $old_list = $this->data;
            $temp_array = [];
            for ($i = 0; $i < count($old_list); ++$i) {
                $temp_array2['position'] = $i;
                if ('name' == $sort_by) {
                    $temp_array2[$sort_by] = $this->translateUmlaute($old_list[$i]->getName());
                } elseif ('lastname' == $sort_by) {
                    $temp_array2[$sort_by] = $this->translateUmlaute($old_list[$i]->getLastname());
                } elseif ('modification_date' == $sort_by) {
                    $temp_array2[$sort_by] = $old_list[$i]->getModificationDate();
                } elseif ('title' == $sort_by) {
                    $temp_array2[$sort_by] = $this->translateUmlaute($old_list[$i]->getTitle());
                } elseif ('sorting' == $sort_by) {
                    $temp_array2[$sort_by] = $old_list[$i]->getSortingFieldContent();
                } elseif ('filename' == $sort_by) {
                    $temp_array2[$sort_by] = $old_list[$i]->getDisplayName();
                } elseif ('date' == $sort_by) {
                    $temp_array2[$sort_by] = $old_list[$i]->getDateTime_start().$old_list[$i]->getDateTime_end();
                } elseif ('treePosition' == $sort_by) {
                    $temp_array2[$sort_by] = $old_list[$i]->getPosition();
                } else {
                    throw new LogicException('not implemented');
                }
                $temp_array[] = $temp_array2;
            }

            // sort temp array
            usort($temp_array, fn ($a, $b) => strnatcasecmp((string) $a[$sort_by], (string) $b[$sort_by]));

            // create sorted list array
            unset($this->data);
            $this->data = [];
            for ($i = 0; $i < count($temp_array); ++$i) {
                $this->data[$i] = $old_list[$temp_array[$i]['position']];
            }
        }
    }

    /** reverse list elements
     * this method reverse the list.
     */
    public function reverse(): void
    {
        $this->data = array_reverse($this->data);
    }

    /** list unique
     * this method is like array_unique.
     */
    public function unique(): void
    {
        if (count($this->data) > 1) {
            $a = $this->data;
            $r = [];
            for ($i = 0; $i < count($a); ++$i) {
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

    public function inList($item): bool
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

    public function to_array(): array
    {
        return $this->data;
    }

    public function getIDArray(): array
    {
        $ids = [];

        foreach ($this as $item) {
            if (method_exists($item, 'getItemID')) {
                $ids[] = $item->getItemID();
            }
        }

        return $ids;
    }

    /**
     * Retrieve an external iterator.
     *
     * @see https://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     *                     <b>Traversable</b>
     *
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
