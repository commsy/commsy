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
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Mime\Email;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A mail attaches only files uploaded for it: the form hands the path of
 * each upload back, and a path that leads elsewhere is not attached.
 */
#[WithStory(RoomWithMemberStory::class)]
final class MailAttachmentPathTest extends AbstractApplicationTestCase
{
    use MailerAssertionsTrait;

    private Room $room;
    private User $roomUser;
    private string $outside;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');

        $this->outside = (string) tempnam(sys_get_temp_dir(), 'outside');
        file_put_contents($this->outside, 'not an upload');

        $this->loginAsUser((int) $account->getPortal()?->getId(), $account->getUsername(), $account->getPlainPassword());
    }

    protected function tearDown(): void
    {
        @unlink($this->outside);
        parent::tearDown();
    }

    public function testAPathOutsideTheUploadsIsNotAttached(): void
    {
        $this->sendWithAttachmentAt($this->outside);

        self::assertSame([], $this->attachmentsOfTheSentMail());
    }

    public function testAnUploadIsAttached(): void
    {
        $uploads = self::getContainer()->getParameter('files_directory').'/temp/';
        @mkdir($uploads, 0777, true);
        $upload = $uploads.md5(uniqid()).'.txt';
        file_put_contents($upload, 'an upload');

        try {
            $this->sendWithAttachmentAt($upload);
        } finally {
            @unlink($upload);
        }

        self::assertSame(['notes.txt'], $this->attachmentsOfTheSentMail());
    }

    private function sendWithAttachmentAt(string $path): void
    {
        $crawler = $this->client->request('POST', "/room/{$this->room->getItemId()}/user/sendMultiple", [
            'action' => 'sendmail',
            'positiveItemIds' => [(string) $this->roomUser->getItemId()],
        ]);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('user_send[save]')->form();
        $values = $form->getPhpValues();
        $values['user_send']['subject'] = 'Betreff';
        $values['user_send']['files'] = [[
            'checked' => '1',
            'fileId' => 'f1',
            'filename' => 'notes.txt',
            'filePath' => $path,
        ]];
        $values['user_send']['save'] = '';

        $this->client->request('POST', (string) $form->getUri(), $values);
        $this->assertResponseRedirects();
    }

    /**
     * @return list<string|null> the names of the attachments
     */
    private function attachmentsOfTheSentMail(): array
    {
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage(0);
        self::assertInstanceOf(Email::class, $email);

        return array_map(static fn ($part): ?string => $part->getFilename(), $email->getAttachments());
    }
}
