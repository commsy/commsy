<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Integration\Export;

use App\Entity\Files;
use App\Export\RoomFileCollector;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\FilesFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Attachments keep their uploaded name and are kept apart by their id, so two
 * entries carrying a "Protokoll.pdf" each hand the importer both.
 */
final class RoomFileCollectorTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private const ITEM = 70_001;

    private string $relativeDirectory;
    private Connection $connection;

    public function testAFileIsNamedAfterItsUploadAndFiledUnderItsId(): void
    {
        $file = $this->file('Protokoll.pdf', 'a document');
        $this->link(self::ITEM, 0, $file);

        $collected = $this->collect();

        $this->assertCount(1, $collected);
        $this->assertSame($file->getFilesId(), $collected[0]->id);
        $this->assertSame('Protokoll.pdf', $collected[0]->name);
        $this->assertSame('files/'.$file->getFilesId().'/Protokoll.pdf', $collected[0]->archivePath);
        $this->assertTrue($collected[0]->available);
        $this->assertFileExists($collected[0]->sourcePath);
    }

    public function testTwoAttachmentsOfTheSameNameBothSurvive(): void
    {
        $first = $this->file('Protokoll.pdf', 'first');
        $second = $this->file('Protokoll.pdf', 'second');

        $this->link(self::ITEM, 0, $first);
        $this->link(70_002, 0, $second);

        $collected = $this->collect([self::ITEM => 0, 70_002 => 0]);

        $this->assertCount(2, $collected);
        $this->assertNotSame($collected[0]->archivePath, $collected[1]->archivePath);
    }

    /**
     * Rows outlive their content. The attachment is still named, so the document
     * can say it existed, but the archive is not asked to carry nothing.
     */
    public function testAFileWhoseContentIsGoneIsNamedButNotAvailable(): void
    {
        $file = $this->file('Verschwunden.txt', contents: null);
        $this->link(self::ITEM, 0, $file);

        $collected = $this->collect();

        $this->assertCount(1, $collected);
        $this->assertFalse($collected[0]->available);
    }

    /**
     * A stored name decides what the file is called, never where it goes.
     */
    public function testAFileNameCannotReachOutOfItsDirectory(): void
    {
        $file = $this->file('../../etc/passwd', 'not really');
        $this->link(self::ITEM, 0, $file);

        $collected = $this->collect();

        $this->assertSame('files/'.$file->getFilesId().'/passwd', $collected[0]->archivePath);
    }

    public function testFilesComeBackInTheOrderOfTheirIds(): void
    {
        $first = $this->file('a.txt', 'a');
        $second = $this->file('b.txt', 'b');

        $this->link(self::ITEM, 0, $second);
        $this->link(self::ITEM, 0, $first);

        $collected = $this->collect();

        $this->assertSame(
            [$first->getFilesId(), $second->getFilesId()],
            array_map(static fn ($file): int => $file->id, $collected)
        );
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->relativeDirectory = 'var/tmp/commsy-export-files-'.getmypid();
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->absolute($this->relativeDirectory));

        parent::tearDown();
    }

    /**
     * @param array<int, int>|null $versionByItemId
     *
     * @return list<\App\Export\ExportFile>
     */
    private function collect(?array $versionByItemId = null): array
    {
        return self::getContainer()->get(RoomFileCollector::class)
            ->collect($versionByItemId ?? [self::ITEM => 0]);
    }

    /**
     * Writes the row the way an upload leaves it: the stored path is relative to
     * the project, the stored name is what someone uploaded.
     */
    private function file(string $name, ?string $contents): Files
    {
        $relativePath = $this->relativeDirectory.'/'.uniqid('file', true);

        if (null !== $contents) {
            (new Filesystem())->dumpFile($this->absolute($relativePath), $contents);
        }

        return FilesFactory::createOne([
            'filename' => $name,
            'filepath' => $relativePath,
            'size' => null === $contents ? 0 : strlen($contents),
        ]);
    }

    private function link(int $itemId, int $versionId, Files $file): void
    {
        $this->connection->insert('item_link_file', [
            'item_iid' => $itemId,
            'item_vid' => $versionId,
            'file_id' => $file->getFilesId(),
        ]);
    }

    private function absolute(string $relativePath): string
    {
        return self::getContainer()->getParameter('kernel.project_dir').'/'.$relativePath;
    }
}
