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
use App\Entity\User;
use App\Files\FilesDirectory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * What is served of the pictures of people, and how.
 */
final class UserImageTest extends AbstractApplicationTestCase
{
    /** @var list<string> directories written by the test */
    private array $directories = [];

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directories);
        parent::tearDown();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testTheImageLibraryServesNothingFromTheFilesDirectory(): void
    {
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var User $user */
        $user = RoomWithMemberStory::get('roomUser');
        $relativePath = static::getContainer()->get(FilesDirectory::class)->relativeOf(
            $this->givePicture($user, (int) $account->getContextId())
        );

        $this->client->request('GET', "/media/cache/resolve/commsy_user_image/$relativePath");

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Sets a picture as the room profile does and writes a real image.
     */
    private function givePicture(User $user, int $portalId): string
    {
        $contextId = (int) $user->getContextId();
        $name = "cid{$contextId}_{$user->getUserId()}.png";
        static::getContainer()->get(EntityManagerInterface::class)->getConnection()
            ->update('user', ['extras' => serialize(['USERPICTURE' => $name])], ['item_id' => $user->getItemId()]);

        $directory = static::getContainer()->get(FilesDirectory::class)->contextDirectory($portalId, $contextId);
        (new Filesystem())->mkdir($directory);
        $image = imagecreatetruecolor(240, 240);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 200, 30, 30));
        imagepng($image, "$directory/$name");
        $this->directories[] = $directory;

        return "$directory/$name";
    }
}
