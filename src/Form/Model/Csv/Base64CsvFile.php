<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 29.07.18
 * Time: 23:32
 */

namespace App\Form\Model\Csv;

use App\Form\Model\Csv\CsvUserDataset;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class Base64CsvFile
{
    /**
     * @var bool
     */
    private $checked;

    /**
     * @var array
     */
    private $base64Content;

    /**
     * @var CsvUserDataset[]
     * @Assert\Valid()
     */
    private $csvUserDatasets;

    public function __construct()
    {
        $this->csvUserDatasets = new ArrayCollection();
    }


    public function setChecked($checked): Base64CsvFile
    {
        $this->checked = $checked;

        return $this;
    }

    public function getChecked(): ?bool
    {
        return $this->checked;
    }

    public function getFilename(): string
    {
        return "file123";
    }

//    public function setBase64Content(array $base64Content): Base64CsvFile
//    {
//        $this->base64Content = $base64Content;
//
//        return $this;
//    }
//
//    public function getBase64Content(): ?array
//    {
//        return $this->base64Content;
//    }

    public function setBase64Content(Collection $csvUserDatasets): Base64CsvFile
    {
        $this->csvUserDatasets = $csvUserDatasets;

        return $this;
    }

    public function getBase64Content(): Collection
    {
        return $this->csvUserDatasets;
    }
}