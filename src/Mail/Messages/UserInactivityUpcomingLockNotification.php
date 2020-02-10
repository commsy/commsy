<?php


namespace App\Mail\Messages;


use App\Mail\Message;

class UserInactivityUpcomingLockNotification extends Message
{
    /**
     * @var int
     */
    private $numDaysLeft;

    /**
     * @var string
     */
    private $portalTitle;

    /**
     * @var \cs_user_item
     */
    private $user;

    public function __construct(int $numDaysLeft, string $portalTitle, \cs_user_item $user)
    {
        $this->numDaysLeft = $numDaysLeft;
        $this->portalTitle = $portalTitle;
        $this->user = $user;
    }

    public function getSubject(): string
    {
        return '%portal_name%: Your account will be locked in %num_days% days';
    }

    public function getTemplateName(): string
    {
        return 'mail/user_inactivity_upcoming_lock_notification.html.twig';
    }

    public function getParameters(): array
    {
        return [

        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            '%portal_name%' => $this->portalTitle,
            '%num_days%' => $this->numDaysLeft,
        ];
    }
}