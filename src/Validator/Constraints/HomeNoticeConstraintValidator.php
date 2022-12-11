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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class HomeNoticeConstraintValidator extends ConstraintValidator
{
    public function __construct(private ContainerInterface $container, private TranslatorInterface $translator, private \App\Utils\ItemService $itemService)
    {
    }

    public function validate($homeNoticeItemId, Constraint $constraint)
    {
        if ($homeNoticeItemId) {
            if (is_numeric($homeNoticeItemId)) {
                $itemService = $this->itemService;

                $item = $itemService->getTypedItem($homeNoticeItemId);
                if ($item) {
                    $validType = true;

                    $itemType = $item->getItemType();
                    if (!in_array($itemType, [CS_ANNOUNCEMENT_TYPE, CS_DATE_TYPE, CS_MATERIAL_TYPE, CS_TODO_TYPE])) {
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
