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

use App\Rubric\RubricType;
use App\Utils\ItemService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class HomeNoticeConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ItemService $itemService
    ) {}

    public function validate($homeNoticeItemId, Constraint $constraint): void
    {
        if ($homeNoticeItemId) {
            if (is_numeric($homeNoticeItemId)) {
                $itemService = $this->itemService;

                $item = $itemService->getTypedItem($homeNoticeItemId);
                if ($item) {
                    $validType = true;

                    $itemType = $item->getItemType();
                    if (!in_array($itemType, [RubricType::Announcement->value, RubricType::Date->value, RubricType::Material->value, RubricType::Todo->value])) {
                        $validType = false;
                    }

                    if (!$validType) {
                        $this->context->buildViolation($constraint->message)->setParameter('parameter', 'value')->setParameter('{{ type }}', $this->translator->trans($itemType, [], 'form'))->addViolation();
                    }
                } else {
                    $this->context->buildViolation($constraint->messageNoItem)->setParameter('parameter', 'value')->addViolation();
                }
            } else {
                $this->context->buildViolation($constraint->messageInvalidId)->setParameter('parameter', 'value')->addViolation();
            }
        }
    }
}
