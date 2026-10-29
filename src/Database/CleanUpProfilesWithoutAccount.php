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

namespace App\Database;

use App\User\UserMembershipDeleter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Soft-deletes live profiles that belong to no account. Nobody signs in as
 * them any more, and the signup refuses a new account whose username such a
 * profile still carries. Their content stays, as no account decides on it.
 *
 * Profiles in contexts that no longer exist, or without their items row, are
 * left to {@see FixContext} and {@see FixItemLink}, which run before.
 */
final readonly class CleanUpProfilesWithoutAccount implements DatabaseCheck
{
    public function __construct(
        private Connection $connection,
        private UserMembershipDeleter $membershipDeleter,
    ) {
    }

    public function getPriority(): int
    {
        return 196;
    }

    public function resolve(SymfonyStyle $io): bool
    {
        $io->text('Inspecting live profiles without an account');

        $profiles = $this->profiles();
        foreach ($profiles as $profile) {
            $io->text(sprintf(
                'Profile %d "%s" in context %d: deleting',
                $profile['item_id'],
                $profile['user_id'],
                $profile['context_id'],
            ));
            // DB-fix path: no user session, deleter id 0
            $this->membershipDeleter->softDeleteMembership((int) $profile['item_id'], 0, eraseContent: false);
        }

        $io->text(sprintf('%d deleted', count($profiles)));

        return true;
    }

    /**
     * @return list<array{item_id: int, user_id: string, context_id: int}>
     */
    private function profiles(): array
    {
        $qb = $this->connection->createQueryBuilder();
        $itemsRow = $this->connection->createQueryBuilder()
            ->select('1')->from('items', 'i')->where('i.item_id = u.item_id');
        $room = $this->connection->createQueryBuilder()
            ->select('1')->from('room', 'r')->where('r.item_id = u.context_id');
        $portal = $this->connection->createQueryBuilder()
            ->select('1')->from('portal', 'p')->where('p.id = u.context_id');

        /** @var list<array{item_id: int, user_id: string, context_id: int}> */
        return $qb
            ->select('u.item_id', 'u.user_id', 'u.context_id')
            ->from('user', 'u')
            ->where('u.account_id IS NULL')
            ->andWhere('u.deletion_date IS NULL')
            ->andWhere('u.deleter_id IS NULL')
            ->andWhere("u.user_id != 'root'")
            ->andWhere('EXISTS ('.$itemsRow->getSQL().')')
            ->andWhere($qb->expr()->or(
                'EXISTS ('.$room->getSQL().')',
                'EXISTS ('.$portal->getSQL().')',
            ))
            ->orderBy('u.item_id')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
