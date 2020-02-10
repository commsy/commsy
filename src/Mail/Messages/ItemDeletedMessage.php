<?php


namespace App\Mail\Messages;


use App\Mail\Message;

class ItemDeletedMessage extends Message
{
    private $item;
    private $deleter;

    public function __construct(\cs_item $item, \cs_user_item $deleter)
    {
        $this->item = $item;
        $this->deleter = $deleter;
    }

    public function getSubject(): string
    {
        return 'Deleted entry (%room_name%)';
    }

    public function getTemplateName(): string
    {
        return 'mail/item_deleted.html.twig';
    }

    public function getParameters(): array
    {
        return [
            'item' => $this->item,
            'deleter' => $this->deleter,
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            '%room_name%' => $this->item->getContextItem()->getTitle(),
        ];
    }
}