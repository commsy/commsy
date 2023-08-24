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

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_annotations_manager;
use cs_environment;
use cs_list;

class AnnotationService
{
    private readonly cs_environment $legacyEnvironment;

    private readonly cs_annotations_manager $annotationManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->annotationManager = $this->legacyEnvironment->getAnnotationManager();
        $this->annotationManager->reset();
    }

    public function getListAnnotations($roomId, $linkedItemId, $max, $start)
    {
        /*
         * Annotating entries in a portfolio of another user results in annotations with the context id of the
         * user's private room who annotated, not the context of the private room the item belongs to. Setting the
         * context limit to null will remove the query restrictions on the context id.
         */
        $this->annotationManager->setContextLimit(null);

        $this->annotationManager->setLinkedItemID($linkedItemId);

        $this->annotationManager->select();
        $annotationList = $this->annotationManager->get();

        return array_reverse($annotationList->to_array());
    }

    public function addAnnotation($roomId, $itemId, $description)
    {
        $readerManager = $this->legacyEnvironment->getReaderManager();
        $noticedManager = $this->legacyEnvironment->getNoticedManager();

        $user = $this->legacyEnvironment->getCurrentUser();
        // create new annotation
        $annotationManager = $this->legacyEnvironment->getAnnotationManager();
        $annotationItem = $annotationManager->getNewItem();
        $annotationItem->setContextID($roomId);
        $annotationItem->setCreatorItem($user);
        $annotationItem->setCreationDate(getCurrentDateTimeInMySQL());

        // set modificator and modification date
        $annotationItem->setModificatorItem($user);
        $annotationItem->setModificationDate(getCurrentDateTimeInMySQL());

        $annotationItem->setDescription($description);

        $annotationItem->setLinkedItemID($itemId);

        $annotationItem->save();

        $reader = $readerManager->getLatestReader($annotationItem->getItemID());
        if (empty($reader) || $reader['read_date'] < $annotationItem->getModificationDate()) {
            $readerManager->markRead($annotationItem->getItemID(), 0);
        }

        $noticed = $noticedManager->getLatestNoticed($annotationItem->getItemID());
        if (empty($noticed) || $noticed['read_date'] < $annotationItem->getModificationDate()) {
            $noticedManager->markNoticed($annotationItem->getItemID(), 0);
        }

        return $annotationItem->getItemID();
    }

    public function markAnnotationsReadedAndNoticed(cs_list $annotationList)
    {
        $readerManager = $this->legacyEnvironment->getReaderManager();
        $noticedManager = $this->legacyEnvironment->getNoticedManager();

        // collect an array of all ids and precache
        $idArray = $annotationList->getIDArray();

        $readerManager->getLatestReaderByIDArray($idArray);
        $noticedManager->getLatestNoticedByIDArray($idArray);

        // mark if needed
        foreach ($annotationList as $annotation) {
            $reader = $readerManager->getLatestReader($annotation->getItemID());
            if (empty($reader) || $reader['read_date'] < $annotation->getModificationDate()) {
                $readerManager->markRead($annotation->getItemID(), 0);
            }

            $noticed = $noticedManager->getLatestNoticed($annotation->getItemID());
            if (empty($noticed) || $noticed['read_date'] < $annotation->getModificationDate()) {
                $noticedManager->markNoticed($annotation->getItemID(), 0);
            }
        }
    }
}
