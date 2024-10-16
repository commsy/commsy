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

use App\Account\AccountManager;
use App\Entity\Portal;
use App\Mail\Factories\NewsletterMessageFactory;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Newsletter\NewsletterGenerator;
use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_privateroom_item;
use DateTimeImmutable;

class CronNewsletter implements CronTaskInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly PortalRepository $portalRepository,
        private readonly AccountManager $accountManager,
        private readonly Mailer $mailer,
        private readonly NewsletterMessageFactory $newsletterMessageFactory,
        private readonly NewsletterGenerator $newsletterGenerator
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $portals = $this->portalRepository->findAll();
        foreach ($portals as $portal) {
            $this->legacyEnvironment->setCurrentContextID($portal->getId());

            $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
            $privateRoomManager->reset();
            $privateRoomManager->setContextLimit($portal->getId());
            $privateRoomManager->setActiveLimit();
            $privateRoomManager->select();
            $privateRooms = $privateRoomManager->get();

            foreach ($privateRooms as $privateRoom) {
                /** @var cs_privateroom_item $privateRoom */
                if (!$privateRoom->isOpen() || !$privateRoom->isPrivateRoomNewsletterActive()) {
                    continue;
                }

                $frequency = $privateRoom->getPrivateRoomNewsletterActivity();
                $send = 'daily' === $frequency;

                if ('weekly' === $frequency) {
                    // send weekly newsletter on monday
                    $send = 1 == date('N');
                }

                if ($send) {
                    $this->sendNewsletter($portal, $privateRoom);
                }
            }
        }
    }

    public function getSummary(): string
    {
        return 'Send newsletter';
    }

    /**
     * Prepare and send the newsletters. They describe the activity during the last day or week,
     * depending on the user's frequency setting.
     */
    private function sendNewsletter(Portal $portal, cs_privateroom_item $privateRoom)
    {
        // prepare newsletter data
        $newsletterData = $this->newsletterGenerator->getNewsletterData($privateRoom);

        // generate newsletter message
        $message = $this->newsletterMessageFactory->createNewsletterMessage(
            $portal,
            $newsletterData
        );

        $user = $privateRoom->getOwnerUserItem();
        if (!$user) {
            return;
        }

        $account = $this->accountManager->getAccount($user, $portal->getId());
        if (!$account) {
            return;
        }

        $recipient = RecipientFactory::createFromAccount($account);

        // send newsletter email
        $this->mailer->send($message, $recipient);
    }
}
