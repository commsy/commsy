<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

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

    /**
     * @Route("/room/{roomId}/material/new")
     * @Template()
     */
    public function newAction($roomId, Request $request)
    {

    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        // get material from MaterialService
        $materialService = $this->get('commsy_legacy.material_service');
        $materialItem = $materialService->getMaterial($itemId);

        if (!$materialItem) {
            throw $this->createNotFoundException('No material found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.material');
        $materialData = $transformer->transform($materialItem);

        $form = $this->createForm('material', $materialData, array(
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $materialItem = $transformer->applyTransformation($materialItem, $form->getData());

            $materialItem->save();

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
            
            return $this->redirectToRoute('commsy_material_savematerial', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView()
        );
    }
    
    /**
     * @Route("/room/{roomId}/material/{itemId}/editsection")
     * @Template("CommsyBundle:Section:editSection.html.twig")
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editSectionAction($roomId, $itemId, Request $request)
    {
        // get material from MaterialService
        $materialService = $this->get('commsy_legacy.material_service');
        $sectionItem = $materialService->getSection($itemId);

        if (!$sectionItem) {
            throw $this->createNotFoundException('No section found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.material');
        $sectionData = $transformer->transform($sectionItem);

        $form = $this->createForm('section', $sectionData, array(
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $sectionItem = $transformer->applyTransformation($sectionItem, $form->getData());

            $sectionItem->save();

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
            
            return $this->redirectToRoute('commsy_material_savesection', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView()
        );
    }
    
    /**
     * @Route("/room/{roomId}/material/{itemId}/savematerial")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveMaterialAction($roomId, $itemId, Request $request)
    {
        $materialService = $this->get('commsy_legacy.material_service');
        $itemService = $this->get('commsy.item_service');
        
        $material = $materialService->getMaterial($itemId);

        $itemArray = array($material);

        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        return array(
            'roomId' => $roomId,
            'material' => $material,
            'modifierList' => $modifierList
        );
    }
    
    /**
     * @Route("/room/{roomId}/material/{itemId}/savesection")
     * @Template("CommsyBundle:Section:saveSection.html.twig")
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveSectionAction($roomId, $itemId, Request $request)
    {
        $materialService = $this->get('commsy_legacy.material_service');
        $itemService = $this->get('commsy.item_service');
        
        $section = $materialService->getSection($itemId);

        $itemArray = array($section);

        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        return array(
            'roomId' => $roomId,
            'section' => $section,
            'modifierList' => $modifierList
        );
    }
}
