<?php
namespace App\Model;

class SearchData
{
    /**
     * @var string|null
     */
    private $phrase;

    /**
     * @var boolean|null
     */
    private $allRooms;

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
     * @var \DateInterval|null $creationDateRange
     */
    private $creationDateRange;

    /**
     * @var \DateInterval|null $modificationDateRange
     */
    private $modificationDateRange;


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
     * @param string $selectedRubric
     * @return SearchData
     */
    public function setSelectedRubric(string $selectedRubric): SearchData
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
     * @param string $selectedCreator
     * @return SearchData
     */
    public function setSelectedCreator(string $selectedCreator): SearchData
    {
        $this->selectedCreator = $selectedCreator;
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