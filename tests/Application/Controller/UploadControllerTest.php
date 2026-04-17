<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(RoomWithMemberStory::class)]
class UploadControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private Room $room;
    private User $roomUser;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->roomId = $this->room->getItemId();

        $this->loginAsUser(
            $this->account->getPortal()->getId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
    }

    public function testUploadAttachesFilesToMaterial(): void
    {
        $material = $this->createMaterial();

        $uploadedFile = $this->createTempUpload('payload.txt', 'hello world');

        $this->client->request(
            'POST',
            "/room/{$this->roomId}/upload/{$material->getItemId()}",
            [],
            ['files' => [$uploadedFile]]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('fileIds', $payload);
        $this->assertNotEmpty($payload['fileIds'], 'fileIds should not be empty after successful upload');
    }

    public function testTempUploadStoresFileForCurrentUser(): void
    {
        $uploadedFile = $this->createTempUpload('avatar.png', 'not-a-real-png');

        $this->client->request(
            'POST',
            "/room/{$this->roomId}/tempupload/avatar.png",
            [],
            ['files' => [$uploadedFile]]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('fileIds', $payload);
    }

    public function testAttachAddsFileIdsToExistingItem(): void
    {
        $material = $this->createMaterial();

        $uploadedFile = $this->createTempUpload('attachment.txt', 'attached');

        $this->client->request(
            'POST',
            "/room/{$this->roomId}/attach/{$material->getItemId()}",
            [],
            ['files' => [$uploadedFile]]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('fileIds', $payload);
    }

    public function testAttachReturns404ForUnknownItem(): void
    {
        $uploadedFile = $this->createTempUpload('attach.txt', 'x');

        $this->client->request(
            'POST',
            "/room/{$this->roomId}/attach/99999999",
            [],
            ['files' => [$uploadedFile]]
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCkUploadReturnsJson(): void
    {
        $material = $this->createMaterial();

        $uploadedFile = $this->createTempUpload('cke.txt', 'ck-payload');

        $this->client->request(
            'POST',
            "/room/{$this->roomId}/ckupload/{$material->getItemId()}/",
            [],
            ['upload' => $uploadedFile]
        );

        $this->assertResponseIsSuccessful();
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('uploaded', $payload);
    }

    public function testMailAttachmentsCollectsFiles(): void
    {
        // the controller writes into <project>/files/temp/; make sure the
        // directory exists for the test run (it's normally provisioned by
        // the production installer)
        $targetDir = self::getContainer()->getParameter('kernel.project_dir').'/files/temp';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        $uploadedFile = $this->createTempUpload('mail.txt', 'mail body');

        $this->client->request(
            'POST',
            "/room/{$this->roomId}/upload/mailattachments/",
            [],
            ['files' => [$uploadedFile]]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('attachmentInfo', $payload);
    }

    private function createMaterial(): Materials
    {
        return MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ]);
    }

    /**
     * Build a Symfony UploadedFile backed by a real temp file (required because
     * UploadedFile::getRealPath() is called deep in the upload path).
     */
    private function createTempUpload(string $clientName, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'commsy_test_');
        file_put_contents($path, $content);

        return new UploadedFile(
            path: $path,
            originalName: $clientName,
            mimeType: 'text/plain',
            error: null,
            test: true,
        );
    }
}
