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
}