<?php

namespace Tests\Cron;

use Tests\DatabaseTestCase;


class AnnotationsInNewsletterTest extends DatabaseTestCase
{
    protected $portal;
    
    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet('tests/Cron/delete_inactive_user_dataset.xml');
    }

    /**
     * Tests portal settings for password expiration.
     */
    public function testAnnotationsInNewsletter()
    {
        global $environment;
        $portal = $this->setUpPortal();

        $testMaterialId = 142; // material "Material" from test dump
        $testMaterialId2 = 144; // material "Material" from test dump

        // add new annotation to existing material
        $annotationManager = $environment->getAnnotationManager();

        for ($i = 0; $i < 3; $i++) {
            $annotationItem = $annotationManager->getNewItem();
            $annotationItem->setContextId(134);
            $annotationItem->setTitle('TEST Newsletter #'.$i);
            $annotationItem->setLinkedItemId($testMaterialId);
            $annotationItem->save();
        }

        for ($i = 0; $i < 3; $i++) {
            $annotationItem = $annotationManager->getNewItem();
            $annotationItem->setContextId(117);
            $annotationItem->setTitle('TEST Newsletter 2 #'.$i);
            $annotationItem->setLinkedItemId($testMaterialId2);
            $annotationItem->save();
        }

        $materialManager = $environment->getMaterialManager();
        $materialItem = $materialManager->getItem($testMaterialId);
        $annotationsArray = $materialItem->getAnnotationList()->to_array();

        $foundAnnotation = false;
        foreach ($annotationsArray as $tempAnnotationItem) {
            if ($tempAnnotationItem->getItemId() == $annotationItem->getItemId()) {
                $foundAnnotation = true;
            }
        }
        //$this->assertTrue($foundAnnotation);

        // simulate cron
        $portal->resetUserList();
        $portal_users = $portal->getUserList();
        $portal_user = $portal_users->getFirst();
        while ($portal_user){
            $privateRoomUserItem = $portal_user->getRelatedPrivateRoomUserItem();
            if ($privateRoomUserItem) {
                $privateRoomItem = $privateRoomUserItem->getContextItem();
                $privateRoomItem->setPrivateRoomNewsletterActivity('daily');
                $cronResult = $privateRoomItem->_sendPrivateRoomNewsletter();

                $this->assertTrue(true);
            }
            $portal_user = $portal_users->getNext();
        }

        // check is new annotation is announced in newsletter
    }

    private function setUpPortal () {
        global $environment;
        $environment->setCurrentContextID(99);
        $environment->setCurrentPortalID(101);
        $portalManager = $environment->getPortalManager();
        $portalManager->setCacheOff();
        $portalManager->reset();
        return $portalManager->getItem(101);
    }
}