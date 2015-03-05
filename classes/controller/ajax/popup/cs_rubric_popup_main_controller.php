<?php

class cs_rubric_popup_main_controller {


    public function saveBuzzwords($environment, $item, $formBuzzwords)
    {     
        // buzzwords
        $new_buzzword = '';
        $buzzwords = array();
        $buzzword_manager = $environment->getLabelManager();
        $buzzword_manager->resetLimits();
        $buzzword_manager->setContextLimit($environment->getCurrentContextID());
        $buzzword_manager->setTypeLimit('buzzword');
        $buzzword_manager->select();
        $buzzword_list = $buzzword_manager->get();
        $buzzword_ids = $buzzword_manager->getIDArray();
        // check if form buzzword id are existing
        if (isset($formBuzzwords)){
            foreach($formBuzzwords as $buzzword){
                if (in_array($buzzword,$buzzword_ids)){
                    $buzzwords[] =  $buzzword;
                }
            }
        }
        if(!empty($buzzwords)) {
            $item->setBuzzwordListByID($buzzwords);
            $item->save();
            // return $buzzwords;
        } else {
            $item->setBuzzwordListByID(array());
            $item->save();
        }
    }
}
?>