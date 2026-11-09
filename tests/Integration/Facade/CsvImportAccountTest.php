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


declare(strict_types=1);

namespace Tests\Integration\Facade;

use App\Entity\Account;
use App\Facade\UserCreatorFacade;
use App\Form\Model\Csv\CsvUserDataset;
use App\Utils\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The CSV import of the portal settings creates an account with its portal
 * profile for each row, with the password the row gives.
 */
#[WithStory(AccountStory::class)]
final class CsvImportAccountTest extends KernelTestCase
{
    public function testARowBecomesAnAccountWithItsPassword(): void
    {
        self::bootKernel();
        $authSource = AccountStory::get('account')->getAuthSource();
        // built as the import builds it, every column given
        $row = (new CsvUserDataset())
            ->setIdentifier('importiert')
            ->setFirstname('Ina')
            ->setLastname('Import')
            ->setEmail('ina.import@example.org')
            ->setPassword('Geheim-123')
            ->setRooms('');

        self::getContainer()->get(UserCreatorFacade::class)->createFromCsvDataset($authSource, $row);

        $account = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Account::class)
            ->findOneBy(['username' => 'importiert', 'authSource' => $authSource]);
        self::assertInstanceOf(Account::class, $account);
        self::assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($account, 'Geheim-123'));
        self::assertSame('Import', self::getContainer()->get(UserService::class)->getPortalUser($account)->getLastname());
    }
}
