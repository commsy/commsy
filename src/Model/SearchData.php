<?php
namespace App\Model;

class SearchData
{
    private $phrase;

    private $allRooms;

    private $rubrics;

    private $selectedRubric;

    /**
     * @var string[] $creators
     */
    private $creators;

    /**
     * @var string[] $selectedCreators
     */
    private $selectedCreators;

    /**
     * @var \DateInterval $creationDateRange
     */
    private $creationDateRange;

    /**
     * @var \DateInterval $modificationDateRange
     */
    private $modificationDateRange;

    public function setPhrase($phrase)
    {
        $this->phrase = $phrase;

        return $this;
    }

    public function getPhrase()
    {
        return $this->phrase;
    }

    /**
     * @return mixed
     */
    public function getAllRooms()
    {
        return $this->allRooms;
    }

    /**
     * @param mixed $allRooms
     * @return SearchData
     */
    public function setAllRooms($allRooms)
    {
        $this->allRooms = $allRooms;
        return $this;
    }

    /**
     * @return array
     */
    public function getRubrics()
    {
        return $this->rubrics;
    }

    /**
     * @param array $rubrics
     * @return SearchData
     */
    public function setRubrics($rubrics)
    {
        $this->rubrics = $rubrics;
        return $this;
    }

    /**
     * @param string $rubric
     * @return $this
     */
    public function addRubric($rubric)
    {
        $this->rubrics[] = $rubric;
        return $this;
    }

    /**
     * @return string
     */
    public function getSelectedRubric()
    {
        return $this->selectedRubric;
    }

    /**
     * @param string $selectedRubric
     * @return SearchData
     */
    public function setSelectedRubric($selectedRubric)
    {
        $this->selectedRubric = $selectedRubric;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getCreators(): ?array
    {
        return $this->creators;
    }

    /**
     * @param string[] $creators
     * @return SearchData
     */
    public function setCreators(array $creators): SearchData
    {
        $this->creators = $creators;
        return $this;
    }

    /**
     * @param string $creator
     * @return SearchData
     */
    public function addCreator(string $creator): SearchData
    {
        $this->creators[] = $creator;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getSelectedCreators(): ?array
    {
        return $this->selectedCreators;
    }

    /**
     * @param string[] $selectedCreators
     * @return SearchData
     */
    public function setSelectedCreators(array $selectedCreators): SearchData
    {
        $this->selectedCreators = $selectedCreators;
        return $this;
    }

    /**
     * @return \DateInterval
     */
    public function getCreationDateRange(): ?\DateInterval
    {
        return $this->creationDateRange;
    }

    /**
     * @param \DateInterval $creationDateRange
     * @return SearchData
     */
    public function setCreationDateRange(\DateInterval $creationDateRange): SearchData
    {
        $this->creationDateRange = $creationDateRange;
        return $this;
    }

    /**
     * @return \DateInterval
     */
    public function getModificationDateRange(): ?\DateInterval
    {
        return $this->modificationDateRange;
    }

    /**
     * @param \DateInterval $modificationDateRange
     * @return SearchData
     */
    public function setModificationDateRange(\DateInterval $modificationDateRange): SearchData
    {
        $this->modificationDateRange = $modificationDateRange;
        return $this;
    }
}