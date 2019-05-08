<?php
namespace App\Command;

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class CronCommand extends Command
{
    private $stopwatch;

    private $legacyEnvironment;
    private $router;
    private $itemService;
    private $mailer;
    private $projectDir;
    private $emailFrom;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        RouterInterface $router,
        ItemService $itemService,
        \Swift_Mailer $mailer,
        $projectDir,
        $emailFrom
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->router = $router;
        $this->itemService = $itemService;
        $this->mailer = $mailer;
        $this->projectDir = $projectDir;
        $this->emailFrom = $emailFrom;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('commsy:cron:main')
            ->setDescription('main commsy cron')
            ->addArgument(
                'contextId',
                InputArgument::OPTIONAL,
                'Context ID (Portal / Server) to be processed in this run'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        chdir($this->projectDir . '/legacy/');
        $this->legacyEnvironment->setCacheOff();

        $this->stopwatch = new Stopwatch();

        $contextId = $input->getArgument('contextId');
        if ($contextId) {
            $output->writeln('<info>Running Cron tasks for single context ' . $contextId . '</info>');

            $this->performCronTasks($contextId, $output);
        } else {
            $output->writeln('<info>No explicit Cron context given - running full stack (Portals + Server)</info>');

            $serverItem = $this->legacyEnvironment->getServerItem();
            $portalIds = $serverItem->getPortalIDArray();

            foreach ($portalIds as $portalId) {
                $this->performCronTasks($portalId, $output);
            }
            $this->performCronTasks($this->legacyEnvironment->getServerID(), $output);
        }
    }

    private function performCronTasks($contextId, $output)
    {
        $item = $this->itemService->getTypedItem($contextId);
        if (!$item || $item->isDeleted()) {
            $output->writeln('<info>Skipping context ' . $contextId . ' - item is deleted</info>');
            return;
        }

        $output->writeln('<info>Running Cron tasks for context ' . $contextId . '</info>');

        $this->stopwatch->openSection();
        switch ($item->getType()) {
            case 'portal':
                $this->stopwatch->start('portal_main', 'portal_main');
                $item->runCron();
                $this->stopwatch->stop('portal_main');

                $this->stopwatch->start('private_main', 'private_main');
                $privateRoomIds = $item->getActiveUserPrivateIDArray();
                $this->performRoomTasks($privateRoomIds, true);
                $this->stopwatch->stop('private_main');

                $this->stopwatch->start('community_main', 'community_main');
                $communityRoomIds = $item->getActiveCommunityIDArray();
                $this->performRoomTasks($communityRoomIds);
                $this->stopwatch->stop('community_main');

                $this->stopwatch->start('project_main', 'project_main');
                $projectRoomIds = $item->getActiveProjectIDArray();
                $this->performRoomTasks($projectRoomIds);
                $this->stopwatch->stop('project_main');

                $this->stopwatch->start('grouproom_main', 'grouproom_main');
                $groupRoomIds = $item->getActiveGroupIDArray();
                $this->performRoomTasks($groupRoomIds);
                $this->stopwatch->stop('grouproom_main');

                $this->stopwatch->start('workflow_main', 'workflow_main');
                $this->performWorkflowTasks($item);
                $this->stopwatch->stop('workflow_main');

                break;

            case 'server':
                $this->stopwatch->start('server_main', 'server_main');
                $item->runCron();
                $this->stopwatch->stop('server_main');

                break;

            default:
                $output->writeln('<error>Cannot run Cron Tasks for type "' . $item->getType() . '"</error>');
                break;
        }
        $this->stopwatch->stopSection($contextId);

        $events = $this->stopwatch->getSectionEvents($contextId);
        $output->writeln($events);
        $output->writeln('');
    }

    private function performRoomTasks($roomIds, $privateRooms = false)
    {
        if ($privateRooms) {
            $roomManager = $this->legacyEnvironment->getPrivateRoomManager();
        } else {
            $roomManager = $this->legacyEnvironment->getRoomManager();
        }

        $roomManager->setCacheOff();

        foreach ($roomIds as $roomId) {
            $room = $roomManager->getItem($roomId);

            $isActive = false;

            if ($room->isCommunityRoom() ||
                    $room->isProjectRoom() ||
                    $room->isGroupRoom()) {

                if ($room->isOpen()) {
                    $isActive = $room->isActiveDuringLast99Days();
                }
            }

            if ($room->isPrivateRoom()) {
                $privateRoomUser = $room->getOwnerUserItem();
                if (isset($privateRoomUser) && $privateRoomUser->isUser()) {
                    $portalUserItem = $privateRoomUser->getRelatedCommSyUserItem();
                    if (isset($portalUserItem) && $portalUserItem->isUser()) {
                        $isActive = $portalUserItem->isActiveDuringLast99Days();
                    }
                }
            }

            if ($isActive) {
                $room->runCron();
            }
        }
    }

    private function performWorkflowTasks($portalItem)
    {
        $materialManager = $this->legacyEnvironment->getMaterialManager();

        $resubmissionItems = $materialManager->getResubmissionItemIDsByDate(date('Y'), date('m'), date('d'));
        foreach ($resubmissionItems as $resubmissionItemInfo) {
            $material = $materialManager->getItem($resubmissionItemInfo['item_id']);
            $latestMaterialVersionId = $materialManager->getLatestVersionID($resubmissionItemInfo['item_id']);

            if (isset($material) && !$material->isDeleted() && ($resubmissionItemInfo['item_id'] == $latestMaterialVersionId)) {
                $roomManager = $this->legacyEnvironment->getRoomManager();
                $room = $roomManager->getItem($material->getContextId());

                // check if context of room is current portal
                if ($room->getContextID() != $portalItem->getItemID()) continue;

                if ($material->getWorkflowResubmission() && $material->withWorkflowResubmission()) {
                    $emailReceivers = [];

                    if ($material->getWorkflowResubmissionWho() == 'creator') {
                        $emailReceivers[] = $material->getCreator();
                    } else {
                        $modifierList = $material->getModifierList();
                        $emailReceivers = $modifierList->to_array();
                    }

                    $to = [];
                    foreach ($emailReceivers as $emailReceiver) {
                        $to[] = $emailReceiver->getEmail();
                    }

                    $additionalReceiver = $material->getWorkflowResubmissionWhoAdditional();
                    if (!empty($additionalReceiver)) {
                        $to = array_merge($to, explode(',', $additionalReceiver));
                    }

                    $translator = $this->legacyEnvironment->getTranslationObject();

                    $path = $this->router->generate('app_material_detail', [
                        'roomId' => $room->getItemID(),
                        'itemId' => $material->getItemID(),
                        'versionId' => $material->getVersionID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $link = '<a href="' . $path . '">' . $material->getTitle() . '</a>';

                    $body = $translator->getMessage('COMMON_WORKFLOW_EMAIL_BODY_RESUBMISSION', $room->getTitle(), $material->getTitle(), $link);

                    $message = (new \Swift_Message())
                        ->setSubject($translator->getMessage('COMMON_WORKFLOW_EMAIL_SUBJECT_RESUBMISSION', $portalItem->getTitle()))
                        ->setBody($body, 'text/html')
                        ->setFrom([$this->emailFrom => $portalItem->getTitle()])
                        ->setTo($to);

                    $this->mailer->send($message);

                    // change material status
                    $materialManager->setWorkflowStatus($material->getItemID(), $material->getWorkflowResubmissionTrafficLight(), $material->getVersionID());
                }
            }
        }

        $validityItems = $materialManager->getValidityItemIDsByDate(date('Y'), date('m'), date('d'));
        foreach ($validityItems as $validityItemInfo) {
            $material = $materialManager->getItem($validityItemInfo['item_id']);
            $latestMaterialVersionId = $materialManager->getLatestVersionID($validityItemInfo['item_id']);

            if (isset($material) && !$material->isDeleted() && ($validityItemInfo['item_id'] == $latestMaterialVersionId)) {
                $roomManager = $this->legacyEnvironment->getRoomManager();
                $room = $roomManager->getItem($material->getContextId());

                // check if context of room is current portal
                if ($room->getContextID() != $portalItem->getItemID()) continue;

                if ($material->getWorkflowValidity() && $material->withWorkflowValidity()) {
                    $emailReceivers = [];

                    if ($material->getWorkflowValidityWho() == 'creator') {
                        $emailReceivers[] = $material->getCreator();
                    } else {
                        $modifierList = $material->getModifierList();
                        $emailReceivers = $modifierList->to_array();
                    }

                    $to = [];
                    foreach ($emailReceivers as $emailReceiver) {
                        $to[] = $emailReceiver->getEmail();
                    }

                    $additionalReceiver = $material->getWorkflowValidityWhoAdditional();
                    if (!empty($additionalReceiver)) {
                        $to = array_merge($to, explode(',', $additionalReceiver));
                    }

                    $translator = $this->legacyEnvironment->getTranslationObject();

                    $path = $this->router->generate('app_material_detail', [
                        'roomId' => $room->getItemID(),
                        'itemId' => $material->getItemID(),
                        'versionId' => $material->getVersionID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $link = '<a href="' . $path . '">' . $material->getTitle() . '</a>';

                    $body = $translator->getMessage('COMMON_WORKFLOW_EMAIL_BODY_VALIDITY', $room->getTitle(), $material->getTitle(), $link);

                    $message = (new \Swift_Message())
                        ->setSubject($translator->getMessage('COMMON_WORKFLOW_EMAIL_SUBJECT_VALIDITY', $portalItem->getTitle()))
                        ->setBody($body, 'text/html')
                        ->setFrom([$this->emailFrom => $portalItem->getTitle()])
                        ->setTo($to);

                    $this->mailer->send($message);

                    // change material status
                    $materialManager->setWorkflowStatus($material->getItemID(), $material->getWorkflowValidityTrafficLight(), $material->getVersionID());
                }
            }
        }
    }
}