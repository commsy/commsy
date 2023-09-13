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

namespace App\Form\DataTransformer;

use App\Entity\Room;
use App\Entity\RoomSlug;
use App\Repository\RoomRepository;
use Doctrine\Common\Collections\Collection;
use LogicException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class RoomSlugCollectionToStringTransformer implements DataTransformerInterface
{
    private int $roomId;

    public function __construct(
        private RoomRepository $roomRepository
    ) {
    }

    public function setRoomId(int $roomId)
    {
        $this->roomId = $roomId;
    }

    /**
     * Transforms a Collection of RoomSlug to a string
     *
     * @param Collection|null $slugs
     */
    public function transform($slugs): string
    {
        $slugTitles = array_map(fn (RoomSlug $slug) => $slug->getSlug(), iterator_to_array($slugs));

        return implode(',', $slugTitles);
    }

    /**
     * Transforms a string to a Collection of RoomSlug
     *
     * @param string $slugStr
     * @throws TransformationFailedException if object (RoomSlug) is not found.
     */
    public function reverseTransform($slugStr): ?Collection
    {
        if (!$this->roomId) {
            throw new LogicException('room id is not set');
        }

        /** @var Room $room */
        $room = $this->roomRepository->find($this->roomId);
        $roomSlugs = $room->getSlugs();

        $slugs = empty($slugStr) ? [] : explode(',', $slugStr);
        $formSlugs = array_map(function ($slug) use ($room) {
            $formSlug = new RoomSlug();
            $formSlug->setSlug($slug);
            $formSlug->setRoom($room);

            return $formSlug;
        }, $slugs);

        $deletes = array_diff($roomSlugs->toArray(), $formSlugs);
        $creates = array_diff($formSlugs, $roomSlugs->toArray());

        foreach ($deletes as $delete) {
            $roomSlugs->removeElement($delete);
        }

        foreach ($creates as $create) {
            $roomSlugs->add($create);
        }

        return $roomSlugs;
    }
}
