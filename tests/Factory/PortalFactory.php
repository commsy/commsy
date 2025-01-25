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
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Portal>
 */
final class PortalFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct()
    {
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
            'status' => 1,
            'descriptionEnglish' => self::faker()->text(),
            'descriptionGerman' => self::faker()->text(),
        ];
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
