<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 16.07.18
 * Time: 22:46
 */

namespace App\Action\Delete;


use Symfony\Component\Routing\RouterInterface;

class DeleteSection implements DeleteInterface
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
        /** @var \cs_section_item $section */
        $section = $item;

        $material = $section->getLinkedItem();
        $section->delete($material->getVersionID());
    }

    /**
     * @param \cs_item $item
     * @return string|null
     */
    public function getRedirectRoute(\cs_item $item)
    {
        /** @var \cs_section_item $section */
        $section = $item;

        return $this->router->generate('commsy_material_detail', [
            'roomId' => $section->getContextID(),
            'itemId' => $section->getLinkedItemID(),
        ]);
    }
}