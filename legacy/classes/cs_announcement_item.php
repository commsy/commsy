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

/* upper class of the announcement item
 */

use App\Entity\Announcement;

/** class for a announcement
 * this class implements a announcement item.
 */
class cs_announcement_item extends cs_item
{
    /** constructor: cs_announcement_item
     * the only available constructor, initial values for internal variables.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = CS_ANNOUNCEMENT_TYPE;
    }

    /** Checks and sets the data of the item.
     *
     * @param $data_array
     *
     * @author CommSy Development Group
     */
    public function _setItemData($data_array)
    {
        // not yet implemented
        $this->_data = $data_array;
    }

    /** get title of an announcement
     * this method returns the title of the announcement.
     *
     * @return string title of an announcement
     *
     * @author CommSy Development Group
     */
    public function getTitle()
    {
        if ('-1' == $this->getPublic()) {
            $translator = $this->_environment->getTranslationObject();

            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE');
        } else {
            return $this->_getValue('title');
        }
    }

    /** set title of an announcement
     * this method sets the title of the announcement.
     *
     * @param string value title of the announcement
     *
     * @author CommSy Development Group
     */
    public function setTitle(string $value)
    {
        // sanitize title
        $converter = $this->_environment->getTextConverter();
        $value = htmlentities($value);
        $value = $converter->sanitizeHTML($value);
        $this->_setValue('title', $value);
    }

    /** get description of an announcement
     * this method returns the description of the announcement.
     *
     * @return string description of an announcement
     *
     * @author CommSy Development Group
     */
    public function getDescription()
    {
        if ('-1' == $this->getPublic()) {
            $translator = $this->_environment->getTranslationObject();

            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION');
        } else {
            return $this->_getValue('description');
        }
    }

    /** set description of an announcement
     * this method sets the description of the announcement.
     *
     * @param string value description of the announcement
     *
     * @author CommSy Development Group
     */
    public function setDescription($value)
    {
        // sanitize description
        $converter = $this->_environment->getTextConverter();
        $value = $converter->sanitizeFullHTML($value);
        $this->_setValue('description', $value);
    }

    /** set setfirstdate of an announcement
     * this method sets the setfirstdate of the announcement.
     *
     * @param date value setfirstdate of the announcement
     *
     * @author CommSy Development Group
     */
    public function setFirstDateTime($value)
    {
        $this->_setValue('creation_date', $value);
    }

    /** get description of an announcement
     * this method returns the description of the announcement.
     *
     * @return string description of an announcement
     *
     * @author CommSy Development Group
     */
    public function getFirstDateTime()
    {
        return $this->_getValue('creation_date');
    }

    /** set setfirstdate of an announcement
     * this method sets the setfirstdate of the announcement.
     *
     * @param date value setfirstdate of the announcement
     *
     * @author CommSy Development Group
     */
    public function setSecondDateTime($value)
    {
        $this->_setValue('enddate', $value);
    }

    /** get description of an announcement
     * this method returns the description of the announcement.
     *
     * @return string description of an announcement
     *
     * @author CommSy Development Group
     */
    public function getSecondDateTime()
    {
        return $this->_getValue('enddate');
    }

    public function save()
    {
        $announcement_manager = $this->_environment->getAnnouncementManager();
        $this->_save($announcement_manager);
        $this->_saveFiles();     // this must be done before saveFileLinks
        $this->_saveFileLinks(); // this must be done after saving item so we can be sure to have an item id

        $this->updateElastic();
    }

    public function updateElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_announcement');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(Announcement::class);

        $this->replaceElasticItem($objectPersister, $repository);
    }

     /** delete announcement
      * this method deletes the announcement.
      */
     public function delete()
     {
         global $symfonyContainer;

         /** @var \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcer */
         $eventDispatcer = $symfonyContainer->get('event_dispatcher');

         $itemDeletedEvent = new \App\Event\ItemDeletedEvent($this);
         $eventDispatcer->dispatch($itemDeletedEvent, \App\Event\ItemDeletedEvent::NAME);

         $manager = $this->_environment->getAnnouncementManager();
         $this->_delete($manager);

         // delete associated annotations
         $this->deleteAssociatedAnnotations();

         $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_announcement');
         $em = $symfonyContainer->get('doctrine.orm.entity_manager');
         $repository = $em->getRepository(Announcement::class);

         $this->deleteElasticItem($objectPersister, $repository);
     }

    /** asks if item is editable by everybody or just creator.
     *
     * @param value
     *
     * @author CommSy Development Group
     */
    public function isPublic()
    {
        if (1 == $this->_getValue('public')) {
            return true;
        } else {
            return false;
        }
    }

    /** sets if announcement is editable by everybody or just creator.
     *
     * @param value
     *
     * @author CommSy Development Group
     */
    public function setPublic($value)
    {
        $this->_setValue('public', $value);
    }

    public function copy()
    {
        $copy = $this->cloneCopy();
        $copy->setItemID('');
        $copy->setFileList($this->_copyFileList());
        $copy->setContextID($this->_environment->getCurrentContextID());
        $user = $this->_environment->getCurrentUserItem();
        $copy->setCreatorItem($user);
        $copy->setModificatorItem($user);
        $list = new cs_list();
        $copy->setGroupList($list);
        $copy->setTopicList($list);
        $copy->save();

        return $copy;
    }

    public function cloneCopy()
    {
        $clone_item = clone $this; // "clone" needed for php5
        $group_list = $this->getGroupList();
        $clone_item->setGroupList($group_list);
        $topic_list = $this->getTopicList();
        $clone_item->setTopicList($topic_list);

        return $clone_item;
    }
}
