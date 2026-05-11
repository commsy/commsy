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
use Symfony\Component\DependencyInjection\ContainerInterface;

class cs_file_item extends cs_item
{
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

    public function setPostFile($post_data)
    {
        $this->setTempName($post_data['tmp_name']);
        $filename = rawurldecode(basename(rawurlencode((string) $post_data['name'])));
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

    public function getTitle(): string
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

    public function setPortalId(int $portalId)
    {
        $this->_setValue('portal_id', $portalId);
    }

    public function getPortalId(): int
    {
        return (int) $this->_getValue('portal_id');
    }

    public function getDisplayName()
    {
        return mb_convert_encoding(rawurldecode((string)$this->_getValue('filename')), 'UTF-8');
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

    /**
     * Returns the name of the file without any directory or extension information
     */
    public function getFilenameWithoutExtension(): string
    {
        $fileInfo = new SplFileInfo($this->getFileName());
        return $fileInfo->getBasename(".{$fileInfo->getExtension()}");
    }

    public function getExtension(): ?string
    {
        $display_name = $this->getDisplayName();
        if (!empty($display_name)) {
            return cs_strtolower(mb_substr(strrchr((string) $display_name, '.'), 1));
        }

        return null;
    }

    /**
     * @return int File size in kilobytes
     */
    public function getFileSize(): int
    {
        $discManager = $this->_environment->getDiscManager();
        $filePath = $discManager->getAbsoluteFilePath(
            $this->getPortalId(),
            $this->getContextID(),
            $this->getDiskFileNameWithoutFolder()
        );

        if (!file_exists($filePath)) {
            return 0;
        }

        if (0 == $this->_getValue('size')) {
            $filesize = filesize($filePath);
            $this->_data['size'] = $filesize ?: 0;
        }

        return round(($this->_getValue('size') + 1023) / 1024);
    }

    public function getDiskFileName(): string
    {
        /** @var ContainerInterface $symfonyContainer */
        global $symfonyContainer;
        $projectDir = $symfonyContainer->getParameter('kernel.project_dir');

        return $projectDir . '/' . $this->getFilePath();
    }

    public function getDiskFileNameWithoutFolder(): string
    {
        $discManager = $this->_environment->getDiscManager();
        return $discManager->getCurrentFileName($this->getFileID(), $this->getExtension());
    }

    public function save(): void
    {
        $manager = $this->_environment->getFileManager();
        $this->_save($manager);
    }

    public function update()
    {
        $manager = $this->_environment->getFileManager();
        $manager->updateItem($this);
    }

    private function _getFileAsString(): string
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

    /**
     * Returns true if the user represented by the given user item is allowed to edit the file,
     * otherwise returns false.
     *
     * Phase 2.6: delegates to {@see \App\Files\FilePermissionChecker::canEdit()}.
     */
    public function mayEdit(cs_user_item $user_item)
    {
        global $symfonyContainer;
        /** @var \App\Files\FilePermissionChecker $checker */
        $checker = $symfonyContainer->get(\App\Files\FilePermissionChecker::class);
        /** @var \App\Repository\UserRepository $userRepository */
        $userRepository = $symfonyContainer->get(\App\Repository\UserRepository::class);
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');

        $actor = $userRepository->findOneByLegacyIdentity(
            $user_item->getUserID(),
            (int) $user_item->getContextID(),
            $user_item->getAuthSource() !== null ? (int) $user_item->getAuthSource() : null,
        );
        if ($actor === null) {
            return false;
        }

        $file = $em->getRepository(\App\Entity\Files::class)->find((int) $this->getFileID());
        if ($file === null) {
            return false;
        }

        return $checker->canEdit($actor, $file);
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
     * Phase 2.6: delegates to {@see \App\Files\FilePermissionChecker::canSee()}.
     */
    public function maySee(cs_user_item $userItem)
    {
        global $symfonyContainer;
        /** @var \App\Files\FilePermissionChecker $checker */
        $checker = $symfonyContainer->get(\App\Files\FilePermissionChecker::class);
        /** @var \App\Repository\UserRepository $userRepository */
        $userRepository = $symfonyContainer->get(\App\Repository\UserRepository::class);
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');

        $actor = $userRepository->findOneByLegacyIdentity(
            $userItem->getUserID(),
            (int) $userItem->getContextID(),
            $userItem->getAuthSource() !== null ? (int) $userItem->getAuthSource() : null,
        );
        if ($actor === null) {
            return false;
        }

        $file = $em->getRepository(\App\Entity\Files::class)->find((int) $this->getFileID());
        if ($file === null) {
            return false;
        }

        // ItemViewChecker's guest fallback only fires when the legacy
        // current context resolves to a Room — mirror that here.
        $currentLegacyContext = $this->_environment->getCurrentContextItem();
        $currentRoom = null;
        if ($currentLegacyContext instanceof cs_room_item) {
            $currentRoom = $em->getRepository(\App\Entity\Room::class)
                ->find($currentLegacyContext->getItemID());
        }

        return $checker->canSee($actor, $file, $currentRoom);
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

    /**
     * May view files for external viewer. Phase 2.6: delegates to
     * {@see \App\Files\FilePermissionChecker::canExternalViewerSee()}.
     * The first-link-only quirk is preserved there 1:1 — see the
     * checker's docblock for the deferred-fix rationale.
     */
    public function mayExternalViewerSeeLinkedItem(string $username): bool
    {
        global $symfonyContainer;
        /** @var \App\Files\FilePermissionChecker $checker */
        $checker = $symfonyContainer->get(\App\Files\FilePermissionChecker::class);
        return $checker->canExternalViewerSee((int) $this->getFileID(), $username);
    }
}
