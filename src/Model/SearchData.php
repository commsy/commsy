<?php
namespace App\Model;

use App\Entity\SavedSearch;
use Symfony\Component\Validator\Constraints as Assert;

class SearchData
{
    /**
     * @var SavedSearch|null $selectedSavedSearch the currently selected saved search (aka "view")
     */
    private $selectedSavedSearch;

    /**
     * @var string|null
     * @Assert\NotBlank(normalizer="trim", groups={"save"})
     */
    private $selectedSavedSearchTitle;

    /**
     * @var SavedSearch[]|null $savedSearches array of all saved searches belonging to the current user's account
     */
    private $savedSearches;

    /**
     * @var string|null
     */
    private $phrase;

    /**
     * @var boolean|null
     */
    private $allRooms;

    /**
     * @var boolean|null
     */
    private $appearsInTitle;

    /**
     * @var boolean|null
     */
    private $appearsInDescription;

    /**
     * @var array|null associative array of rubrics (key: rubric name, value: count)
     */
    private $rubrics;

    /**
     * @var string|null
     */
    private $selectedRubric;

    /**
     * @var array|null associative array of creators (key: creator name, value: count)
     */
    private $creators;

    /**
     * @var string|null $selectedCreator
     */
    private $selectedCreator;

    /**
     * @var array|null associative array of hashtags (key: hashtag name, value: count)
     */
    private $hashtags;

    /**
     * @var string[]|null $selectedHashtags
     */
    private $selectedHashtags;

    /**
     * @var array|null associative array of categories (key: category name, value: count)
     */
    private $categories;

    /**
     * @var string[]|null $selectedCategories
     */
    private $selectedCategories;

    /**
     * @var \DateTime|null $creationDateFrom
     */
    private $creationDateFrom;

    /**
     * @var \DateTime|null $creationDateUntil
     */
    private $creationDateUntil;

    /**
     * @var \DateTime|null $modificationDateFrom
     */
    private $modificationDateFrom;

    /**
     * @var \DateTime|null $modificationDateUntil
     */
    private $modificationDateUntil;

    /**
     * @return SavedSearch|null
     */
    public function getSelectedSavedSearch(): ?SavedSearch
    {
        return $this->selectedSavedSearch;
    }

    /**
     * @param SavedSearch|null $selectedSavedSearch
     * @return SearchData
     */
    public function setSelectedSavedSearch(?SavedSearch $selectedSavedSearch): SearchData
    {
        $this->selectedSavedSearch = $selectedSavedSearch;
        return $this;
    }

    /**
     * @return SavedSearch[]|null
     */
    public function getSavedSearches(): ?array
    {
        return $this->savedSearches;
    }

    /**
     * @param SavedSearch[]|null $savedSearches
     * @return SearchData
     */
    public function setSavedSearches(?array $savedSearches): SearchData
    {
        $this->savedSearches = $savedSearches;
        return $this;
    }

    /**
     * @return int
     */
    public function getSelectedSavedSearchId(): int
    {
        if (!$this->selectedSavedSearch || !$this->selectedSavedSearch->getId()) {
            return 0;
        }
        return $this->selectedSavedSearch->getId();
    }

    /**
     * @return string|null
     */
    public function getSelectedSavedSearchTitle(): ?string
    {
        return $this->selectedSavedSearchTitle;

//        if (!$this->selectedSavedSearch || !$this->selectedSavedSearch->getTitle()) {
//            return '';
//        }
//        return $this->selectedSavedSearch->getTitle();
    }

    /**
     * @param string|null $title
     * @return SearchData
     */
    public function setSelectedSavedSearchTitle(?string $title): SearchData
    {
        $this->selectedSavedSearchTitle = $title;
        return $this;

//        if ($this->selectedSavedSearch && !empty($title)) {
//            $this->selectedSavedSearch->setTitle($title);
//        }
//        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhrase(): ?string
    {
        return $this->phrase;
    }

    /**
     * @param string|null $phrase
     * @return SearchData
     */
    public function setPhrase(?string $phrase): SearchData
    {
        $this->phrase = $phrase;
        return $this;
    }

    /**
     * @return boolean|null
     */
    public function getAllRooms(): ?bool
    {
        return $this->allRooms;
    }

    /**
     * @param boolean $allRooms
     * @return SearchData
     */
    public function setAllRooms(bool $allRooms): SearchData
    {
        $this->allRooms = $allRooms;
        return $this;
    }

    /**
     * @return array an array of field names describing the fields that must contain a search term
     */
    public function getAppearsIn(): array
    {
        $appearsIn = [];
        if ($this->getAppearsInTitle()) {
            $appearsIn[] = 'title';
        }
        if ($this->getAppearsInDescription()) {
            $appearsIn[] = 'description';
        }
        return $appearsIn;
    }

    /**
     * @param array $appearsIn an array of field names describing the fields that must contain a search term
     * @return SearchData
     */
    public function setAppearsIn(array $appearsIn): SearchData
    {
        $this->setAppearsInTitle(in_array('title', $appearsIn, true) ? true : false);
        $this->setAppearsInDescription(in_array('description', $appearsIn, true) ? true : false);
        return $this;
    }

    /**
     * @return boolean|null
     */
    public function getAppearsInTitle(): ?bool
    {
        return $this->appearsInTitle;
    }

    /**
     * @param boolean $appearsInTitle
     */
    public function setAppearsInTitle(bool $appearsInTitle): SearchData
    {
        $this->appearsInTitle = $appearsInTitle;
        return $this;
    }

    /**
     * @return boolean|null
     */
    public function getAppearsInDescription(): ?bool
    {
        return $this->appearsInDescription;
    }

    /**
     * @param boolean $appearsInDescription
     */
    public function setAppearsInDescription(bool $appearsInDescription): SearchData
    {
        $this->appearsInDescription = $appearsInDescription;
        return $this;
    }

    /**
     * @return array|null associative array of rubrics (key: rubric name, value: count)
     */
    public function getRubrics(): ?array
    {
        return $this->rubrics;
    }

    /**
     * @param array $rubrics associative array of rubrics (key: rubric name, value: count)
     * @return SearchData
     */
    public function setRubrics(array $rubrics): SearchData
    {
        $this->rubrics = $rubrics;
        return $this;
    }

    /**
     * @param array $rubrics associative array of rubrics (key: rubric name, value: count)
     * @return SearchData
     */
    public function addRubrics(array $rubrics): SearchData
    {
        foreach ($rubrics as $name => $count) {
            $this->rubrics[$name] = $count;
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSelectedRubric(): ?string
    {
        return $this->selectedRubric;
    }

    /**
     * @param string|null $selectedRubric
     * @return SearchData
     */
    public function setSelectedRubric(?string $selectedRubric): SearchData
    {
        $this->selectedRubric = $selectedRubric;
        return $this;
    }

    /**
     * @return array|null associative array of creators (key: creator name, value: count)
     */
    public function getCreators(): ?array
    {
        return $this->creators;
    }

    /**
     * @param array $creators associative array of creators (key: creator name, value: count)
     * @return SearchData
     */
    public function setCreators(array $creators): SearchData
    {
        $this->creators = $creators;
        return $this;
    }

    /**
     * @param array $creators associative array of creators (key: creator name, value: count)
     * @return SearchData
     */
    public function addCreators(array $creators): SearchData
    {
        foreach ($creators as $name => $count) {
            $this->creators[$name] = $count;
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSelectedCreator(): ?string
    {
        return $this->selectedCreator;
    }

    /**
     * @param string|null $selectedCreator
     * @return SearchData
     */
    public function setSelectedCreator(?string $selectedCreator): SearchData
    {
        $this->selectedCreator = $selectedCreator;
        return $this;
    }

    /**
     * @return array|null associative array of hashtags (key: hashtag name, value: count)
     */
    public function getHashtags(): ?array
    {
        return $this->hashtags;
    }

    /**
     * @param array $hashtags associative array of hashtags (key: hashtag name, value: count)
     * @return SearchData
     */
    public function setHashtags(array $hashtags): SearchData
    {
        $this->hashtags = $hashtags;
        return $this;
    }

    /**
     * @param array $hashtags associative array of hashtags (key: hashtag name, value: count)
     * @return SearchData
     */
    public function addHashtags(array $hashtags): SearchData
    {
        foreach ($hashtags as $name => $count) {
            $this->hashtags[$name] = $count;
        }
        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getSelectedHashtags(): ?array
    {
        return $this->selectedHashtags;
    }

    /**
     * @param string[] $selectedHashtags
     * @return SearchData
     */
    public function setSelectedHashtags(array $selectedHashtags): SearchData
    {
        $this->selectedHashtags = $selectedHashtags;
        return $this;
    }

    /**
     * @return array|null associative array of categories (key: category name, value: count)
     */
    public function getCategories(): ?array
    {
        return $this->categories;
    }

    /**
     * @param array $categories associative array of categories (key: category name, value: count)
     * @return SearchData
     */
    public function setCategories(array $categories): SearchData
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @param array $categories associative array of categories (key: category name, value: count)
     * @return SearchData
     */
    public function addCategories(array $categories): SearchData
    {
        foreach ($categories as $name => $count) {
            $this->categories[$name] = $count;
        }
        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getSelectedCategories(): ?array
    {
        return $this->selectedCategories;
    }

    /**
     * @param string[] $selectedCategories
     * @return SearchData
     */
    public function setSelectedCategories(array $selectedCategories): SearchData
    {
        $this->selectedCategories = $selectedCategories;
        return $this;
    }

    /**
     * @return array|null an array of two items, start & end date, which may be \DateTime objects or null
     */
    public function getCreationDateRange(): ?array
    {
        return [
            $this->getCreationDateFrom(),
            $this->getCreationDateUntil(),
            ];
    }

    /**
     * @param array|null $creationDateRange an array of two items, start & end date, which may be \DateTime objects or null
     * @return SearchData
     */
    public function setCreationDateRange(?array $creationDateRange): SearchData
    {
        // start date
        if (isset($creationDateRange[0]) && $creationDateRange[0] instanceof \DateTime) {
            $this->setCreationDateFrom($creationDateRange[0]);
        }
        // end date
        if (isset($creationDateRange[1]) && $creationDateRange[1] instanceof \DateTime) {
            $this->setCreationDateUntil($creationDateRange[1]);
        }
        return $this;
    }

    /**
     * @return array|null an array of two items, start & end date, which may be \DateTime objects or null
     */
    public function getModificationDateRange(): ?array
    {
        return [
            $this->getModificationDateFrom(),
            $this->getModificationDateUntil(),
            ];
    }

    /**
     * @param array|null $modificationDateRange an array of two items, start & end date, which may be \DateTime objects or null
     * @return SearchData
     */
    public function setModificationDateRange(?array $modificationDateRange): SearchData
    {
        if (isset($modificationDateRange[0]) && $modificationDateRange[0] instanceof \DateTime) {
            $this->setModificationDateFrom($modificationDateRange[0]);
        }
        if (isset($modificationDateRange[1]) && $modificationDateRange[1] instanceof \DateTime) {
            $this->setModificationDateUntil($modificationDateRange[1]);
        }
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreationDateFrom(): ?\DateTime
    {
        return $this->creationDateFrom;
    }

    /**
     * @param \DateTime|null $creationDateFrom
     * @return SearchData
     */
    public function setCreationDateFrom(?\DateTime $creationDateFrom): SearchData
    {
        $this->creationDateFrom = $creationDateFrom;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreationDateUntil(): ?\DateTime
    {
        return $this->creationDateUntil;
    }

    /**
     * @param \DateTime|null $creationDateUntil
     * @return SearchData
     */
    public function setCreationDateUntil(?\DateTime $creationDateUntil): SearchData
    {
        $this->creationDateUntil = $creationDateUntil;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getModificationDateFrom(): ?\DateTime
    {
        return $this->modificationDateFrom;
    }

    /**
     * @param \DateTime|null $modificationDateFrom
     * @return SearchData
     */
    public function setModificationDateFrom(?\DateTime $modificationDateFrom): SearchData
    {
        $this->modificationDateFrom = $modificationDateFrom;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getModificationDateUntil(): ?\DateTime
    {
        return $this->modificationDateUntil;
    }

    /**
     * @param \DateTime|null $modificationDateUntil
     * @return SearchData
     */
    public function setModificationDateUntil(?\DateTime $modificationDateUntil): SearchData
    {
        $this->modificationDateUntil = $modificationDateUntil;
        return $this;
    }
}
