<?php

namespace Tests\Unit;

use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Entity\Translation;
use App\Facade\PortalCreatorFacade;
use Codeception\Test\Unit;
use Tests\Support\UnitTester;

class PortalTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function createPortal()
    {
        /** @var PortalCreatorFacade $portalCreator */
        $portalCreator = $this->tester->grabService(PortalCreatorFacade::class);

        $portal = new Portal();
        $portal->setTitle('Testportal');
        $portal->setStatus(1);

        $portalCreator->persistPortal($portal);

        $this->tester->assertNotEmpty($portal->getAuthSources());

        $this->tester->seeInRepository(Portal::class, ['title' => 'Testportal']);
        $this->tester->seeInRepository(AuthSource::class, ['portal' => $portal]);
        $this->tester->seeInRepository(Translation::class, ['contextId' => $portal->getId(), 'translationKey' => 'EMAIL_REGEX_ERROR']);
        $this->tester->seeInRepository(Translation::class, ['contextId' => $portal->getId(), 'translationKey' => 'REGISTRATION_USERNAME_HELP']);
    }
}
