<?php

namespace Tests\Cron;

use Tests\DatabaseTestCase;


class PasswordExpiredTest extends DatabaseTestCase
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
    public function testPasswordExpirationPortalSettings()
    {
        $portal = $this->setUpPortal();
        
        // Portal has no password expiration settings
        $this->assertEquals(0, $portal->getPasswordExpiration());
        $this->assertEquals(0, $portal->getDaysBeforeExpiringPasswordSendMail());
        $this->assertEquals(0, $portal->setPasswordGeneration());
        
        $this->setPasswordSettingsOnPortal($portal);
        
        // Portal has password expiration settings
        $this->assertEquals(1, $portal->getPasswordExpiration());
        $this->assertEquals(2, $portal->getDaysBeforeExpiringPasswordSendMail());
        $this->assertEquals(3, $portal->getPasswordGeneration());
        $this->assertTrue($portal->isPasswordExpirationActive());
    }
    
    /**
     * Tests portal settings for password expiration.
     */
    public function testPasswordExpiration()
    {
        global $environment;
        
        $portal = $this->setUpPortal();
        
        $this->setPasswordSettingsOnPortal($portal);
        
        $portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
			$this->assertEquals('', $portal_user->getPasswordExpireDate());
			$portal_user = $portal_users->getNext();
		}
        
        $expireDate = getCurrentDateTimePlusDaysInMySQL($portal->getPasswordExpiration());
        
        $portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
            $portal_user->setPasswordExpireDate($portal->getPasswordExpiration());
			$portal_user->save();
			$portal_user = $portal_users->getNext();
		}
        
        $portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
			$this->assertEquals($expireDate, $portal_user->getPasswordExpireDate());
			$portal_user = $portal_users->getNext();
		}
        
        $serverItem = $environment->getServerItem();
        $cronArray = $serverItem->_cronCheckPasswordExpiredSoon();
        
        $portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
    		$successKey = 'success_'.$portal->getItemId().'_'.$portal_user->getItemId();
			$this->assertTrue(in_array($successKey, array_keys($cronArray)));
			$this->assertTrue($cronArray[$successKey]);
			$portal_user = $portal_users->getNext();
		}
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
    
    private function setPasswordSettingsOnPortal(&$portal) {
        $portal->setPasswordExpiration(1);
        $portal->setDaysBeforeExpiringPasswordSendMail(2);
        $portal->setPasswordGeneration(3);
        $portal->save();
    }
}