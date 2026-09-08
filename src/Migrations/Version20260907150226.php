<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Replaces the content of entries that were only marked as redacted.
 *
 * Two markers were smuggled into the `public` column, which otherwise says who may
 * edit an entry (0 = only its author, 1 = every member):
 *
 *  - `-1` was written by the old privacy redaction. With DATA_PRIVACY_OVERWRITING=FLAG
 *    it set nothing but the marker: the original title and description stayed in the
 *    columns and only the display substituted a placeholder. Those rows still hold the
 *    content the person asked to have removed.
 *  - `-2` marks a discussion contribution its author deleted that had answers, so the
 *    row had to stay to hold the thread together. Its description was emptied and the
 *    placeholder came from the marker at display time.
 *
 * Both are replaced here by the thing they only pretended to be: the placeholder text
 * written into the columns, and `public` reset to 0. The reading code that produced the
 * substitution goes away in the same release, so anything left marked would show its
 * original content again.
 *
 * The texts are hardcoded rather than translated through the application: a migration
 * has to produce the same result whenever it runs, independent of later edits to the
 * translation files. The room's language decides which variant a row gets.
 *
 * Fields that the old display substituted with an empty string — a date's place, a
 * material's author, publishing date and bibliographic values — are emptied here for
 * the same reason.
 *
 * Irreversible by nature: the original content is what is being removed.
 */
final class Version20260907150226 extends AbstractMigration
{
    private const TITLE = [
        'de' => 'Gelöschter Eintrag',
        'en' => 'Deleted entry',
    ];

    private const DESCRIPTION = [
        'de' => 'Dieser Eintrag wurde gelöscht und durch einen Platzhalter ersetzt, um den Zusammenhang zu anderen Einträgen zu wahren.',
        'en' => 'This entry was deleted and replaced with a placeholder in order to keep its context to other entries intact.',
    ];

    private const FALLBACK_LANGUAGE = 'de';

    /**
     * table => [title column or null, description column or null, columns emptied outright]
     */
    private const TABLES = [
        'annotations' => [null, 'description', []],
        'announcement' => ['title', 'description', []],
        'dates' => ['title', 'description', ['place']],
        'discussionarticles' => [null, 'description', []],
        'discussions' => ['title', 'description', []],
        'labels' => ['name', 'description', []],
        'materials' => ['title', 'description', ['author', 'publishing_date']],
        'section' => ['title', 'description', []],
        'step' => ['title', 'description', []],
        'tag' => ['title', null, []],
        'todos' => ['title', 'description', []],
    ];

    /** @var array<int, string> */
    private array $languageByContext = [];

    public function getDescription(): string
    {
        return 'Write the placeholder text into entries that were only marked as redacted (public -1 / -2)';
    }

    public function up(Schema $schema): void
    {
        $total = 0;

        foreach (self::TABLES as $table => [$titleColumn, $descriptionColumn, $clearedColumns]) {
            $total += $this->replaceMarkedRows($table, $titleColumn, $descriptionColumn, $clearedColumns);
        }

        // `materials.extras` carries BIBLIOGRAPHIC, which the old display also blanked
        // out. It is a serialised PHP array, so it cannot be reached from SQL.
        $total += $this->clearMaterialBibliographicValues();

        $this->write(sprintf('  replaced %d marked row(s)', $total));
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'The original content was removed on purpose and cannot be restored.'
        );
    }

    /**
     * @param string[] $clearedColumns
     */
    private function replaceMarkedRows(
        string $table,
        ?string $titleColumn,
        ?string $descriptionColumn,
        array $clearedColumns,
    ): int {
        $rows = $this->connection->fetchAllAssociative(
            sprintf('SELECT item_id, context_id FROM %s WHERE public IN (-1, -2)', $table)
        );
        if ($rows === []) {
            return 0;
        }

        // Group by language so one statement covers every row that shares it.
        $itemIdsByLanguage = [];
        foreach ($rows as $row) {
            $language = $this->languageOf((int) $row['context_id']);
            $itemIdsByLanguage[$language][] = (int) $row['item_id'];
        }

        $assignments = ['public = 0'];
        if ($titleColumn !== null) {
            $assignments[] = sprintf('%s = :title', $titleColumn);
        }
        if ($descriptionColumn !== null) {
            $assignments[] = sprintf('%s = :description', $descriptionColumn);
        }
        foreach ($clearedColumns as $clearedColumn) {
            $assignments[] = sprintf("%s = ''", $clearedColumn);
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE item_id IN (:itemIds)',
            $table,
            implode(', ', $assignments)
        );

        $affected = 0;
        foreach ($itemIdsByLanguage as $language => $itemIds) {
            $parameters = ['itemIds' => $itemIds];
            if ($titleColumn !== null) {
                $parameters['title'] = self::TITLE[$language];
            }
            if ($descriptionColumn !== null) {
                $parameters['description'] = self::DESCRIPTION[$language];
            }

            $affected += (int) $this->connection->executeStatement(
                $sql,
                $parameters,
                ['itemIds' => ArrayParameterType::INTEGER]
            );
        }

        return $affected;
    }

    private function clearMaterialBibliographicValues(): int
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT item_id, version_id, extras FROM materials
                WHERE public IN (-1, -2) AND extras IS NOT NULL AND extras <> :empty',
            ['empty' => '']
        );

        $affected = 0;
        foreach ($rows as $row) {
            $extras = @unserialize((string) $row['extras'], ['allowed_classes' => false]);
            if (!is_array($extras) || !array_key_exists('BIBLIOGRAPHIC', $extras)) {
                continue;
            }

            unset($extras['BIBLIOGRAPHIC']);

            $this->connection->executeStatement(
                'UPDATE materials SET extras = :extras WHERE item_id = :itemId AND version_id = :versionId',
                [
                    'extras' => serialize($extras),
                    'itemId' => (int) $row['item_id'],
                    'versionId' => (int) $row['version_id'],
                ]
            );
            ++$affected;
        }

        return $affected;
    }

    /**
     * The room language lives in the serialised `room.extras` blob. Rooms set to
     * follow the reader's language ("user") have no language of their own, and a
     * portal-scoped context has no room row at all — both fall back.
     */
    private function languageOf(int $contextId): string
    {
        if (isset($this->languageByContext[$contextId])) {
            return $this->languageByContext[$contextId];
        }

        $extras = $this->connection->fetchOne(
            'SELECT extras FROM room WHERE item_id = :contextId',
            ['contextId' => $contextId]
        );

        $language = self::FALLBACK_LANGUAGE;
        if (is_string($extras) && $extras !== '') {
            $decoded = @unserialize($extras, ['allowed_classes' => false]);
            if (is_array($decoded) && isset($decoded['LANGUAGE'])) {
                $candidate = strtolower((string) $decoded['LANGUAGE']);
                if (isset(self::TITLE[$candidate])) {
                    $language = $candidate;
                }
            }
        }

        return $this->languageByContext[$contextId] = $language;
    }
}
