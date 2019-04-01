<?php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class CronCommand extends ContainerAwareCommand
{
    private $stopwatch;

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
        // get dependencies
        $container = $this->getContainer();
        $kernelRootDir = $container->getParameter('kernel.root_dir');
        $legacyEnvironment = $container->get('commsy_legacy.environment')->getEnvironment();
        $logger = $container->get('logger');

        chdir($kernelRootDir.'/../legacy/');
        $legacyEnvironment->setCacheOff();

        $this->stopwatch = new Stopwatch();

        $contextId = $input->getArgument('contextId');
        if ($contextId) {
            $output->writeln('<info>Running Cron tasks for single context ' . $contextId . '</info>');

            $this->performCronTasks($contextId, $output);
        } else {
            $output->writeln('<info>No explicit Cron context given - running full stack (Portals + Server)</info>');

            $serverItem = $legacyEnvironment->getServerItem();
            $portalIds = $serverItem->getPortalIDArray();

            foreach ($portalIds as $portalId) {
                $this->performCronTasks($portalId, $output);
            }
            $this->performCronTasks($legacyEnvironment->getServerID(), $output);
        }
    }

    private function performCronTasks($contextId, $output)
    {
        $container = $this->getContainer();
        $itemService = $container->get('commsy_legacy.item_service');

        $item = $itemService->getTypedItem($contextId);
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
        $container = $this->getContainer();
        $legacyEnvironment = $container->get('commsy_legacy.environment')->getEnvironment();

        if ($privateRooms) {
            $roomManager = $legacyEnvironment->getPrivateRoomManager();
        } else {
            $roomManager = $legacyEnvironment->getRoomManager();
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
        $container = $this->getContainer();
        $legacyEnvironment = $container->get('commsy_legacy.environment')->getEnvironment();

//        global $cs_special_language_tags;

        $materialManager = $legacyEnvironment->getMaterialManager();

        $resubmissionItems = $materialManager->getResubmissionItemIDsByDate(date('Y'), date('m'), date('d'));
        foreach ($resubmissionItems as $resubmissionItemInfo) {
            $material = $materialManager->getItem($resubmissionItemInfo['item_id']);
            $latestMaterialVersionId = $materialManager->getLatestVersionID($resubmissionItemInfo['item_id']);

            if (isset($material) && !$material->isDeleted() && ($resubmissionItemInfo['item_id'] == $latestMaterialVersionId)) {
                $roomManager = $legacyEnvironment->getRoomManager();
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

                    $translator = $legacyEnvironment->getTranslationObject();

                    $path = $container->get('router')->generate('commsy_material_detail', [
                        'roomId' => $room->getItemID(),
                        'itemId' => $material->getItemID(),
                        'versionId' => $material->getVersionID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $link = '<a href="' . $path . '">' . $material->getTitle() . '</a>';

//                    if (isset($cs_special_language_tags) and !empty($cs_special_language_tags)){
//                        $mail->set_message($translator->getMessage($cs_special_language_tags.'_WORKFLOW_EMAIL_BODY_RESUBMISSION', $temp_room->getTitle(), $temp_material->getTitle(), $link));
//                    } else {
//                        $mail->set_message($translator->getMessage('COMMON_WORKFLOW_EMAIL_BODY_RESUBMISSION', $temp_room->getTitle(), $temp_material->getTitle(), $link));
//                    }

                    $body = $translator->getMessage('COMMON_WORKFLOW_EMAIL_BODY_RESUBMISSION', $room->getTitle(), $material->getTitle(), $link);

                    $message = (new \Swift_Message())
                        ->setSubject($translator->getMessage('COMMON_WORKFLOW_EMAIL_SUBJECT_RESUBMISSION', $portalItem->getTitle()))
                        ->setBody($body, 'text/html')
                        ->setFrom([$container->getParameter('commsy.email.from') => $portalItem->getTitle()])
                        ->setTo($to);

                    $container->get('mailer')->send($message);

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
                $roomManager = $legacyEnvironment->getRoomManager();
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

                    $translator = $legacyEnvironment->getTranslationObject();

                    $path = $container->get('router')->generate('commsy_material_detail', [
                        'roomId' => $room->getItemID(),
                        'itemId' => $material->getItemID(),
                        'versionId' => $material->getVersionID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $link = '<a href="' . $path . '">' . $material->getTitle() . '</a>';

//                    if (isset($cs_special_language_tags) and !empty($cs_special_language_tags)){
//                        $mail->set_message($translator->getMessage($cs_special_language_tags.'_WORKFLOW_EMAIL_BODY_RESUBMISSION', $temp_room->getTitle(), $temp_material->getTitle(), $link));
//                    } else {
//                        $mail->set_message($translator->getMessage('COMMON_WORKFLOW_EMAIL_BODY_VALIDITY', $temp_room->getTitle(), $temp_material->getTitle(), $link));
//                    }

                    $body = $translator->getMessage('COMMON_WORKFLOW_EMAIL_BODY_VALIDITY', $room->getTitle(), $material->getTitle(), $link);

                    $message = (new \Swift_Message())
                        ->setSubject($translator->getMessage('COMMON_WORKFLOW_EMAIL_SUBJECT_VALIDITY', $portalItem->getTitle()))
                        ->setBody($body, 'text/html')
                        ->setFrom([$container->getParameter('commsy.email.from') => $portalItem->getTitle()])
                        ->setTo($to);

                    $container->get('mailer')->send($message);

                    // change material status
                    $materialManager->setWorkflowStatus($material->getItemID(), $material->getWorkflowValidityTrafficLight(), $material->getVersionID());
                }
            }
        }
    }

//    function displayCronResults ( $array ) {
//        global $file;
//        $html = '';
//        foreach ($array as $cron_status => $crons) {
//            $html .= '<table border="0" summary="Layout">'.LF;
//            $html .= '<tr>'.LF;
//            $html .= '<td style="vertical-align:top; width: 4em;">'.LF;
//            $html .= '<span style="font-weight: bold;">'.$cron_status.'</span>'.LF;
//            $html .= '</td>'.LF;
//            $html .= '<td>'.LF;
//            if ( !empty($crons) ) {
//                foreach ($crons as $cron) {
//                    $html .= '<div>'.LF;
//                    $html .= '<span style="font-weight: bold;">'.$cron['title'].'</span>'.BRLF;
//                    if (!empty($cron['description'])) {
//                        $html .= $cron['description'];
//                        if (isset($cron['success'])) {
//                            if ($cron['success']) {
//                                $html .= ' [<font color="#00ff00">done</font>]'.BRLF;
//                            } else {
//                                $html .= ' [<font color="#ff0000>failed</font>]'.BRLF;
//                            }
//                        } else {
//                            $html .= ' [<font color="#ff0000>failed</font>]'.BRLF;
//                        }
//                    }
//                    if ( !empty($cron['success_text']) ) {
//                        $html .= $cron['success_text'].BRLF;
//                    }
//                    if ( !empty($cron['time']) ) {
//                        $time = $cron['time'];
//                        if ( $time < 60 ) {
//                            $time_text = 'Total execution time: '.$time.' seconds';
//                        } elseif ( $time < 3600 ) {
//                            $time2 = floor($time / 60);
//                            $sec2 = $time % 60;
//                            $time_text = 'Total execution time: '.$time2.' minutes '.$sec2.' seconds';
//                        } else {
//                            $hour = floor($time / 3600);
//                            $sec = $time % 3660;
//                            if ( $sec > 60 ) {
//                                $minutes = floor($sec / 60);
//                                $sec = $sec % 60;
//                            }
//                            $time_text = 'Total execution time: '.$hour.' hours '.$minutes.' minutes '.$sec.' seconds';
//                        }
//                        $html .= $time_text.BRLF;
//                    } elseif ( isset($cron['time']) ) {
//                        $time_text = 'Total execution time: 0 seconds';
//                        $html .= $time_text.BRLF;
//                    }
//                    $html .= '</div>'.LF;
//                }
//            } else {
//                $html .= 'no crons defined';
//            }
//            $html .= '</td>'.LF;
//            $html .= '</tr>'.LF;
//            $html .= '</table>'.LF;
//        }
//        fwrite($file, $html);
//        unset($html);
//    }
}