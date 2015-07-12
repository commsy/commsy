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
        $filterForm = $this->createForm(new MaterialFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_material_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $materialService = $this->get('commsy_legacy.material_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $materialService->setFilterConditions($filterForm);
        }

        // get material list from manager service 
        $materials = $materialService->getListMaterials($roomId, $max, $start);

        $readerService = $this->get('commsy.reader_service');

        $readerList = array();
        foreach ($materials as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        return array(
            'roomId' => $roomId,
            'materials' => $materials,
            'readerList' => $readerList
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
        $filterForm = $this->createForm(new MaterialFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_material_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $materialService = $this->get('commsy_legacy.material_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $materialService->setFilterConditions($filterForm);
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}")
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $materialService = $this->get('commsy_legacy.material_service');
        $itemService = $this->get('commsy.item_service');
        
        $material = $materialService->getMaterial($itemId);
        $sectionList = $material->getSectionList()->to_array();
        
        $itemArray = array($material);
        $itemArray = array_merge($itemArray, $sectionList);

        $readerService = $this->get('commsy.reader_service');
        
        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
            
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        return array(
            'roomId' => $roomId,
            'material' => $materialService->getMaterial($itemId),
            'sectionList' => $sectionList,
            'readerList' => $readerList,
            'modifierList' => $modifierList
        );
    }
}
