<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Filter\MaterialFilterType;

class MaterialController extends Controller
{
    /**
     * @Route("/room/{roomId}/material/{itemId}")
     * @Template()
     */
    public function indexAction($roomId, $itemId, Request $request)
    {
        return array();
    }

    /**
     * @Route("/room/{roomId}/material")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // get the material manager service
        $materialManager = $this->get('commsy_legacy.material_manager');

        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $form = $this->createForm(new MaterialFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_material_list', array('roomId' => $roomId)),
            'method' => 'GET',
        ));

        // check query for form data
        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->get($form->getName()));
        }

        // set filter conditions in material manager
        $materialManager->setFilterConditions($form);

        // get material list from manager service 
        $materials = $materialManager->getListMaterials($roomId);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $materials,
            $request->query->getInt('page', 1),
            10
        );

        return array(
            'roomId' => $roomId,
            'pagination' => $pagination,
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}")
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        return array();
    }
}
