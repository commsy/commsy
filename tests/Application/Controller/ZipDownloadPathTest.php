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
use App\Entity\User;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use ZipArchive;

/**
 * The ZIP download names the folder and the PDF of an entry after its title,
 * reduced to a single path segment: a title that climbs up writes nothing
 * outside the export folder.
 */
#[WithStory(RoomWithMemberStory::class)]
final class ZipDownloadPathTest extends AbstractApplicationTestCase
{
    private const string TITLE = '../../../escape5526';

    private string $escaped;

    public function setUp(): void
    {
        parent::setUp();

        // where the title would lead from inside the export folder
        $this->escaped = self::getContainer()->getParameter('files_directory').'/escape5526';
        (new Filesystem())->remove([$this->escaped, $this->escaped.'.pdf']);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove([$this->escaped, $this->escaped.'.pdf']);

        parent::tearDown();
    }

    public function testATitleThatClimbsUpStaysInTheArchive(): void
    {
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');
        $material = MaterialFactory::createOne(['room' => $room, 'creator' => $roomUser, 'title' => self::TITLE]);

        $this->loginAsUser((int) $account->getPortal()?->getId(), $account->getUsername(), $account->getPlainPassword());
        $this->client->request('POST', "/room/{$room->getItemId()}/material/download", [
            'action' => 'download',
            'positiveItemIds' => [$material->getItemId()],
        ]);

        $this->assertResponseIsSuccessful();
        self::assertFileDoesNotExist($this->escaped);
        self::assertFileDoesNotExist($this->escaped.'.pdf');

        $entries = $this->entriesOf((string) $this->client->getInternalResponse()->getContent());
        self::assertContains('_.._.._escape5526/_.._.._escape5526.pdf', $entries);
    }

    /**
     * @return list<string>
     */
    private function entriesOf(string $zipContent): array
    {
        $path = tempnam(sys_get_temp_dir(), 'zip5526');
        file_put_contents($path, $zipContent);
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path));

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $entries[] = (string) $zip->getNameIndex($i);
        }
        $zip->close();
        unlink($path);

        return $entries;
    }
}
