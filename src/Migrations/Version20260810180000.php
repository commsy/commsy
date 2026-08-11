<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Aligns user room owners with their membership in the project room above.
 *
 * A user room is created as soon as somebody applies for membership, and until now its
 * owner was always made a regular user there. Applicants could therefore work in their
 * own user room while the application was still pending. The code no longer does this;
 * this migration corrects the rooms that were created under the old behaviour.
 *
 * Only the case the defect produced is touched: the project room says "not a member
 * yet" while the user room says "regular user". Divergences in the other direction are
 * left alone — they cannot come from this defect, and lowering a status on a guess
 * would take access away from someone who has it legitimately.
 */
final class Version20260810180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lower user room owners whose membership in the project room is still pending';
    }

    public function up(Schema $schema): void
    {
        // extras is a serialised PHP array (Doctrine's deprecated "array" type), so the
        // links between a user room, its owner and the project room user cannot be
        // reached from SQL; they are read here instead.
        $userRooms = $this->connection->fetchAllAssociative(
            "SELECT item_id, extras FROM room WHERE type = 'userroom' AND deletion_date IS NULL"
        );

        $corrected = 0;
        foreach ($userRooms as $userRoom) {
            $projectUserId = $this->extra($userRoom['extras'], 'USER_ITEM_ID');
            if (null === $projectUserId) {
                continue;
            }

            $projectUserStatus = $this->connection->fetchOne(
                'SELECT status FROM user WHERE item_id = ? AND deletion_date IS NULL',
                [$projectUserId]
            );
            // nothing to correct unless the membership is still pending or was refused
            if (false === $projectUserStatus || (int) $projectUserStatus >= 2) {
                continue;
            }

            $members = $this->connection->fetchAllAssociative(
                'SELECT item_id, status, extras FROM user WHERE context_id = ? AND deletion_date IS NULL',
                [$userRoom['item_id']]
            );

            foreach ($members as $member) {
                // the owner is the one representing the project room user this room belongs
                // to; the other members are the project room's moderators
                if ($this->extra($member['extras'], 'PROJECT_USER_ITEM_ID') !== $projectUserId) {
                    continue;
                }
                if ((int) $member['status'] < 2) {
                    continue;
                }

                $this->connection->executeStatement(
                    'UPDATE user SET status = ? WHERE item_id = ?',
                    [(int) $projectUserStatus, $member['item_id']]
                );
                ++$corrected;
            }
        }

        $this->write(sprintf('Corrected %d user room owner(s).', $corrected));
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'The previous statuses are not recorded, and restoring them would hand back '
            .'access to applications that were never granted.'
        );
    }

    /**
     * Reads one key out of a serialised legacy extras blob.
     *
     * Via DbConverter rather than a plain unserialize(): legacy blobs carry string
     * lengths that no longer match their bytes, a leftover of past charset changes.
     * unserialize() returns false on those, which would quietly skip exactly the old
     * rows this migration exists for.
     */
    private function extra($extras, string $key): ?int
    {
        $decoded = DbConverter::convertToPHPValue($extras);

        return isset($decoded[$key]) ? (int) $decoded[$key] : null;
    }
}
