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

use App\Account\AccountLanguage;
use App\Entity\Account;
use App\Facade\AccountCreatorFacade;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Account>
 */
final class AccountFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AccountCreatorFacade $accountCreatorFacade,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Account::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        return [
            'activityState' => self::faker()->randomElement([
                Account::ACTIVITY_ACTIVE,
                Account::ACTIVITY_ACTIVE_NOTIFIED,
                Account::ACTIVITY_IDLE,
                Account::ACTIVITY_IDLE_NOTIFIED,
                Account::ACTIVITY_ABANDONED,
            ]),
            'email' => self::faker()->email(),
            'firstname' => self::faker()->firstName(),
            'language' => self::faker()->randomElement([
                AccountLanguage::GERMAN,
                AccountLanguage::ENGLISH,
                AccountLanguage::BROWSER,
            ]),
            'lastname' => self::faker()->lastName(),
            'locked' => self::faker()->boolean(),
            'username' => self::faker()->userName(),
            'plainPassword' => self::faker()->password(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function(Account $account): void {
                $account->setPassword($this->passwordHasher->hashPassword($account, $account->getPlainPassword()));

                if (!$account->getPortal()) {
                    $portal = PortalFactory::randomOrCreate();
                    $account->setPortal($portal);
                    $account->setAuthSource($portal->getAuthSources()->first());
                }
            })
            ->afterPersist(function (Account $account): void {
                $this->accountCreatorFacade->persistNewAccount($account);
            })
        ;
    }
}
