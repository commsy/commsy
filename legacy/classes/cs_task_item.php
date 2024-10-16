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

/** class for a task
 * this class implements a task item.
 */
class cs_task_item extends cs_item
{
    /**
     * object - linked object to the task item.
     */
    public $_item = null;

    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param object environment the environment of the commsy
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = 'task';
    }

    /** Checks and sets the data of the item.
     *
     * @param $data_array
     *
     * @author CommSy Development Group
     */
    public function _setItemData($data_array): void
    {
        // not yet implemented
        $this->_data = $data_array;
    }

    /** get title of a task
     * this method returns the title of the task.
     *
     * @return string title of a task
     *
     * @author CommSy Development Group
     */
    public function getTitle(): string
    {
        return $this->_getValue('title');
    }

    /** set title of a task
     * this method sets the title of the task.
     *
     * @param string value title of the task
     *
     * @author CommSy Development Group
     */
    public function setTitle(string $value): void
    {
        // sanitize title
        $converter = $this->_environment->getTextConverter();
        $value = htmlentities($value);
        $value = $converter->sanitizeHTML($value);
        $this->_setValue('title', $value);
    }

    /** get status of a task
     * this method returns the status of the task.
     *
     * @return int|string status of a task
     *
     * @author CommSy Development Group
     */
    public function getStatus(): int|string
    {
        return $this->_getValue('status');
    }

    /** set status of a task
     * this method sets the status of the task.
     *
     * @param string value status of the task
     *
     * @author CommSy Development Group
     */
    public function setStatus($value): void
    {
        $this->_setValue('status', $value);
    }

    /** set linked item of a task
     * this method sets the linked item of the task.
     *
     * @param cs_item value linked_item of the task
     *
     * @author CommSy Development Group
     */
    public function setItem($item)
    {
        $this->setLinkedItemID($item->getItemID());
    }

    /** set linked item_id of a task
     * this method sets the linked item_id of the task.
     *
     * @param int value linked_item_id of the task
     *
     * @author CommSy Development Group
     */
    public function setLinkedItemID($item_id)
    {
        $this->_setValue('linked_item_id', $item_id);
    }

    /** get linked item_id of a task
     * this method gets the linked item_id of the task.
     *
     * @retrun int value linked_item_id of the task
     *
     * @author CommSy Development Group
     */
    public function getLinkedItemID()
    {
        return $this->_getValue('linked_item_id');
    }

    /** get linked item of a task
     * this method gets the linked item_id of the task.
     *
     * @param object value linked_item of the task
     */
    public function getItem()
    {
        return $this->getLinkedItem();
    }

    /** get linked item
     * this method returns a commsy item which is linked to the task.
     *
     * @return object cs_item a commsy item (cs_*_item)
     */
    public function getlinkedItem()
    {
        $item_id = $this->_getValue('linked_item_id');
        $item_manager = $this->_environment->getItemManager();
        $item = $item_manager->getItem($item_id);
        if (!empty($item)) {
            $manager = $this->_environment->getManager($item->getItemType());
            if (!empty($manager)) {
                $item = $manager->getItem($item->getItemID());
            }
        }

        return $item;
    }

    /** save task
     * this method save the task.
     */
    public function save(): void
    {
        $task_manager = $this->_environment->getTaskManager();
        $this->_save($task_manager);
    }

    /** delete task
     * this method deletes the task.
     */
    public function delete()
    {
        $task_manager = $this->_environment->getTaskManager();
        $this->_delete($task_manager);

        // delete associated annotations
        $this->deleteAssociatedAnnotations();
    }
}
