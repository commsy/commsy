<?php

namespace App\Message;

class WorkspaceActivityStateTransitions
{
    /**
     * @var int[]
     */
    private array $ids;

    /**
     * @param array $ids
     */
    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    /**
     * @return int[]
     */
    public function getIds(): array
    {
        return $this->ids;
    }
}
