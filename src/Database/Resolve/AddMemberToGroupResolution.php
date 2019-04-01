<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.04.18
 * Time: 17:09
 */

namespace App\Database\Resolve;


use App\Services\LegacyEnvironment;

class AddMemberToGroupResolution implements ResolutionInterface
{
    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    public function __construct(\cs_environment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function resolve($problems)
    {
        foreach ($problems as $problem) {
            $data = $problem->getObject();

            /** @var \cs_user_item $userItem */
            $userItem = $data['user'];
            /** @var \cs_group_item $groupAll */
            $groupAll = $data['group'];

            $linkManager = $this->legacyEnvironment->getLinkItemManager();

            /** @var \cs_link_item $linkItem */
            $linkItem = $linkManager->getNewItem();

            $linkItem->setCreatorItem($userItem);
            $linkItem->setModificatorItem($userItem);
            $linkItem->setFirstLinkedItem($groupAll);
            $linkItem->setSecondLinkedItem($userItem);

            $linkItem->save();
        }

        return true;
    }

    public function getKey()
    {
        return 'add_to_group';
    }

    public function getDescription()
    {
        return '';
    }
}