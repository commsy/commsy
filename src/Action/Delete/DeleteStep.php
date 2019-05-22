<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 22:13
 */

namespace App\Action\Delete;


use Symfony\Component\Routing\RouterInterface;

class DeleteStep implements DeleteInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param \cs_item $item
     */
    public function delete(\cs_item $item): void
    {
        $item->delete();
    }

    /**
     * @param \cs_item $item
     * @return string|null
     */
    public function getRedirectRoute(\cs_item $item)
    {
        /** @var \cs_step_item $step */
        $step = $item;

        return $this->router->generate('app_todo_detail', [
            'roomId' => $step->getContextID(),
            'itemId' => $step->getTodoID(),
        ]);
    }
}