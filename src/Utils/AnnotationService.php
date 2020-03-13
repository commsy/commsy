<?php
namespace App\Utils;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;

class AnnotationService
{
    private $legacyEnvironment;

    private $annotationManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->annotationManager = $this->legacyEnvironment->getAnnotationManager();
        $this->annotationManager->reset();
    }

    public function getListAnnotations($roomId, $linkedItemId, $max, $start)
    {
        /**
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
        if(empty($reader) || $reader['read_date'] < $annotationItem->getModificationDate()) {
            $readerManager->markRead($annotationItem->getItemID(), 0);
        }

        $noticed = $noticedManager->getLatestNoticed($annotationItem->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $annotationItem->getModificationDate()) {
            $noticedManager->markNoticed($annotationItem->getItemID(), 0);
        }
        return $annotationItem->getItemID();
    }

    public function markAnnotationsReadedAndNoticed($annotationList) {
        $readerManager = $this->legacyEnvironment->getReaderManager();
        $noticedManager = $this->legacyEnvironment->getNoticedManager();

        // collect an array of all ids and precach
        $idArray = array();
        $annotation = $annotationList->getFirst();
        while($annotation) {
            $idArray[] = $annotation->getItemID();

            $annotation = $annotationList->getNext();
        }

        $readerManager->getLatestReaderByIDArray($idArray);
        $noticedManager->getLatestNoticedByIDArray($idArray);

        // mark if needed
        $annotation = $annotationList->getFirst();
        while($annotation) {
            $reader = $readerManager->getLatestReader($annotation->getItemID());
            if(empty($reader) || $reader['read_date'] < $annotation->getModificationDate()) {
                $readerManager->markRead($annotation->getItemID(), 0);
            }

            $noticed = $noticedManager->getLatestNoticed($annotation->getItemID());
            if(empty($noticed) || $noticed['read_date'] < $annotation->getModificationDate()) {
                $noticedManager->markNoticed($annotation->getItemID(), 0);
            }

            $annotation = $annotationList->getNext();
        }
    }
}