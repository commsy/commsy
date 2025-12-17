<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Database\FixFilepaths;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217132111 extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        private readonly FixFilepaths $fixFilepaths
    ) {
        parent::__construct($connection, $logger);
    }

    public function getDescription(): string
    {
        return 'Run database fix: FixFilepaths';
    }

    public function up(Schema $schema): void
    {
        $output = new BufferedOutput();
        $io = new SymfonyStyle(new ArrayInput([]), $output);

        $this->write('Running FixFilepaths ...');

        $ok = $this->fixFilepaths->resolve($io);

        $buffer = trim($output->fetch());
        if ($buffer !== '') {
            $this->write($buffer);
        }

        if (!$ok) {
            throw new RuntimeException('FixFilepaths reported failure.');
        }

        $this->write('FixFilepaths done.');
    }
}
