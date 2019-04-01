<?php
namespace App\Model;

class GlobalSearch
{
    private $phrase;

    public function setPhrase($phrase)
    {
        $this->phrase = $phrase;

        return $this;
    }

    public function getPhrase()
    {
        return $this->phrase;
    }
}