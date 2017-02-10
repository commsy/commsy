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
        
        
        // set initial expire date on portal users
        $portal->resetUserList();
        $portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
			$this->assertEquals('', $portal_user->getPasswordExpireDate());
			$portal_user = $portal_users->getNext();
		}
        
        $expireDate = getCurrentDateTimePlusDaysInMySQL($portal->getPasswordExpiration());
        
        $portal->resetUserList();
        $portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
            $portal_user->setPasswordExpireDate($portal->getPasswordExpiration());
			$portal_user->save();
			$portal_user = $portal_users->getNext();
		}
        
        $portal->resetUserList();
        $portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
			$this->assertEquals(substr($expireDate, 0, 17), substr($portal_user->getPasswordExpireDate(), 0, 17)); // compare without seconds -> runtime of test might make this assertion false
			$portal_user = $portal_users->getNext();
		}
        
        
        // "run cron" to test sending the password change preparation emails.
        $serverItem = $environment->getServerItem();
        $cronArray = $serverItem->_cronCheckPasswordExpiredSoon();
        
        $portal->resetUserList();
        $portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
    		$successKey = 'success_'.$portal->getItemId().'_'.$portal_user->getItemId();
			$this->assertTrue(in_array($successKey, array_keys($cronArray)));
			$this->assertTrue($cronArray[$successKey]);
			$portal_user = $portal_users->getNext();
		}
		
		$portal->resetUserList();
		$portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
            $portal_user->setPasswordExpireDate(-1);
			$portal_user->save();
			$this->assertFalse($portal_user->isPasswordExpiredEmailSend());
			$portal_user = $portal_users->getNext();
		}
		
		
		// "run cron" to change the passwords.
		$cronArray = $serverItem->_cronCheckPasswordExpired();
		
		$portal->resetUserList();
		$portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
    		$successKey = 'success_'.$portal->getItemId().'_'.$portal_user->getItemId();
			$this->assertTrue(in_array($successKey, array_keys($cronArray)));
			$this->assertTrue($cronArray[$successKey]);
			$this->assertTrue($portal_user->isPasswordExpiredEmailSend());
			$portal_user = $portal_users->getNext();
		}
		
		
		// set expitation date to test if send email flag is reset to null
		$portal->resetUserList();
		$portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
    		$portal_user->setPasswordExpireDate($expireDate);
    		$portal_user->save();
    		$portal_user = $portal_users->getNext();
		}
		
		$portal->resetUserList();
		$portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
			$this->assertFalse($portal_user->isPasswordExpiredEmailSend());
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