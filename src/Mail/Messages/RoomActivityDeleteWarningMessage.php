<?php

namespace App\Mail\Messages;

use App\Entity\Portal;
use App\Mail\Message;
use App\Services\LegacyEnvironment;
use cs_environment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RoomActivityDeleteWarningMessage extends Message
{
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var Portal
     */
    private Portal $portal;

    /**
     * @var object
     */
    private object $room;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        LegacyEnvironment $legacyEnvironment,
        Portal $portal,
        object $room

    ) {
        $this->urlGenerator = $urlGenerator;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->portal = $portal;
        $this->room = $room;
    }

    public function getSubject(): string
    {
        return '%portal_name%: Workspace will be deleted in %num_days% days';
    }

    public function getTemplateName(): string
    {
        return 'mail/account_room_lock_warning.html.twig';
    }

    public function getParameters(): array
    {
        return [
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            '%portal_name%' => $this->portal->getTitle(),
            '%num_days%' => $this->portal->getClearInactiveRoomsDeleteDays(),
        ];
    }
}

//$save_language = $translator->getSelectedLanguage();
//$translator->setSelectedLanguage($language);
//
//if ($this->isCommunityRoom()) {
//    $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_DELETE_INFO', $this->getTitle(), $current_portal->getDaysSendMailBeforeDeletingRooms(), ($current_portal->getDaysUnusedBeforeDeletingRooms() - $current_portal->getDaysSendMailBeforeDeletingRooms()));
//} else {
//    $body .= $translator->getEmailMessage('PROJECT_MAIL_BODY_DELETE_INFO', $this->getTitle(), $current_portal->getDaysSendMailBeforeDeletingRooms(), ($current_portal->getDaysUnusedBeforeDeletingRooms() - $current_portal->getDaysSendMailBeforeDeletingRooms()));
//}
//$room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_DELETE_INFO');
//
//$body .= LF . LF;
//$body .= $translator->getMessage('PROJECT_MAIL_BODY_INFORMATION', str_ireplace('&amp;', '&', $this->getTitle()), $current_user->getFullname(), $room_change_action);
//
//// set new commsy url
//global $symfonyContainer;
//
///** @var \Symfony\Component\Routing\RouterInterface $router */
//$router = $symfonyContainer->get('router');
//$url = $router->generate('app_room_home', [
//    'roomId' => $this->getItemID(),
//], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
//
//$body .= LF . $url;
//
//if ($this->isProjectRoom()) {
//    $community_name_array = array();
//    $community_list = $this->getCommunityList();
//    if ($community_list->isNotEmpty()) {
//        $community_item = $community_list->getFirst();
//        while ($community_item) {
//            $community_name_array[] = $community_item->getTitle();
//            unset($community_item);
//            $community_item = $community_list->getNext();
//        }
//    }
//    unset($community_list);
//    if (!empty($community_name_array)) {
//        $body .= LF . LF;
//        $body .= $translator->getMessage('PROJECT_MAIL_BODY_COMMUNITIY_ROOMS') . LF;
//        $body .= implode(LF, $community_name_array);
//    }
//}
//
//$body .= LF . LF;
//$body .= $translator->getMessage('MAIL_SEND_TO', implode(LF, $moderator_name_array));
//$body .= LF . LF;
//if ($this->isCommunityRoom()) {
//    $body .= $translator->getMessage('MAIL_SEND_WHY_COMMUNITY', $this->getTitle());
//} else {
//    $body .= $translator->getMessage('MAIL_SEND_WHY_PROJECT', $this->getTitle());
//}