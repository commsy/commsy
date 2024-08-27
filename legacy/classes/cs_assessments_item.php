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

/** class for assessments
 * this class implements an assessments object.
 */
class cs_assessments_item extends cs_item
{

    /** constructor
     * the only available constructor, initial values for internal variables.
     */
    public function __construct($environment)
    {
        $this->_environment = $environment;
        $this->_type = CS_ASSESSMENT_TYPE;
    }

    /** is the type of the list = $type ?
     * this method returns a boolean expressing if type of the list is $type or not.
     *
     * @param string type string to compare with type of list (_type)
     *
     * @return bool true - type of this list is $type
     *              false - type of this list is not $type
     */
    public function isA($type)
    {
        return $this->_type == $type;
    }

    /** return the type of the object = link
     * this method returns the type of the object = link.
     *
     * @return string type of the object link
     *
     * @author CommSy Development Group
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * sets item id of item.
     *
     * @param int item_it
     */
    public function setItemID($value)
    {
        $this->_data['item_id'] = (int) $value;
    }

    /**
     * sets context id of item.
     *
     * @param int context_id
     */
    public function setContextID($value)
    {
        $this->_data['context_id'] = (int) $value;
    }

    /**
     * sets creator id of item.
     *
     * @param int creator_id
     */
    public function setCreatorID($value): void
    {
        $this->_data['creator_id'] = (int) $value;
    }

    /**
     * sets deleter id of item.
     *
     * @param int deleter_id
     */
    public function setDeleterID($value): void
    {
        $this->_data['deleter_id'] = (int) $value;
    }

    /**
     * returns deleter id of item.
     *
     * @return int deleter_id
     */
    public function getDeleterID()
    {
        return $this->_getValue('deleter_id');
    }

    /**
     * sets creation date of item.
     *
     * @param date creation_date
     */
    public function setCreationDate($value)
    {
        $this->_data['creation_date'] = $value;
    }

    /**
     * returns creation date of item.
     *
     * @return date creation_date
     */
    public function getCreationDate()
    {
        return $this->_getValue('creation_date');
    }

    /**
     * sets deletion date of item.
     *
     * @param date deletion_date
     */
    public function setDeletionDate($value)
    {
        $this->_data['deletion_date'] = $value;
    }

    /**
     * returns deletion date of item.
     *
     * @return date deletion_date
     */
    public function getDeletionDate()
    {
        return $this->_getValue('deletion_date');
    }

    /**
     * sets id of the linked item.
     *
     * @param int item_link_id
     */
    public function setItemLinkID($value)
    {
        $this->_data['item_link_id'] = (int) $value;
    }

    /**
     * returns id of the linked item.
     *
     * @return int item_link_id
     */
    public function getItemLinkID()
    {
        return $this->_getValue('item_link_id');
    }

    /**
     * sets assessment of item.
     *
     * @param int assessment
     */
    public function setAssessment($value)
    {
        $this->_data['assessment'] = (int) $value;
    }

    /**
     * returns assessment of item.
     */
    public function getAssessment()
    {
        return $this->_getValue('assessment');
    }

    /** return a value of the link, INTERNAL
     * this method returns a value of the link.
     *
     * @return string a value the link
     */
    public function _getValue($key)
    {
        if (!empty($this->_data[$key])) {
            $value = $this->_data[$key];
        } else {
            $value = '';
        }

        return $value;
    }

    public function save(): void
    {
        $manager = $this->_environment->getManager($this->_type);
        $manager->saveItem($this);
    }
}
