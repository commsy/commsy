<?php
namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

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
        $this->annotationManager->setContextLimit($roomId);
        $this->annotationManager->setLinkedItemID($linkedItemId);

        $this->annotationManager->select();
        $annotationList = $this->annotationManager->get();

        return array_reverse($annotationList->to_array());
    }

    public function addAnnotation($roomId, $itemId, $description)
    {
        $user = $this->legacyEnvironment->getCurrentUser();
        // create new annotation
        $annotation_manager = $this->legacyEnvironment->getAnnotationManager();
        $annotation_item = $annotation_manager->getNewItem();
        $annotation_item->setContextID($roomId);
        $annotation_item->setCreatorItem($user);
        $annotation_item->setCreationDate(getCurrentDateTimeInMySQL());

        // set modificator and modification date
        $annotation_item->setModificatorItem($user);
        $annotation_item->setModificationDate(getCurrentDateTimeInMySQL());

        $annotation_item->setDescription($description);

        $annotation_item->setLinkedItemID($itemId);

        $annotation_item->save();

    }

    public function markAnnotationsReadedAndNoticed($annotation_list) {
        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $noticed_manager = $this->legacyEnvironment->getNoticedManager();

        // collect an array of all ids and precach
        $id_array = array();
        $annotation = $annotation_list->getFirst();
        while($annotation) {
            $id_array[] = $annotation->getItemID();

            $annotation = $annotation_list->getNext();
        }

        $reader_manager->getLatestReaderByIDArray($id_array);
        $noticed_manager->getLatestNoticedByIDArray($id_array);

        // mark if needed
        $annotation = $annotation_list->getFirst();
        while($annotation) {
            $reader = $reader_manager->getLatestReader($annotation->getItemID());
            if(empty($reader) || $reader['read_date'] < $annotation->getModificationDate()) {
                $reader_manager->markRead($annotation->getItemID(), 0);
            }

            $noticed = $noticed_manager->getLatestNoticed($annotation->getItemID());
            if(empty($noticed) || $noticed['read_date'] < $annotation->getModificationDate()) {
                $noticed_manager->markNoticed($annotation->getItemID(), 0);
            }

            $annotation = $annotation_list->getNext();
        }
    }
}