<?php
namespace CommsyBundle\Migrations;

use Doctrine\ORM\EntityManager;
use Commsy\LegacyBundle\Utils\MaterialService;

class MaterialsMigration
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function migrateMaterialWorkflowConfiguration()
    {
        $repository = $this->em->getRepository('CommsyBundle:Materials');
        $materials = $repository->findAll();

        foreach ($materials as $material) {
            $extras = $material->getExtras();
            
            if (isset($extras['WORKFLOWVALIDITY'])) {
                if ($extras['WORKFLOWVALIDITY'] == 'on') {
                    $extras['WORKFLOWVALIDITY'] = '1';
                } else if ($extras['WORKFLOWVALIDITY'] == '0') {
                    $extras['WORKFLOWVALIDITY'] = '-1';
                }
                $material->setExtras($extras);
            }

            if (isset($extras['WORKFLOWRESUBMISSION'])) {
                if ($extras['WORKFLOWRESUBMISSION'] == 'on') {
                    $extras['WORKFLOWRESUBMISSION'] = '1';
                } else if ($extras['WORKFLOWRESUBMISSION'] == '0') {
                    $extras['WORKFLOWRESUBMISSION'] = '-1';
                }
                $material->setExtras($extras);
            }
        }

        $this->em->flush();
    }
}