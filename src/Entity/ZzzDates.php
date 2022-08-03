<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * ZzzDates
 *
 * @ORM\Table(name="zzz_dates", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="creator_id", columns={"creator_id"})})
 * @ORM\Entity
 */
class ZzzDates
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $itemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=true)
     */
    private $contextId;

    /**
     * @var integer
     *
     * @ORM\Column(name="creator_id", type="integer", nullable=false)
     */
    private $creatorId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="modifier_id", type="integer", nullable=true)
     */
    private $modifierId;

    /**
     * @var integer
     *
     * @ORM\Column(name="deleter_id", type="integer", nullable=true)
     */
    private $deleterId;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=false)
     */
    private $modificationDate = '0000-00-00 00:00:00';

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="activation_date", type="datetime")
     */
    private ?DateTime $activationDate;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=16777215, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="start_time", type="string", length=100, nullable=true)
     */
    private $startTime;

    /**
     * @var string
     *
     * @ORM\Column(name="end_time", type="string", length=100, nullable=true)
     */
    private $endTime;

    /**
     * @var string
     *
     * @ORM\Column(name="start_day", type="string", length=100, nullable=false)
     */
    private $startDay;

    /**
     * @var string
     *
     * @ORM\Column(name="end_day", type="string", length=100, nullable=true)
     */
    private $endDay;

    /**
     * @var string
     *
     * @ORM\Column(name="place", type="string", length=100, nullable=true)
     */
    private $place;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="datetime_start", type="datetime", nullable=false)
     */
    private $datetimeStart = '0000-00-00 00:00:00';

    /**
     * @var DateTime
     *
     * @ORM\Column(name="datetime_end", type="datetime", nullable=false)
     */
    private $datetimeEnd = '0000-00-00 00:00:00';

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=false)
     */
    private $public = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="date_mode", type="boolean", nullable=false)
     */
    private $dateMode = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="text", length=65535, nullable=true)
     */
    private $extras;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=255, nullable=true)
     */
    private $color;

    /**
     * @var integer
     *
     * @ORM\Column(name="recurrence_id", type="integer", nullable=true)
     */
    private $recurrenceId;

    /**
     * @var string
     *
     * @ORM\Column(name="recurrence_pattern", type="text", length=65535, nullable=true)
     */
    private $recurrencePattern;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="locking_date", type="datetime", nullable=true)
     */
    private $lockingDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="locking_user_id", type="integer", nullable=true)
     */
    private $lockingUserId;


}

