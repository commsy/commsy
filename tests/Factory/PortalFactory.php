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

namespace Tests\Factory;

use App\Entity\Portal;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Portal>
 */
final class PortalFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return Portal::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        return [
            'authSources' => [AuthSourceLocalFactory::new()],
            'title' => self::faker()->word(),
            'status' => '1',
            'descriptionEnglish' => self::faker()->text(),
            'descriptionGerman' => self::faker()->text(),
        ];
    }

    /**
     * status='3' — locked. NOTE: due to a long-standing type-mismatch bug
     * in PortalProxy::isLocked() (`3 === $portal->getStatus()` against a
     * string column), Voter::canEnter does NOT actually block locked
     * portals. The flag is still written to the DB; only the consumer
     * misreads it. Pinned by ItemVoterEnterTest.
     */
    public function locked(): static
    {
        return $this->with(['status' => '3']);
    }

    public function closed(): static
    {
        return $this->with(['status' => '2']);
    }

    /**
     * Adds an enabled AuthSourceGuest alongside the default Local source
     * so PortalProxy::isOpenForGuests() returns true.
     */
    public function withGuestAuth(): static
    {
        return $this->with(['authSources' => [
            AuthSourceLocalFactory::new(),
            AuthSourceGuestFactory::new(),
        ]]);
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            ->afterPersist(function(Portal $portal): void {
                TranslationFactory::createOne([
                    'contextId' => $portal->getId(),
                    'translationKey' => 'EMAIL_REGEX_ERROR',
                    'translationDe' => 'email_regex_de',
                    'translationEn' => 'email_regex_en',
                ]);

                TranslationFactory::createOne([
                    'contextId' => $portal->getId(),
                    'translationKey' => 'REGISTRATION_USERNAME_HELP',
                    'translationDe' => 'Ein frei wählbarer, eindeutiger Benutzername.',
                    'translationEn' => 'An arbitrary, unique username.',
                ]);

                TranslationFactory::createOne([
                    'contextId' => $portal->getId(),
                    'translationKey' => 'ROOM_SETTINGS_SLUG_HELP',
                    'translationDe' => 'help_de',
                    'translationEn' => 'help_en',
                ]);
            })
        ;
    }
}
