<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class HashtagController extends Controller
{
    /**
     * @Template("CommsyBundle:Hashtag:show.html.twig")
     */
    public function showAction($roomId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $hashtags = $em->getRepository('CommsyBundle:Labels')
            ->findRoomHashtags($roomId);



// $buzzword_manager = $this->_environment->getLabelManager();
//             $text_converter = $this->_environment->getTextConverter();
//             $params = $this->_environment->getCurrentParameterArray();

//             $buzzword_manager->resetLimits();
//             if ($roomId == null) {
//                 $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
//             } else {
//                 $buzzword_manager->setContextLimit($roomId);
//             }
//             $buzzword_manager->setTypeLimit('buzzword');
//             $buzzword_manager->setGetCountLinks();
//             $buzzword_manager->select();
//             $buzzword_list = $buzzword_manager->get();

//             $buzzword = $buzzword_list->getFirst();
//             while($buzzword) {
//                 $count = $buzzword->getCountLinks();
//                 if($count > 0 || $return_empty) {
//                     if ( isset($params['selbuzzword']) and !empty($params['selbuzzword']) and $buzzword->getItemID() == $params['selbuzzword']){
//                         $return[] = array(
//                             'to_item_id'        => $buzzword->getItemID(),
//                             'name'              => $text_converter->text_as_html_short($buzzword->getName()),
//                             'class_id'          => $this->getBuzzwordSizeLogarithmic($count, 0, 30, 1, 4),
//                             'selected_id'       => $buzzword->getItemID()
//                         );
//                     }else{
//                         $return[] = array(
//                             'to_item_id'        => $buzzword->getItemID(),
//                             'name'              => $text_converter->text_as_html_short($buzzword->getName()),
//                             'class_id'          => $this->getBuzzwordSizeLogarithmic($count, 0, 30, 1, 4),
//                             'selected_id'       => 'no'
//                         );
//                     }
//                 }

//                 $buzzword = $buzzword_list->getNext();
//             }



        return array(
            'hashtags' => $hashtags
        );
    }
}
