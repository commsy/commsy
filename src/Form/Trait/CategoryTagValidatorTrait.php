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

namespace App\Form\Trait;

use App\Utils\ItemService;
use cs_context_item;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait CategoryTagValidatorTrait
{
    private ItemService $itemService;

    #[Required]
    public function setItemService(ItemService $itemService): void
    {
        $this->itemService = $itemService;
    }

    private function validate(array $data, ExecutionContextInterface $context): void
    {
        /** @var Form $form */
        $form = $context->getObject();

        /** @var cs_context_item $room */
        $room = $form->getConfig()->getOption('room');
        $itemId = $form->getConfig()->getOption('itemId');
        $item = $this->itemService->getItem($itemId);

        if (!$item?->isDraft()) {
            return;
        }

        if ($room->withBuzzwords() && $room->isBuzzwordMandatory() && $item->getBuzzwordList()->isEmpty()) {
            $context->buildViolation('Please select at least one hashtag')
                ->atPath('hashtags')
                ->addViolation();
        }

        if ($room->withTags() && $room->isTagMandatory() && $item->getTagList()->isEmpty()) {
            $context->buildViolation('Please select at least one category')
                ->atPath('categories')
                ->addViolation();
        }
    }
}
