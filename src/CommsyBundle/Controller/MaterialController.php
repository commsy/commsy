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
     * @Route("/room/{roomId}/material/feed/{start}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, Request $request)
    {
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

        // get the material manager service
        $materialService = $this->get('commsy_legacy.material_service');

        // set filter conditions in material manager
        $materialService->setFilterConditions($form);

        // get material list from manager service 
        $materials = $materialService->getListMaterials($roomId, $max, $start);

        return array(
            'roomId' => $roomId,
            'materials' => $materials,
        );
    }

    /**
     * @Route("/room/{roomId}/material")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
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

        return array(
            'roomId' => $roomId,
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}")
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $materialService = $this->get('commsy_legacy.material_service');
        
        $material = $materialService->getMaterial($itemId);
        $sectionList = $material->getSectionList()->to_array();
        
        return array(
            'roomId' => $roomId,
            'material' => $materialService->getMaterial($itemId),
            'sectionList' => $sectionList
        );
    }
}
