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

namespace App\Entity;

use App\Repository\RoomSlugRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RoomSlugRepository::class)]
#[UniqueEntity('slug', message: 'A workspace with the same workspace identifier already exists.')]
class RoomSlug
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true, nullable: false)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Your workspace identifier must not exceed {{ limit }} characters.'
    )]
    #[Assert\Regex(
        // unreserved URI chars only: any alphanumeric chars plus: ~._-
        pattern: '/^[[:alnum:]~._-]+$/',
        message: 'Your workspace identifier may only contain lowercase English letters, digits or any of these special characters: -._~'
    )]
    private string $slug;

    #[ORM\ManyToOne(inversedBy: 'slugs')]
    #[ORM\JoinColumn(referencedColumnName: 'item_id', nullable: false)]
    private Room $room;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = strtolower($slug);

        return $this;
    }

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): self
    {
        $this->room = $room;

        return $this;
    }

    public function __toString(): string
    {
        return $this->slug;
    }
}
