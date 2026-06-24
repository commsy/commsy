<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Cron\Tasks;

use App\Entity\Room;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Repository\RoomRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CronWorkflow implements CronTaskInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly RouterInterface $router,
        private readonly Mailer $mailer,
        private readonly RoomRepository $roomRepository,
        private readonly TranslatorInterface $translator
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $materialManager = $this->legacyEnvironment->getMaterialManager();

        $resubmissionItems = $materialManager->getResubmissionItemIDsByDate(date('Y'), date('m'), date('d'));
        foreach ($resubmissionItems as $resubmissionItemInfo) {
            $material = $materialManager->getItem($resubmissionItemInfo['item_id']);
            $latestMaterialVersionId = $materialManager->getLatestVersionID($resubmissionItemInfo['item_id']);

            if (isset($material) && !$material->isDeleted() && ($resubmissionItemInfo['version_id'] == $latestMaterialVersionId)) {
                /** @var Room $room */
                $room = $this->roomRepository->find($material->getContextId());
                if ($material->getWorkflowResubmission() && $room->withWorkflowResubmission()) {
                    $emailReceivers = [];

                    if ('creator' == $material->getWorkflowResubmissionWho()) {
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
                        foreach (explode(',', (string) $additionalReceiver) as $receiver) {
                            $to[] = trim($receiver);
                        }
                    }
                    $to = array_unique($to);
                    $recipients = [];
                    foreach ($to as $mail) {
                        $recipients[] = RecipientFactory::createFromRaw($mail);
                    }

                    $path = $this->router->generate('app_material_detail', [
                        'roomId' => $room->getItemID(),
                        'itemId' => $material->getItemID(),
                        'versionId' => $material->getVersionID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $link = '<a href="'.$path.'">'.$material->getTitle().'</a>';

                    $body = $this->translator->trans('mail.workflow.resubmission_body', ['p1' => $room->getTitle(), 'p2' => $material->getTitle(), 'p3' => $link], 'mail');

                    $portal = $room->getPortal();

                    $this->mailer->sendMultipleRaw(
                        $this->translator->trans('mail.workflow.resubmission_subject', ['p1' => $portal->getTitle()], 'mail'),
                        $body,
                        $recipients,
                        $portal->getTitle()
                    );

                    // change material status
                    $materialManager->setWorkflowStatus($material->getItemID(),
                        $material->getWorkflowResubmissionTrafficLight(), $material->getVersionID());
                }
            }
        }

        $validityItems = $materialManager->getValidityItemIDsByDate(date('Y'), date('m'), date('d'));
        foreach ($validityItems as $validityItemInfo) {
            $material = $materialManager->getItem($validityItemInfo['item_id']);
            $latestMaterialVersionId = $materialManager->getLatestVersionID($validityItemInfo['item_id']);

            if (isset($material) && !$material->isDeleted() && ($validityItemInfo['item_id'] == $latestMaterialVersionId)) {
                /** @var Room $room */
                $room = $this->roomRepository->find($material->getContextId());

                if ($material->getWorkflowValidity() && $material->withWorkflowValidity()) {
                    $emailReceivers = [];

                    if ('creator' == $material->getWorkflowValidityWho()) {
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
                        $to = array_merge($to, explode(',', (string) $additionalReceiver));
                    }

                    $to = array_unique($to);
                    $recipients = [];
                    foreach ($to as $mail) {
                        $recipients[] = RecipientFactory::createFromRaw($mail);
                    }

                    $path = $this->router->generate('app_material_detail', [
                        'roomId' => $room->getItemID(),
                        'itemId' => $material->getItemID(),
                        'versionId' => $material->getVersionID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $link = '<a href="'.$path.'">'.$material->getTitle().'</a>';

                    $body = $this->translator->trans('mail.workflow.validity_body', ['p1' => $room->getTitle(), 'p2' => $material->getTitle(), 'p3' => $link], 'mail');

                    $portal = $room->getPortal();

                    $this->mailer->sendMultipleRaw(
                        $this->translator->trans('mail.workflow.validity_subject', ['p1' => $portal->getTitle()], 'mail'),
                        $body,
                        $recipients,
                        $portal->getTitle()
                    );

                    // change material status
                    $materialManager->setWorkflowStatus($material->getItemID(),
                        $material->getWorkflowValidityTrafficLight(), $material->getVersionID());
                }
            }
        }
    }

    public function getSummary(): string
    {
        return 'Material workflow progression';
    }
}
