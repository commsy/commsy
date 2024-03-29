<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Facade;

use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use App\Entity\Translation;
use Doctrine\Persistence\ManagerRegistry;

class PortalCreatorFacade
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
    }

    public function persistPortal(Portal $portal)
    {
        $authSource = new AuthSourceLocal();
        $authSource->setPortal($portal);
        $authSource->setTitle('Lokal');
        $authSource->setEnabled(true);
        $authSource->setDefault(true);
        $authSource->setAddAccount(AuthSource::ADD_ACCOUNT_YES);
        $authSource->setChangeUsername(true);
        $authSource->setDeleteAccount(true);
        $authSource->setChangeUserdata(true);
        $authSource->setChangePassword(true);
        $authSource->setCreateRoom(true);

        $portal->addAuthSource($authSource);

        $manager = $this->registry->getManager();

        $manager->persist($portal);
        $manager->persist($authSource);
        $manager->flush();

        // TODO: Make this a relation and flush all together
        $translation = new Translation();
        $translation->setContextId($portal->getId());
        $translation->setTranslationKey('EMAIL_REGEX_ERROR');
        $translation->setTranslationDe('Die angegebene E-Mail-Adresse entspricht nicht den Vorgaben der Portalmoderation.');
        $translation->setTranslationEn('The given email-address does not match the requirements set by the portal moderators.');
        $manager->persist($translation);

        $translation = new Translation();
        $translation->setContextId($portal->getId());
        $translation->setTranslationKey('REGISTRATION_USERNAME_HELP');
        $translation->setTranslationDe('Ein frei wählbarer, eindeutiger Benutzername.');
        $translation->setTranslationEn('An arbitrary, unique username.');
        $manager->persist($translation);

        $manager->flush();
    }
}
