<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invitations
 *
 * @ORM\Table(name="invitations", indexes={@ORM\Index(name="invitation_id", columns={"invitation_id"})})
 * @ORM\Entity
 */
class Invitations
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", nullable=false)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=false)
     */
    private $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="authsource_id", type="integer", nullable=false)
     */
    private $authSourceId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration_date", type="datetime", nullable=true)
     */
    private $expirationDate = '0000-00-00 00:00:00';

    /**
     * @var boolean
     *
     * @ORM\Column(name="redeemed", type="boolean", nullable=false)
     */
    private $redeemed = '0';
}

