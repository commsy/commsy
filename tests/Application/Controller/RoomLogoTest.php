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

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Room;
use App\Files\FilesDirectory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The logo the navigation bar asks for: a room's own, otherwise the
 * placeholder, also where the context is no room at all.
 */
final class RoomLogoTest extends AbstractApplicationTestCase
{
    /** @var list<string> directories written by the test */
    private array $directories = [];

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directories);
        parent::tearDown();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testThePortalOnTheDashboardGetsThePlaceholder(): void
    {
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        self::assertSame($this->placeholder(), $this->fetch("/room/{$account->getContextId()}/logo"));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testARoomShowsItsOwnLogoOrElseThePlaceholder(): void
    {
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());
        self::assertSame($this->placeholder(), $this->fetch("/room/{$room->getItemId()}/logo"), 'without a logo');

        $name = "cid{$room->getItemId()}_logo_Wappen.png";
        $directory = static::getContainer()->get(FilesDirectory::class)->contextDirectory((int) $account->getContextId(), (int) $room->getItemId());
        (new Filesystem())->dumpFile("$directory/$name", 'the logo');
        $this->directories[] = $directory;
        static::getContainer()->get(EntityManagerInterface::class)->getConnection()
            ->update('room', ['extras' => serialize(['LOGOFILENAME' => $name])], ['item_id' => $room->getItemId()]);

        self::assertSame('the logo', $this->fetch("/room/{$room->getItemId()}/logo"));
    }

    private function fetch(string $path): string
    {
        $this->client->request('GET', $path);
        $this->assertResponseIsSuccessful();

        return (string) $this->client->getInternalResponse()->getContent();
    }

    private function placeholder(): string
    {
        return (string) file_get_contents(static::getContainer()->getParameter('themes_directory').'/customBgPlaceholder.png');
    }
}
