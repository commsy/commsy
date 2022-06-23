<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CommsyEditEvent extends Event {

    const EDIT = 'commsy.edit';
    const SAVE = 'commsy.save';
    const CANCEL = 'commsy.cancel';

    protected $item;

    function __construct($item) {
        $this->item = $item;
    }

    public function getItem() {
        return $this->item;
    }
}