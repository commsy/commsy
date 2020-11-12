<?php

namespace App\Services;

use App\Entity\SavedSearch;
use Doctrine\ORM\EntityManagerInterface;

class SavedSearchesService
{
    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Returns an array of saved searches belonging to the given account ID, or an empty
     * array if nothing was found.
     * @param int $accountId The ID of the user account whose saved searches shall be returned
     * @return SavedSearch[] An array of saved searches belonging to the given account ID
     */
    public function getSavedSearchesForAccountId(int $accountId): array
    {
        $result = [];

        $repository = $this->em->getRepository('App:SavedSearch');
        $query = $repository->createQueryBuilder('savedsearches')
            ->select()
            ->where('savedsearches.account_id = :account_id')
            ->setParameter('account_id', $accountId)
            ->getQuery();
        $savedSearches = $query->getResult();

        foreach ($savedSearches as $savedSearch) {
            $result[] = $savedSearch;
        }

        return $result;
    }

    /**
     * Returns the saved search with the given ID, or null if nothing was found.
     * @param int $savedSearchId The ID of the saved search to be returned.
     * @return SavedSearch|null
     */
    public function getSavedSearch(int $savedSearchId): ?SavedSearch
    {
        $repository = $this->em->getRepository('App:SavedSearch');
        $query = $repository->createQueryBuilder('savedsearches')
            ->select()
            ->where('savedsearches.id = :saved_search_id')
            ->setParameter('saved_search_id', $savedSearchId)
            ->getQuery();
        $savedSearch = $query->getResult();

        return $savedSearch;
    }

    /**
     * Deletes the given saved search.
     * @param SavedSearch $savedSearch
     */
    public function removeSavedSearch(SavedSearch $savedSearch)
    {
        $this->em->remove($savedSearch);

        $this->em->flush();
    }
}
