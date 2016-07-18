<?php
namespace CommsyBundle\Migrations;

use Doctrine\ORM\EntityManager;

class RoomMigration extends AbstractMigration
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function migrateRoomConfiguration()
    {
        $repository = $this->em->getRepository('CommsyBundle:Room');
        $rooms = $repository->findAll();

        foreach ($rooms as $room) {
            $this->removeEvents($this->em, $room);

            $extras = $room->getExtras();

            if (isset($extras['HOMECONF']) && !empty($extras['HOMECONF'])) {
                $homeConf = $this->migrateHomeConf($extras['HOMECONF']);
                $extras['HOMECONF'] = $homeConf;

                $room->setExtras($extras);
            }
        }

        $this->em->flush();
    }

    private function migrateHomeConf($homeConfiguration)
    {
        // old home configuration syntax looks like
        // [rubric]_[short|tiny|none]
        // since we now got a feed, we need to convert these values
        $convertMap = array(
            '' => 'show',
            'short' => 'show',
            'tiny' => 'show',
            'none' => 'hide',
            'nodisplay' => 'hide',
        );

        $convertedConfiguration = array();

        $rubricConfigurations = explode(',', $homeConfiguration);
        foreach ($rubricConfigurations as $rubricConfiguration) {
            list($rubric, $mode) = explode('_', $rubricConfiguration);

            $convertedConfiguration[] = $rubric . '_' . ($convertMap[$mode] ? $convertMap[$mode] : $convertMap['']);
        }

        return implode(',', $convertedConfiguration);
    }
}