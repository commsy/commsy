<?php

namespace CommsyBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CommsyEditEvent extends Event {

    protected $item;

    function __construct($item) {
        $this->item = $item;
    }

    public function getItem() {
        return $this->item;
    }
}