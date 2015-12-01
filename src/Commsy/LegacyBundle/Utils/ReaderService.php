<?php

namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class ReaderService
{
    private $legacyEnvironment;

    private $readerManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->readerManager = $this->legacyEnvironment->getEnvironment()->getReaderManager();
    }

    public function getLatestReader($itemId)
    {
        $this->readerManager->resetLimits();
        return $this->readerManager->getLatestReader($itemId);
    }

    public function getChangeStatus($itemId) {
      $current_user = $this->legacyEnvironment->getEnvironment()->getCurrentUserItem();
      $return = '';
      if ($current_user->isUser()) {
         $readerManager = $this->readerManager;
         $reader = $readerManager->getLatestReader($itemId);
         $itemManager = $this->legacyEnvironment->getEnvironment()->getItemManager();
         $item = $itemManager->getItem($itemId);
         if ( empty($reader) ) {
            $return = 'new';
         } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
            $return = 'changed';
         } 


         if ($return == ''){

           $annotation_list = $item->getAnnotationList();
           $anno_item = $annotation_list->getFirst();
           $new = false;
           $changed = false;
           $date = "0000-00-00 00:00:00";
           while ( $anno_item ) {
                $reader = $readerManager->getLatestReader($anno_item->getItemID());
                if ( empty($reader) ) {
                 if ($date < $anno_item->getModificationDate() ) {
                       $new = true;
                       $changed = false;
                       $date = $anno_item->getModificationDate();
                 }
                } elseif ( $reader['read_date'] < $anno_item->getModificationDate() ) {
                 if ($date < $anno_item->getModificationDate() ) {
                       $new = false;
                       $changed = true;
                       $date = $anno_item->getModificationDate();
                 }
                }
                $anno_item = $annotation_list->getNext();
           }
           if ( $new ) {
                   $return ='new_annotation';
           } else if ( $changed ) {
                   $return = 'changed_annotation';
           } 
        } 

      } 
      return $return;
   }

    
    public function getLatestReaderForUserByID($itemId, $userId)
    {
        $this->readerManager->resetLimits();
        return $this->readerManager->getLatestReaderForUserByID($itemId, $userId);
    }
}