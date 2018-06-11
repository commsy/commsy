<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.04.18
 * Time: 17:14
 */

namespace CommsyBundle\Database\Resolve;


use CommsyBundle\Entity\Room;

class CreateGroupAllResolution implements ResolutionInterface
{
    private $legacyEnvironment;

    public function __construct(\cs_environment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function resolve($problems)
    {
        $groupManager = $this->legacyEnvironment->getGroupManager();

        foreach ($problems as $problem) {
            /** @var Room $room */
            $room = $problem->getObject();

            /** @var \cs_group_item $group */
            $group = $groupManager->getNewItem('group');
            $group->setName('ALL');
            $group->setDescription('GROUP_ALL_DESC');
            $group->setContextID($room->getItemId());
            $group->setCreatorID($room->getCreator()->getItemId());
            $group->makeSystemLabel();
            $group->save();
        }

        return true;
    }

    public function getKey()
    {
        return 'create_group_all';
    }

    public function getDescription()
    {
        return '';
    }
}