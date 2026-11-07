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
use App\Files\FilesDirectory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Uploaded files reach the browser as files: a page or a script uploaded by
 * a member never runs as a page of the site, neither as an attachment, nor
 * as a room logo or background, nor through the editor's upload dialog.
 */
#[WithStory(RoomWithMemberStory::class)]
final class ServedFilesStayFilesTest extends AbstractApplicationTestCase
{
    private const string PAGE = '<!DOCTYPE html><html><body><script>document.title = "ran"</script></body></html>';
    private const string SVG = '<svg xmlns="http://www.w3.org/2000/svg"><script>document.title = "ran"</script></svg>';
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    private const string PDF = "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n";

    private Account $account;
    private Room $room;
    private User $roomUser;

    /** @var list<string> directories written by the test */
    private array $directories = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser((int) $this->account->getPortal()?->getId(), $this->account->getUsername(), $this->account->getPlainPassword());
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directories);
        parent::tearDown();
    }

    public function testAnUploadedPageIsDownloadedEvenWhenAskedToShowIt(): void
    {
        foreach (['page.html' => self::PAGE, 'drawing.svg' => self::SVG] as $name => $content) {
            $this->client->request('GET', '/file/'.$this->upload($name, $content).'/inline');

            self::assertResponseIsSuccessful();
            self::assertStringStartsWith('attachment', (string) $this->client->getResponse()->headers->get('Content-Disposition'), $name);
            self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
            self::assertResponseHeaderSame('Content-Security-Policy', 'sandbox');
        }
    }

    public function testImagesAndPdfStillShowInPlace(): void
    {
        $this->client->request('GET', '/file/'.$this->upload('dot.png', (string) base64_decode(self::PNG)).'/inline');
        self::assertStringStartsWith('inline', (string) $this->client->getResponse()->headers->get('Content-Disposition'));
        self::assertResponseHeaderSame('Content-Security-Policy', 'sandbox');

        // PDF viewers refuse a sandboxed document
        $this->client->request('GET', '/file/'.$this->upload('paper.pdf', self::PDF).'/inline');
        self::assertStringStartsWith('inline', (string) $this->client->getResponse()->headers->get('Content-Disposition'));
        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        self::assertFalse($this->client->getResponse()->headers->has('Content-Security-Policy'));
    }

    public function testARoomLogoThatIsNoImageGivesWayToThePlaceholder(): void
    {
        $this->roomFile('LOGOFILENAME', "cid{$this->room->getItemId()}_logo_page.html", self::PAGE);

        $this->client->request('GET', "/room/{$this->room->getItemId()}/logo");

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/png');
        self::assertSame($this->placeholder(), (string) $this->client->getInternalResponse()->getContent());
    }

    public function testACustomBackgroundThatIsNoImageGivesWayToThePlaceholder(): void
    {
        $this->roomFile('BGIMAGEFILENAME', "cid{$this->room->getItemId()}_bgimage_page.html", self::PAGE);

        $this->client->request('GET', "/room/{$this->room->getItemId()}/custom/background/");

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/png');
        self::assertSame($this->placeholder(), (string) $this->client->getInternalResponse()->getContent());
    }

    public function testARoomLogoThatIsAnImageIsShown(): void
    {
        $png = (string) base64_decode(self::PNG);
        $this->roomFile('LOGOFILENAME', "cid{$this->room->getItemId()}_logo_dot.png", $png);

        $this->client->request('GET', "/room/{$this->room->getItemId()}/logo");

        self::assertSame($png, (string) $this->client->getInternalResponse()->getContent());
        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
    }

    public function testTheEditorUploadDialogIsCalledBackByNumberOnly(): void
    {
        $material = MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser])->getItemId();
        $route = "/room/{$this->room->getItemId()}/ckupload/$material/?CKEditor=description&CKEditorFuncNum=";

        $this->client->request('POST', $route.rawurlencode('1);document.title="ran";//'), [], ['upload' => $this->file('a.png', (string) base64_decode(self::PNG))]);
        self::assertResponseStatusCodeSame(400);

        $this->client->request('POST', $route.'7', [], ['upload' => $this->file('b.png', (string) base64_decode(self::PNG))]);
        self::assertResponseIsSuccessful();
        self::assertMatchesRegularExpression('#callFunction\(7, "/file/\d+(/attachment)?"\);#', (string) $this->client->getInternalResponse()->getContent());
    }

    /**
     * Attaches a file to a new material; its id.
     */
    private function upload(string $name, string $content): int
    {
        $material = MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser])->getItemId();

        $this->client->request('POST', "/room/{$this->room->getItemId()}/attach/$material", [], ['files' => [$this->file($name, $content)]]);
        self::assertResponseIsSuccessful();

        $fileIds = json_decode((string) $this->client->getInternalResponse()->getContent(), true)['fileIds'] ?? [];
        self::assertCount(1, $fileIds);

        // keyed by file id, each with its label
        return (int) array_key_first($fileIds);
    }

    private function file(string $name, string $content): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'commsy_test_');
        file_put_contents($path, $content);

        return new UploadedFile(path: $path, originalName: $name, mimeType: null, error: null, test: true);
    }

    /**
     * Places a file in the room's directory and names it in the room's extras.
     */
    private function roomFile(string $extra, string $name, string $content): void
    {
        $directory = static::getContainer()->get(FilesDirectory::class)->contextDirectory((int) $this->account->getPortal()?->getId(), (int) $this->room->getItemId());
        (new Filesystem())->dumpFile("$directory/$name", $content);
        $this->directories[] = $directory;

        static::getContainer()->get(EntityManagerInterface::class)->getConnection()
            ->update('room', ['extras' => serialize([$extra => $name])], ['item_id' => $this->room->getItemId()]);
    }

    private function placeholder(): string
    {
        return (string) file_get_contents(static::getContainer()->getParameter('themes_directory').'/customBgPlaceholder.png');
    }
}
