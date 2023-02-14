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

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Mime\MimeTypes;

class cs_file_item extends cs_item
{
    private ?int $_portal_id = null;

    /** constructor: cs_file_item
     * the only available constructor, initial values for internal variables.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = 'file';
    }

    /* There was a bug in CommSy so context ID of an item were not
       saved correctly. This method is a workaround for file item db entries
       with context_id of 0. */
    public function getContextID(): int
    {
        $context_id = parent::getContextID();
        if (0 == $context_id) {
            $context_id = $this->_environment->getCurrentContextID();
        }

        return $context_id;
    }

    public function setPortalID($value)
    {
        $this->_portal_id = (int)$value;
    }

    public function getPortalID()
    {
        return $this->_portal_id;
    }

    public function setPostFile($post_data)
    {
        $this->setTempName($post_data['tmp_name']);
        $filename = rawurldecode(basename(rawurlencode($post_data['name'])));
        $filename = str_replace(' ', '_', $filename);
        $this->setFileName($filename);
    }

    /** set file_id of the file
     * this method sets the file_id of the file.
     *
     * @param int $value file_id of the file
     */
    public function setFileID($value)
    {
        $this->_data['files_id'] = $value;
    }

    /** get file_id of the file
     * this method returns the file_id of the file.
     *
     * @return int file_id of the file
     */
    public function getFileID()
    {
        return $this->_getValue('files_id');
    }

    public function getTitle()
    {
        return $this->getFileName();
    }

    public function setFileName($value)
    {
        $this->_setValue('filename', $value);
    }

    public function getFileName()
    {
        return $this->_getValue('filename');
    }

    public function setFilePath($value)
    {
        $this->_setValue('filepath', $value);
    }

    public function getFilePath()
    {
        return $this->_getValue('filepath');
    }

    public function getDisplayName()
    {
        $temp_display_name = rawurldecode($this->_getValue('filename'));

        return cs_utf8_encode($temp_display_name);
    }

    public function setTempKey($value)
    {
        $this->_setExtra('TEMP_KEY', (string)$value);
    }

    public function setTempName($value)
    {
        $this->_data['tmp_name'] = $value;
    }

    public function getTempName()
    {
        return $this->_getValue('tmp_name');
    }

    public function getMime(): string
    {
        $fileInfo = new SplFileInfo($this->getDisplayName());
        $mimeTypes = (new MimeTypes())->getMimeTypes($fileInfo->getExtension());

        return $mimeTypes[0] ?? 'application/octetstream';
    }

    public function getExtension()
    {
        $display_name = $this->getDisplayName();
        if (!empty($display_name)) {
            return cs_strtolower(mb_substr(strrchr($display_name, '.'), 1));
        }
    }

    public function getUrl()
    {
        $params = [];
        $params['iid'] = $this->_data['files_id'];
        global $c_single_entry_point;

        return curl($this->getContextID(), 'material', 'getfile', $params, '', $this->_data['filename'], $c_single_entry_point);
    }

    public function getFileSize()
    {
        $discManager = $this->_environment->getDiscManager();
        if (!$discManager->existsFile($this->getDiskFileNameWithoutFolder())) {
            return 0;
        }

        if (0 == $this->_getValue('size')) {
            $diskFileName = $this->getDiskFileName();
            $filesize = filesize($diskFileName);
            $this->_data['size'] = $filesize ?: 0;
        }

        return round(($this->_getValue('size') + 1023) / 1024, 0);
    }

    public function getDiskFileName(): string
    {
        // the files context id is the containing room
        $roomId = $this->getContextID();

        $roomManager = $this->_environment->getRoomManager();
        $parentRoom = $roomManager->getItem($roomId);
        if (!$parentRoom) {
            $privateRoomManager = $this->_environment->getPrivateRoomManager();
            $parentRoom = $privateRoomManager->getItem($roomId);
        }

        // the room context is either the portal or (unfortunately) in case of a user room the parent project room
        $contextId = $parentRoom->getContextID();

        $discManager = $this->_environment->getDiscManager();
        return $discManager->getAbsoluteFilePath($contextId, $roomId, $this->getDiskFileNameWithoutFolder());
    }

    public function getDiskFileNameWithoutFolder(): string
    {
        $discManager = $this->_environment->getDiscManager();
        return $discManager->getCurrentFileName($this->getFileID(), $this->getExtension());
    }

    public function save()
    {
        $manager = $this->_environment->getFileManager();
        return $this->_save($manager);
    }

    public function update()
    {
        $manager = $this->_environment->getFileManager();
        $manager->updateItem($this);
    }

    private function _getFileAsString()
    {
        $disc_manager = $this->_environment->getDiscManager();
        $portal_id = $this->getPortalID();
        if (isset($portal_id) and !empty($portal_id)) {
            $disc_manager->setPortalID($portal_id);
        }
        return $disc_manager->getFileAsString($this->getDiskFileName());
    }

    public function getString()
    {
        return $this->_getFileAsString();
    }

    /** Get the linked items of the file.
     *
     * @return ArrayCollection an array collection of \cs_item objects that are linked to this file
     */
    public function getLinkedItems(): ArrayCollection
    {
        // get a list of \cs_link_item_file objects
        $linkItemManager = $this->_environment->getLinkItemFileManager();
        $linkItemManager->resetLimits();
        $linkItemManager->setFileIDLimit($this->getFileID());
        $linkItemManager->select();
        $linkItemList = $linkItemManager->get();

        // assemble array collection of corresponding \cs_item objects
        $itemCollection = new ArrayCollection();

        foreach ($linkItemList as $linkItem) {
            $linkedItem = $linkItem->getLinkedItem();
            if ($linkedItem) {
                $itemCollection->add($linkedItem);
            }
        }

        return $itemCollection;
    }

    public function _delete($manager)
    {
        $manager->delete($this->getFileID());
    }

    public function deleteReally()
    {
        $manager = $this->_environment->getFileManager();
        $manager->deleteReally($this);
    }

    /**
     * Returns true if the user represented by the given user item is allowed to edit the file,
     * otherwise returns false.
     *
     * @return bool
     */
    public function mayEdit(cs_user_item $user_item)
    {
        $access = false;
        if (!$user_item->isOnlyReadUser()) {
            if ($user_item->isRoot() or
                ($user_item->getContextID() == $this->getContextID()
                    and ($user_item->isModerator()
                        or ($user_item->isUser()
                            and ($user_item->getItemID() == $this->getCreatorID()
                                or $this->mayEditLinkedItem($user_item)
                            )
                        )
                    )
                )
            ) {
                $access = true;
            }
        }

        return $access;
    }

    /**
     * Returns true if the user represented by the given user item is allowed to edit any of
     * the file's linked items, otherwise returns false.
     */
    public function mayEditLinkedItem(cs_user_item $userItem): bool
    {
        $itemCollection = $this->getLinkedItems();
        if (!isset($itemCollection) or $itemCollection->isEmpty()) {
            return false;
        }

        foreach ($itemCollection as $item) {
            if ($item->mayEdit($userItem)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the user represented by the given user item is allowed to see the file,
     * otherwise returns false.
     *
     * @return bool
     */
    public function maySee(cs_user_item $userItem)
    {
        // a user who's allowed to see any of this file's linked items may also see this file
        return $this->maySeeLinkedItem($userItem);
    }

    /**
     * Returns true if the user represented by the given user item is allowed to see any of
     * the file's linked items, otherwise returns false.
     */
    public function maySeeLinkedItem(cs_user_item $userItem): bool
    {
        $itemCollection = $this->getLinkedItems();
        if (!isset($itemCollection) or $itemCollection->isEmpty()) {
            return false;
        }

        foreach ($itemCollection as $item) {
            if (!$item->getHasOverwrittenContent() && $item->maySee($userItem)) {
                return true;
            }
        }

        return false;
    }

    public function mayPortfolioSeeLinkedItem(cs_user_item $userItem)
    {
        $itemCollection = $this->getLinkedItems();
        if (!isset($itemCollection) or $itemCollection->isEmpty()) {
            return false;
        }

        foreach ($itemCollection as $item) {
            if ($item->mayPortfolioSee($userItem->getUserID())) {
                return true;
            }
        }

        return false;
    }

    /**
     * May view files for external viewer.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function mayExternalViewerSeeLinkedItem(string $username): bool
    {
        $itemCollection = $this->getLinkedItems();
        if (!isset($itemCollection) or $itemCollection->isEmpty()) {
            return false;
        }
        $itemId = $itemCollection[0]->getItemID();

        return $this->mayExternalSee($itemId, $username);
    }
}
