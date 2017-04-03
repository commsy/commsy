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
        $this->assertEquals(7, $portal->getPasswordExpiration());
        $this->assertEquals(3, $portal->getDaysBeforeExpiringPasswordSendMail());
        $this->assertEquals(3, $portal->getPasswordGeneration());
        $this->assertTrue($portal->isPasswordExpirationActive());
    }
    
    /**
     * Tests portal settings for password expiration.
     */
    public function testPasswordExpiration()
    {
        \DateTesting::$dateTime = "2017-03-23 12:13:14";

        global $environment;
        global $c_password_expiration_user_ids_ignore;
        
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
        
        $expireDate = getCurrentDateTimePlusDaysInMySQL($portal->getPasswordExpiration(), true);

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
			$this->assertEquals($expireDate, $portal_user->getPasswordExpireDate());
			$portal_user = $portal_users->getNext();
		}

        $serverItem = $environment->getServerItem();

        // "run cron" before emails should be send, 2 days after setting the password
        \DateTesting::$dateTime = "2017-03-25 12:13:14";
        $cronArray = $serverItem->_cronCheckPasswordExpiredSoon();
        $portal->resetUserList();
        $portal_users = $portal->getUserList();
        $portal_user = $portal_users->getFirst();
        while ($portal_user){
            $successKey = 'success_'.$portal->getItemId().'_'.$portal_user->getItemId();
            if (!in_array($portal_user->getUserId(), $c_password_expiration_user_ids_ignore)) {
                $this->assertFalse(in_array($successKey, array_keys($cronArray)));
                $this->assertArrayNotHasKey($successKey, $cronArray);
            }
            $portal_user = $portal_users->getNext();

        }

        // "run cron" to test sending the password change preparation emails.
        $dates = array("2017-03-27 12:13:14", "2017-03-28 12:13:14", "2017-03-29 12:13:14");
        foreach ($dates as $tempDate) {
            \DateTesting::$dateTime = $tempDate;
            $cronArray = $serverItem->_cronCheckPasswordExpiredSoon();
            $portal->resetUserList();
            $portal_users = $portal->getUserList();
            $portal_user = $portal_users->getFirst();
            while ($portal_user) {
                $successKey = 'success_' . $portal->getItemId() . '_' . $portal_user->getItemId();
                if (!in_array($portal_user->getUserId(), $c_password_expiration_user_ids_ignore)) {
                    $this->assertTrue(in_array($successKey, array_keys($cronArray)));
                    $this->assertTrue($cronArray[$successKey]);
                } else {
                    $this->assertFalse(in_array($successKey, array_keys($cronArray)));
                    $this->assertNull($cronArray[$successKey]);
                }
                $portal_user = $portal_users->getNext();
            }
        }
		
		// "run cron" to change the passwords.
        \DateTesting::$dateTime = "2017-03-30 12:13:14";
        $expireDate = "2017-04-06 00:00:00";
		$cronArray = $serverItem->_cronCheckPasswordExpired();
		
		$portal->resetUserList();
		$portal_users = $portal->getUserList();
		$portal_user = $portal_users->getFirst();
		while ($portal_user){
    		$successKey = 'success_'.$portal->getItemId().'_'.$portal_user->getItemId();
			$this->assertTrue(in_array($successKey, array_keys($cronArray)));
			$this->assertTrue($cronArray[$successKey]);
            $this->assertEquals($expireDate, $portal_user->getPasswordExpireDate());
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
        $portal->setPasswordExpiration(7);
        $portal->setDaysBeforeExpiringPasswordSendMail(3);
        $portal->setPasswordGeneration(3);
        $portal->save();
    }
}