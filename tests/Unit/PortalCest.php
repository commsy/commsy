<?php

namespace Tests\Unit;

use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Entity\Translation;
use App\Facade\PortalCreatorFacade;
use Tests\Support\UnitTester;

class PortalCest
{
    public function createPortal(UnitTester $I)
    {
        /** @var PortalCreatorFacade $portalCreator */
        $portalCreator = $I->grabService(PortalCreatorFacade::class);

        $portal = new Portal();
        $portal->setTitle('Testportal');
        $portal->setStatus(1);

        $portalCreator->persistPortal($portal);

        $I->assertNotEmpty($portal->getAuthSources());

        $I->seeInRepository(Portal::class, ['title' => 'Testportal']);
        $I->seeInRepository(AuthSource::class, ['portal' => $portal]);
        $I->seeInRepository(Translation::class, ['contextId' => $portal->getId(), 'translationKey' => 'EMAIL_REGEX_ERROR']);
    }
}
