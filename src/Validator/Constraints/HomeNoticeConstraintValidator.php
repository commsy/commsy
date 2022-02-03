<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class HomeNoticeConstraintValidator extends ConstraintValidator
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    public function __construct(ContainerInterface $container, TranslatorInterface $translator)
    {
        $this->container = $container;
        $this->translator = $translator;
    }

    public function validate($homeNoticeItemId, Constraint $constraint)
    {
        if ($homeNoticeItemId) {
            if (is_numeric($homeNoticeItemId)) {
                $itemService = $this->container->get('commsy_legacy.item_service');

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