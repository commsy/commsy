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

namespace App\Validator\Constraints;

use App\Form\Model\Send;
use App\Services\LegacyEnvironment;
use cs_environment;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SendRecipientsConstraintValidator extends ConstraintValidator
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function validate($additional_recipients, Constraint $constraint): void
    {
        $values = $this->context->getRoot()->getData();
        $foundRecipient = false;

        if (Send::class == $values::class) {
            if (sizeof($values->getAdditionalRecipients()) > 0 && isset($values->getAdditionalRecipients()[0])) {
                $foundRecipient = true;
            }

            $isSendToGroupAll = $values->getSendToGroupAll();

            if (!isset($isSendToGroupAll)) {
                $isSendToGroupAll = true;
            }

            if (sizeof($values->getSendToGroups()) > 0 && isset($values->getSendToGroups()[0]) && $isSendToGroupAll) {
                $foundRecipient = true;
            }
            if ($values->getSendToGroupAll()) {
                $foundRecipient = true;
            }
            if ($values->getSendToAll()) {
                $foundRecipient = true;
            }
            if ($values->getSendToCreator()) {
                $foundRecipient = true;
            }
            if ($values->getCopyToSender()) {
                $foundRecipient = true;
            }
            if (!is_null($values->getSendToAttendees())) {
                $foundRecipient = true;
            }
        } else {
            if (isset($values['additional_recipients'][0])) {
                $foundRecipient = true;
            }
            if (isset($values['send_to_groups'][0])) {
                $foundRecipient = true;
            }
            if ($values['send_to_group_all']) {
                $foundRecipient = true;
            }
            if ($values['send_to_all']) {
                $foundRecipient = true;
            }
            if ($values['send_to_creator']) {
                $foundRecipient = true;
            }
            if ($values['copy_to_sender']) {
                $foundRecipient = true;
            }
            if (isset($values['send_to_attendees'])) {
                if ($values['send_to_attendees']) {
                    $foundRecipient = true;
                }
            }
        }

        if (!$foundRecipient) {
            $this->context->buildViolation($constraint->message)->setParameter('parameter', 'value')->addViolation();
        }
    }
}
