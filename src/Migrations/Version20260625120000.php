<?php

declare(strict_types=1);

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

namespace App\Migrations;

use App\Mail\Text\MailTextCatalog;
use App\Mail\Text\MailTextOverrideConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Canonicalises stored mail-text overrides from the legacy positional %1..%6 format to the
 * named-token format the runtime now expects exclusively (the %N fallback was removed).
 *
 * Portal overrides live in `portal.extras['MAIL_TEXT_ARRAY']`, room overrides in
 * `room.extras['MAIL_TEXT_ARRAY']`; both columns are PHP-serialized arrays. Only rows that
 * actually carry a MAIL_TEXT_ARRAY are touched, and only that sub-key is rewritten -- every
 * other extra is preserved. The conversion (MailTextOverrideConverter) is idempotent, so the
 * migration is a no-op on data that is already named (and on the common case of no overrides).
 */
final class Version20260625120000 extends AbstractMigration
{
    /** @var array<string, string> table => primary key column */
    private const TABLES = ['portal' => 'id', 'room' => 'item_id'];

    public function getDescription(): string
    {
        return 'Convert stored mail-text overrides from legacy %1..%6 to named tokens';
    }

    public function up(Schema $schema): void
    {
        $this->convert(toNamed: true);
    }

    public function down(Schema $schema): void
    {
        $this->convert(toNamed: false);
    }

    private function convert(bool $toNamed): void
    {
        $catalog = new MailTextCatalog();

        foreach (self::TABLES as $table => $primaryKey) {
            $rows = $this->connection->fetchAllAssociative(
                sprintf('SELECT %s AS pk, extras FROM %s WHERE extras LIKE %s', $primaryKey, $table, "'%MAIL_TEXT_ARRAY%'")
            );

            foreach ($rows as $row) {
                $raw = $row['extras'];
                if (!is_string($raw) || '' === $raw) {
                    continue;
                }

                $extras = @unserialize($raw);
                if (!is_array($extras) || !isset($extras['MAIL_TEXT_ARRAY']) || !is_array($extras['MAIL_TEXT_ARRAY'])) {
                    continue;
                }

                $extras['MAIL_TEXT_ARRAY'] = $toNamed
                    ? MailTextOverrideConverter::toNamed($catalog, $extras['MAIL_TEXT_ARRAY'])
                    : MailTextOverrideConverter::toLegacy($catalog, $extras['MAIL_TEXT_ARRAY']);

                $this->connection->executeStatement(
                    sprintf('UPDATE %s SET extras = :extras WHERE %s = :pk', $table, $primaryKey),
                    ['extras' => serialize($extras), 'pk' => $row['pk']]
                );
            }
        }
    }
}
