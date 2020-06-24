<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\AccountIndex;
use App\Entity\AccountIndexUser;
use App\Entity\Portal;
use App\Entity\PortalUserAssignWorkspace;
use App\Entity\PortalUserChangeStatus;
use App\Entity\PortalUserEdit;
use App\Entity\Room;
use App\Entity\RoomCategories;
use App\Entity\Translation;
use App\Event\CommsyEditEvent;
use App\Form\Type\Portal\AccountIndexDetailAssignWorkspaceType;
use App\Form\Type\Portal\AccountIndexDetailChangePasswordType;
use App\Form\Type\Portal\AccountIndexDetailChangeStatusType;
use App\Form\Type\Portal\AccountIndexDetailEditType;
use App\Form\Type\Portal\AccountIndexDetailType;
use App\Form\Type\Portal\AccountIndexType;
use App\Form\Type\Portal\AnnouncementsType;
use App\Form\Type\Portal\GeneralType;
use App\Form\Type\Portal\InactiveType;
use App\Form\Type\Portal\PortalhomeType;
use App\Form\Type\Portal\RoomCategoriesType;
use App\Form\Type\Portal\SupportType;
use App\Form\Type\Portal\TimeType;
use App\Form\Type\TranslationType;
use App\Services\LegacyEnvironment;
use App\Services\RoomCategoriesService;
use App\Utils\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;


class PortalSettingsController extends AbstractController
{
    /**
     * @Route("/portal/{portalId}/settings")
     */
    public function index(int $portalId)
    {
        return $this->redirectToRoute('app_portalsettings_general', [
            'portalId' => $portalId,
        ]);
    }

    /**
     * @Route("/portal/{portalId}/settings/general")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function general(Portal $portal, Request $request)
    {
        $form = $this->createForm(GeneralType::class, $portal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/support")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function support(Portal $portal, Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(SupportType::class, $portal);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($portal);
            $entityManager->flush();
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/portalhome")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @ParamConverter("environment", class="App\Services\LegacyEnvironment")
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function portalhome(Portal $portal, Request $request, EntityManagerInterface $entityManager, LegacyEnvironment $environment)
    {
        $form = $this->createForm(PortalhomeType::class, $portal);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $legacyEnvironment = $environment->getEnvironment();
            $room_item = $legacyEnvironment->getCurrentPortalItem();
            $choice = $portal->getConfigurationSelection();
            if($choice == 1){
                $room_item->setShowRoomsOnHome('preselectcommunityrooms');
            }elseif($choice == 2){
                $room_item->setShowRoomsOnHome('onlycommunityrooms');
            }elseif($choice == 3){
                $room_item->setShowRoomsOnHome('onlyprojectrooms');
            }else{
                $room_item->setShowRoomsOnHome('normal');
            }

            $chosen_templates = $portal->hasConfigurationRoomListTemplates();
            if($chosen_templates){
                $room_item->setShowTemplatesInRoomListON();
            }else{
                $room_item->setShowTemplatesInRoomListOFF();
            }

            $entityManager->persist($portal);
            $entityManager->flush();
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/roomcategories/{roomCategoryId?}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     * @param Portal $portal
     * @param int $roomCategoryId
     * @param Request $request
     * @param RoomCategoriesService $roomCategoriesService
     * @param EventDispatcherInterface $dispatcher
     * @param EntityManagerInterface $entityManager
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function roomcategories(
        Portal $portal,
        $roomCategoryId,
        Request $request,
        RoomCategoriesService $roomCategoriesService,
        EventDispatcherInterface $dispatcher,
        EntityManagerInterface $entityManager
    ) {
        $editForm = null;
        $portalId = $portal->getId();
        $repository = $entityManager->getRepository(RoomCategories::class);

        if ($roomCategoryId) {
            $roomCategory = $repository->find($roomCategoryId);
        } else {
            $roomCategory = new RoomCategories();
            $roomCategory->setContextId($portalId);
        }

        $editForm = $this->createForm(RoomCategoriesType::class, $roomCategory);

        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $clickedButtonName = $editForm->getClickedButton()->getName();

            if ($clickedButtonName === 'new' || $clickedButtonName === 'update') {
                $entityManager->persist($roomCategory);
            } else if ($clickedButtonName === 'delete') {
                $roomCategoriesService->removeRoomCategory($roomCategory);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_portalsettings_roomcategories', [
                'portalId' => $portal->getId(),
            ]);
        }

        $roomCategories = $repository->findBy([
            'context_id' => $portalId,
        ]);

        $dispatcher->dispatch(new CommsyEditEvent(null), CommsyEditEvent::EDIT);

// TODO: add mandatory links form

        return [
            'editForm' => $editForm ? $editForm->createView() : null,
            'portal' => $portal,
            'roomCategoryId' => $roomCategoryId,
            'roomCategories' => $roomCategories,
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/inactive")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @ParamConverter("environment", class="App\Services\LegacyEnvironment")
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function inactive(Portal $portal, Request $request,
                             EntityManagerInterface $entityManager, LegacyEnvironment $environment)
    {
        $form = $this->createForm(InactiveType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
//            // Get the current user
//            $current_user = $environment->getCurrentUserItem();
//            $translator = $environment->getTranslationObject();
//            $current_context = $environment->getCurrentContextItem();
//
//            if (!$current_user->isModerator()
//                or !$current_context->mayEdit($current_user)
//                or !$current_context->isPortal()
//                or $current_user->isGuest()
//            ) {
//                $params = array();
//                $params['environment'] = $environment;
//                $params['with_modifying_actions'] = true;
//                $errorbox = $class_factory->getClass(ERRORBOX_VIEW, $params);
//                unset($params);
//                $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
//                $page->addWarning($errorbox);
//            } else {
//                //access granted
//
//                // Find out what to do
//                if (isset($_POST['option'])) {
//                    $command = $_POST['option'];
//                } else {
//                    $command = '';
//                }
//
//                // Cancel editing
//                if (isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON'))) {
//                    redirect($environment->getCurrentContextID(), 'configuration', 'index', array());
//                } else {
//                    // Show form and/or save item
//
//
//                    // Initialize the form
//                    $form = $class_factory->getClass(CONFIGURATION_INACTIVE_FORM, array('environment' => $environment));
//                    $params = array();
//                    $params['environment'] = $environment;
//                    $params['with_modifying_actions'] = true;
//                    $form_view = $class_factory->getClass(CONFIGURATION_DATASECURITY_FORM_VIEW, $params);
//                    unset($params);
//
//                    // Load form data from postvars
//                    if (!empty($_POST)) {
//                        $values = $_POST;
//                        $form->setFormPost($values);
//                    } else {
//                        $form->setItem($current_context);
//                    }
//
//                    $form->prepareForm();
//                    $form->loadValues();
//
//                    if (!empty($command)
//                        and ( isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) )
//                    ) {
//                        if ($form->check()) {
//                            if (isset($_POST['overwrite_content'])) {
//                                $current_context->setInactivityOverwriteContent($_POST['overwrite_content']);
//                            }
//
//                            if (!empty($_POST['lock_user'])) {
//                                $current_context->setInactivityLockDays($_POST['lock_user']);
//                            } else {
//                                $current_context->setInactivityLockDays('');
//                            }
//
//                            if (!empty($_POST['email_before_lock'])) {
//                                $current_context->setInactivitySendMailBeforeLockDays($_POST['email_before_lock']);
//                            } else {
//                                $current_context->setInactivitySendMailBeforeLockDays('');
//                            }
//
//                            if (!empty($_POST['delete_user'])) {
//                                $current_context->setInactivityDeleteDays($_POST['delete_user']);
//                            } else {
//                                $current_context->setInactivityDeleteDays('');
//                            }
//
//                            if (!empty($_POST['email_before_delete'])) {
//                                $current_context->setInactivitySendMailBeforeDeleteDays($_POST['email_before_delete']);
//                            } else {
//                                $current_context->setInactivitySendMailBeforeDeleteDays('');
//                            }
//                            // save configuration
//                            $current_context->save();
//
//                            if (empty($_POST['delete_user']) and empty($_POST['lock_user'])) {
//                                $params = array();
//                                $params['environment'] = $environment;
//                                $params['with_modifying_actions'] = true;
//                                $errorbox = $class_factory->getClass(ERRORBOX_VIEW, $params);
//                                $errorbox->setText($translator->getMessage('CONFIGURATION_INACTIVITY_ALERT_CONFIG'));
//                                $page->add($errorbox);
//                            } else {
//                                // set config date
//                                $current_context->setInactivityConfigDate();
//
//                                // save room_item
//                                $current_context->save();
//                                $form_view->setItemIsSaved();
//
//                                // warning of locked and deleted user
//                                $lock_days          = $_POST['lock_user'];
//                                $mail_before_lock   = $_POST['email_before_lock'];
//                                $delete_days        = $_POST['delete_user'];
//                                $mail_before_delete = $_POST['email_before_delete'];
//
//                                $user_manager = $environment->getUserManager();
//                                if (isset($lock_days) and !empty($lock_days)) {
//                                    if (isset($mail_before_lock) and !empty($mail_before_lock)) {
//                                        $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL(($lock_days + $mail_before_lock));
//                                    } else {
//                                        $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($lock_days);
//                                    }
//
//                                }
//                                if (isset($delete_days) and !empty($delete_days)) {
//                                    if (isset($mail_before_delete) and !empty($mail_before_delete)) {
//                                        $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($delete_days + $mail_before_delete);
//                                    } else {
//                                        $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($delete_days);
//                                    }
//                                }
//                                if (isset($date_lastlogin_do)) {
//                                    $user_array = $user_manager->getUserLastLoginLaterAs($date_lastlogin_do, $current_context->getItemID());
//                                }
//
//                                if (!empty($user_array)) {
//                                    $count_delete = 0;
//                                    $count_lock = 0;
//                                    foreach ($user_array as $user) {
//                                        $start_date = new DateTime(getCurrentDateTimeInMySQL());
//                                        $since_start = $start_date->diff(new DateTime($user->getLastLogin()));
//                                        $days = $since_start->days;
//                                        if ($days == 0) {
//                                            $days = 1;
//                                        }
//                                        if(!empty($delete_days) AND empty($lock_days)) {
//                                            if ($days >= $delete_days-1 and !empty($delete_days)) {
//                                                $count_delete++;
//                                                continue;
//                                            }
//                                        }
//                                        if ($days >= $lock_days-1 and !empty($lock_days)) {
//                                            $count_lock++;
//                                            continue;
//                                        }
//                                    }
//                                }
//                                if (isset($count_delete) or isset($count_lock)) {
//                                    if ($count_delete != 0 or $count_lock != 0) {
//                                        $html = '';
//                                        if ($count_delete > 0) {
//                                            $html .= $count_delete.' '.$translator->getMessage('CONFIGURATION_INACTIVITY_ALERT_DELETE', $delete_days);
//                                        }
//                                        if ($count_lock > 0) {
//                                            $html .= $count_lock.' '.$translator->getMessage('CONFIGURATION_INACTIVITY_ALERT_LOCK', $lock_days);
//                                        }
//                                        #$html .= $translator->getMessage('CONFIGURATION_INACTIVITY_ALERT_INFO');
//
//                                        $params = array();
//                                        $params['environment'] = $environment;
//                                        $params['with_modifying_actions'] = true;
//                                        $errorbox = $class_factory->getClass(ERRORBOX_VIEW, $params);
//                                        $errorbox->setText($html);
//                                        $page->add($errorbox);
//                                    }
//                                }
//                            }
//                        }
//                    }
//
//                    // display form
//                    if (isset($current_context) and !$current_context->mayEditRegular($current_user)) {
//                        $form_view->warnChanger();
//                        $params = array();
//                        $params['environment'] = $environment;
//                        $params['with_modifying_actions'] = true;
//                        $params['width'] = 500;
//                        $errorbox = $class_factory->getClass(ERRORBOX_VIEW, $params);
//                        unset($params);
//                        $errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
//                        $page->addWarning($errorbox);
//                    }
//
//                    include_once('functions/curl_functions.php');
//                    $form_view->setAction(curl($environment->getCurrentContextID(), $environment->getCurrentModule(), $environment->getCurrentFunction(), ''));
//                    $form_view->setForm($form);
//                    $page->addForm($form_view);
//                }
//            }
        }
        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/time")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @ParamConverter("environment", class="App\Services\LegacyEnvironment")
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function time(Portal $portal, Request $request,
                         EntityManagerInterface $entityManager, LegacyEnvironment $environment)
    {
        $defaultData = ['showTime' => 0];
        $form = $this->createForm(TimeType::class, $defaultData);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked())) {

            // get room item and current user
            $room_item = $portal;
            $data = $form->getData();

                // Load form data from postvars
                if ( !empty($data) ) {
                    $values = $data;

                    // show time
                    $room_item->setShowTime($values['showTime']);


                    // clock pulse names
                    $clockPulseNames = array();
                    array_push($clockPulseNames, $values['timeCycleNameGerman']);
                    array_push($clockPulseNames, $values['timeCycleNameEnglish']);
                    $room_item->setTimeNameArray($clockPulseNames);

                    $room_item->setTimeInFuture($values['futureTimeCycles']);


                    $clockPulseTimeNames = explode(',',$values['token']);
                    $clockPulseTimeTextArray = array();
                    foreach($clockPulseTimeNames as $clockPulseCounter){
                        $clockTextNameDeu = $values['timeCycleNameEnglish_'.$clockPulseCounter];
                        $clockTextNameEng = $values['timeCycleNameGerman_'.$clockPulseCounter];
                        array_push($clockPulseTimeTextArray, $clockTextNameDeu);
                        array_push($clockPulseTimeTextArray, $clockTextNameEng);
                    }
                    $room_item->setTimeTextArray($clockPulseTimeTextArray);

                    // save room_item
                    $entityManager->persist($room_item); // portalProxy
                    $entityManager->flush();

                    // change (insert) time labels
                    $clock_pulse_array = array();

                        $current_year = date('Y');
                        $current_date = getCurrentDate();
                        $ad_year = 0;
                        $first = true;
                        foreach($clockPulseTimeNames as $clockPulseCounter){
                            $date_string = $values['timeCycleFrom_'.$clockPulseCounter];
                            $month = $date_string->format("m");
                            $day = $date_string->format("d");
                            $begin = $month.$day;

                            $date_string = $values['timeCycleTo_'.$clockPulseCounter];
                            $month = $date_string->format("m");
                            $day = $date_string->format("d");
                            $end = $month.$day;

                            $begin2 = ($current_year+$ad_year).$begin;
                            if ($end < $begin) {
                                $ad_year++;
                                $ad_year_pos = $clockPulseCounter;
                            }
                            $end2 = ($current_year+$ad_year).$end;

                            if ($first) {
                                $first = false;
                                $begin_first = $begin2;
                            }

                            if ( $begin2 <= $current_date
                                and $current_date <= $end2) {
                                $current_pos = $clockPulseCounter;
                            }
                        }

                        $year = $current_year;

                        if ($current_date < $begin_first) {
                            $year--;
                            $current_pos = $clockPulseCounter;
                        }

                        $count = sizeof($clockPulseTimeNames);
                        $position = 1;
                        for ($i=0; $i<$values['futureTimeCycles']+$current_pos; $i++) {
                            $clock_pulse_array[] = $year.'_'.$position;
                            $position++;
                            if ($position > $count) {
                                $position = 1;
                                $year++;
                            }
                        }


                    if (!empty($clock_pulse_array)) {
                        $done_array = array();
                        $time_manager = $environment->getEnvironment()->getTimeManager();
                        $time_manager->reset();
                        $time_manager->setContextLimit($portal->getId());
                        $time_manager->setDeleteLimit(false);
                        $time_manager->select();
                        $time_list = $time_manager->get();
                        if ($time_list->isNotEmpty()) {
                            $time_label = $time_list->getFirst();
                            while ($time_label) {
                                if (!in_array($time_label->getTitle(),$clock_pulse_array)) {
                                    $first_new_clock_pulse = $clock_pulse_array[0];
                                    $last_new_clock_pulse = array_pop($clock_pulse_array);
                                    $clock_pulse_array[] = $last_new_clock_pulse;
                                    if ($time_label->getTitle() < $first_new_clock_pulse) {
                                        $temp_clock_pulse_array = explode('_',$time_label->getTitle());
                                        $clock_pulse_pos = $temp_clock_pulse_array[1];
                                        if ($clock_pulse_pos > $count) {
                                            if (!$time_label->isDeleted()) {
                                                $time_label->setDeleterItem($environment->getCurrentUserItem());
                                                $time_label->delete();
                                            }
                                        } else {
                                            if ($time_label->isDeleted()) {
                                                $time_label->setModificatorItem($environment->getCurrentUserItem());
                                                $time_label->unDelete();
                                            }
                                        }
                                    } elseif ($time_label->getTitle() > $last_new_clock_pulse) {
                                        if (!$time_label->isDeleted()) {
                                            $time_label->setDeleterItem($environment->getCurrentUserItem());
                                            $time_label->delete();
                                        }
                                    } else {
                                        if (!$time_label->isDeleted()) {
                                            $time_label->setDeleterItem($environment->getCurrentUserItem());
                                            $time_label->delete();
                                        }
                                    }
                                } else {
                                    if ($time_label->isDeleted()) {
                                        $time_label->setModificatorItem($environment->getCurrentUserItem());
                                        $time_label->unDelete();
                                    }
                                    $done_array[] = $time_label->getTitle();
                                }
                                $time_label = $time_list->getNext();
                            }
                        }

                        foreach ($clock_pulse_array as $clock_pulse) {
                            if (!in_array($clock_pulse,$done_array)) {
                                $time_label = $time_manager->getNewItem();
                                $time_label->setContextID($portal->getId());
                                $user = $environment->getEnvironment()->getCurrentUserItem();
                                $time_label->setCreatorItem($user);
                                $time_label->setModificatorItem($user);
                                $time_label->setTitle($clock_pulse);
                                $time_label->save();
                            }
                        }
                    } else {
                        $time_manager = $environment->getEnvironment()->getTimeManager();
                        $time_manager->reset();
                        $time_manager->setContextLimit($portal->getId());
                        $time_manager->select();
                        $time_list = $time_manager->get();
                        if ($time_list->isNotEmpty()) {
                            $time_label = $time_list->getFirst();
                            while ($time_label) {
                                $time_label->setDeleterItem($environment->getEnvironment()->getCurrentUserItem());
                                $time_label->delete();
                                $time_label = $time_list->getNext();
                            }
                        }
                    }

                    // renew links to continuous rooms
                    $current_context = $room_item;
                    $room_list = $current_context->getContinuousRoomList($environment);
                    if ($room_list->isNotEmpty()) {
                        $room_item2 = $room_list->getFirst();
                        while ($room_item2) {
                            $room_item2->open();
                            if ($room_item2->isOpen()) {
                                $room_item2->setContinuous();
                                $room_item2->saveWithoutChangingModificationInformation($environment);
                            }
                            $room_item2 = $room_list->getNext();
                        }
                    }
                $is_saved = true;
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/announcements")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function announcements(Portal $portal, Request $request, EntityManagerInterface $entityManager)
    {
            $form = $this->createForm(AnnouncementsType::class, $portal);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($portal);
                $entityManager->flush();
            }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/accountindex")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accountIndex(Portal $portal, Request $request, LegacyEnvironment $environment, UserService $userService)
    {
        $userList = $userService->getListUsers($portal->getId());
        $accountIndex = new AccountIndex();

        $accountIndexUserList = [];

        foreach($userList as $singleUser) {
            $singleAccountIndexUser = new AccountIndexUser();
            $singleAccountIndexUser->setName($singleUser->getFullName());
            $singleAccountIndexUser->setChecked(false);
            $singleAccountIndexUser->setItemId($singleUser->getItemID());
            $singleAccountIndexUser->setMail($singleUser->getEmail());
            $singleAccountIndexUser->setUserId($singleUser->getUserID());
            array_push($accountIndexUserList, $singleAccountIndexUser);
        }

        //TODO https://symfony.com/doc/current/reference/forms/types/collection.html

        $accountIndex->setAccountIndexUsers($accountIndexUserList);
        $form = $this->createForm(AccountIndexType::class, $accountIndex);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
        }
        return [
            'form' => $form->createView(),
            'userList' => $userList,
            'portal' => $portal,
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/accountindex/detail/{userId}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accountIndexDetail(Portal $portal, Request $request, UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
            $userList = $userService->getListUsers($portal->getId());

            $form = $this->createForm(AccountIndexDetailType::class, $portal);
            $form->handleRequest($request);
            $user = $userService->getUser($request->get('userId'));

            if ($form->isSubmitted() && $form->isValid()) {

                $key = 0;
                if ($form->get('next')->isClicked() or $form->get('previous')->isClicked()) {
                    $counter = 0;
                    foreach ($userList as $userItem) {
                        if ($userItem->getItemID() == $user->getItemID()) {
                            $key = $counter;
                            break;
                        }
                        $counter = $counter + 1;
                    }
                    if ($form->get('next')->isClicked()) {
                        if ($key < sizeof($userList)) {
                            $user = $userList[$key + 1];
                        }
                    }
                    if ($form->get('previous')->isClicked()) {
                        if ($key > 0) {
                            $user = $userList[$key - 1];
                        }
                    }
                    return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
                        'portal' => $portal,
                        'portalId' => $portal->getId(),
                        'userId' => $user->getItemID(),
                    ]);
                }


                if ($form->get('back')->isClicked()) {
                    return $this->redirectToRoute('app_portalsettings_accountindex', [
                        'portal' => $portal,
                        'portalId' => $portal->getId(),
                    ]);
                }
            }

            return [
                'user' => $user,
                'form' => $form->createView(),
                'portal' => $portal,
            ];
    }

    /**
     * @Route("/portal/{portalId}/settings/accountindex/detail/{userId}/edit")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accountIndexDetailEdit(Portal $portal, Request $request, UserService $userService, LegacyEnvironment $legacyEnvironment)
    {

        $environment = $legacyEnvironment->getEnvironment();

        $user = $userService->getUser($request->get('userId'));
        $userEdit = new PortalUserEdit();
        $userEdit->setFirstName($user->getFirstname());
        $userEdit->setLastName($user->getLastName());
        $userEdit->setAcademicDegree($user->getTitle());

        $userEdit->setBirthday($user->getBirthday());
        $userEdit->setStreet($user->getStreet());
        $userEdit->setZip($user->getZipcode());
        $userEdit->setCity($user->getCity());
        $userEdit->setWorkspace($user->getRoom());
        $userEdit->setTelephone($user->getTelephone());
        $userEdit->setSecondTelephone($user->getCellularphone());
        $userEdit->setEmail($user->getEmail());
        $userEdit->setICQ($user->getIcq());
        $userEdit->setMSN($user->getMsn());
        $userEdit->setSkype($user->getSkype());
        $userEdit->setYahoo($user->getYahoo());
        $userEdit->setHomepage($user->getHomepage());
        $userEdit->setDescription($user->getDescription());
        $userEdit->setMayCreateContext($user->getIsAllowedToCreateContext());
        $userEdit->setMayUseCaldav('standard');
        $userEdit->setPicture($user->getPicture());

        $form = $this->createForm(AccountIndexDetailEditType::class, $userEdit);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PortalUserEdit $editAccountIndex */
            $editAccountIndex = $form->getData();
            $user->setFirstname($editAccountIndex->getFirstName());
            $user->setLastname($editAccountIndex->getLastName());
            $user->setTitle($editAccountIndex->getAcademicDegree());
            $user->setBirthday($editAccountIndex->getBirthday());
            $user->setStreet($editAccountIndex->getStreet());
            $user->setZipcode($editAccountIndex->getZip());
            $user->setCity($editAccountIndex->getCity());
            $user->setRoom($editAccountIndex->getWorkspace());
            $user->setTelephone($editAccountIndex->getTelephone());
            $user->setCellularphone($editAccountIndex->getSecondTelephone());
            $user->setEmail($editAccountIndex->getEmail());

            if($editAccountIndex->getEmailChangeAll()){
                $relatedUsers = $user->getRelatedUserList();
                foreach($relatedUsers as $relatedUser){
                    $relatedUser->setEmail($editAccountIndex->getEmail());
                    $relatedUser->save();
                }
            }
            $user->setICQ($editAccountIndex->getIcq());
            $user->setMSN($editAccountIndex->getMsn());
            $user->setSkype($editAccountIndex->getSkype());
            $user->setYahoo($editAccountIndex->getYahoo());
            $user->setHomepage($editAccountIndex->getHomepage());
            $user->setDescription($editAccountIndex->getDescription());

            if(!empty($editAccountIndex->getPicture())){
                //TODO: Does this piece of code make sense, if we set a new picture anyway?
                if($editAccountIndex->isOverrideExistingPicture()){
                    $disc_manager = $environment->getDiscManager();
                    if ( $disc_manager->existsFile($user->getPicture()) ) {
                        $disc_manager->unlinkFile($user->getPicture());
                    }
                    $user->setPicture('');
                    if ( isset($portal_user_item) ) {
                        $portal_user_item->setPicture('');
                    }
                }

                $filename = 'cid'.$environment->getCurrentContextID().'_'.$user_item->getUserID().'_'.$_FILES['upload']['name'];
                $disc_manager = $environment->getDiscManager();
                $disc_manager->copyFile($_FILES['upload']['tmp_name'],$filename,true);
                $user_item->setPicture($filename);
                if ( isset($portal_user_item) ) {
                    if ( $disc_manager->copyImageFromRoomToRoom($filename,$portal_user_item->getContextID()) ) {
                        $value_array = explode('_',$filename);
                        $old_room_id = $value_array[0];
                        $old_room_id = str_replace('cid','',$old_room_id);
                        $value_array[0] = 'cid'.$portal_user_item->getContextID();
                        $new_picture_name = implode('_',$value_array);
                        $portal_user_item->setPicture($new_picture_name);
                    }
                }

                $user->setPicture($editAccountIndex->getPicture());
            }

            if($editAccountIndex->getMayCreateContext() == 'standard'){
                $user->setIsAllowedToCreateContext(true); //TODO how do we get the pre-set portal value?
            } elseif($editAccountIndex->getMayCreateContext() == '1'){
                $user->setIsAllowedToCreateContext(true);
                $user->getRelatedPortalUserItem()->setIsAllowedToCreateContext(true);
            }else{
                $user->setIsAllowedToCreateContext(false);
                $user->getRelatedPortalUserItem()->setIsAllowedToCreateContext(false);
            }

            //TODO: What is with caldav? $user does not posess a field for that

            $user->save();
        }

        return [
            'user' => $user,
            'form' => $form->createView(),
            'portal' => $portal,
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/accountIndex/detail/{userId}/changeStatus")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accountIndexDetailChangeStatus(Portal $portal, Request $request, UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $user = $userService->getUser($request->get('userId'));
        $userChangeStatus = new PortalUserChangeStatus();
        $userChangeStatus->setName($user->getFullName());
        $userChangeStatus->setUserID($user->getUserID());
        $userChangeStatus->setLastLogin($user->getLastLogin());

        $userStatus = $user->getStatus();
        $currentStatus = 'Moderator';
        if($userStatus == 0){
            $currentStatus = 'User';
        }elseif($userStatus == 0){
            $currentStatus = 'Contact';
        }

        $userChangeStatus->setCurrentStatus($currentStatus);
        $userChangeStatus->setNewStatus('user');
        $userChangeStatus->setContact($user->isContact());
        $userChangeStatus->setLoginIsDeactivated('2');

        $form = $this->createForm(AccountIndexDetailChangeStatusType::class, $userChangeStatus);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $user = $userService->getUser($request->get('userId'));

            /** @var PortalUserChangeStatus $data */
            $data = $form->getData();
            $newStatus = $data->getNewStatus();
            if(strcmp($newStatus, 'user') == 0){
                $user->makeUser();
            }elseif(strcmp($newStatus, 'moderator') == 0){
                $user->makeModerator();
            }elseif(strcmp($newStatus, 'closed') == 0) {
                $user->reject();
            }

            if($data->isContact()){
                $user->makeContactPerson();
            }

            $deactivateTakeOver = $data->getLoginIsDeactivated();
            if($deactivateTakeOver == '2'){
                $user->deactivateLoginAsAnotherUser();
            }

            if(!empty($data->getLoginAsActiveForDays())){
                $user->setDaysForLoginAs();
            }

            $user->save();
        }

        return [
            'form' => $form->createView(),
            'user' => $user,
            'portal' => $portal,
            'portalId' => $portal->getId(),
            'userId' => $user->getItemID(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/accountIndex/detail/{userId}/terminatemembership")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     */
    public function accountIndexDetailTerminateMembership(Portal $portal, Request $request, UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $user = $userService->getUser($request->get('userId'));
        $user->reject();
        $user->save();

        return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
            'portalId' => $request->get('portalId'),
            'userId' => $request->get('userId'),
        ]);
    }

    /**
     * @Route("/portal/{portalId}/settings/accountIndex/detail/{userId}/hidemail")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     */
    public function accountIndexDetailHideMail(Portal $portal, Request $request, UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $user = $userService->getUser($request->get('userId'));
        $user->setEmailNotVisible();
        $user->save();
        return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
            'portalId' => $request->get('portalId'),
            'userId' => $request->get('userId'),
        ]);
    }

    /**
     * @Route("/portal/{portalId}/settings/accountIndex/detail/{userId}/hidemailallwrks")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     */
    public function accountIndexDetailHideMailAllWrks(Portal $portal, Request $request, UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $user = $userService->getUser($request->get('userId'));
        $user->setEmailNotVisible();
        $user->save();

        $relatedUsers = $user->getRelatedUserList();
        foreach($relatedUsers as $relatedUser){
            $relatedUser->setEmailNotVisible();
            $relatedUser->save();
        }

        return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
            'portalId' => $request->get('portalId'),
            'userId' => $request->get('userId'),
        ]);
    }

    /**
     * @Route("/portal/{portalId}/settings/accountIndex/detail/{userId}/showmail")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     */
    public function accountIndexDetailShowMail(Portal $portal, Request $request, UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $user = $userService->getUser($request->get('userId'));
        $user->setEmailVisible();
        $user->save();
        return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
            'portalId' => $request->get('portalId'),
            'userId' => $request->get('userId'),
        ]);
    }

    /**
     * @Route("/portal/{portalId}/settings/accountIndex/detail/{userId}/showmailallwroks")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     */
    public function accountIndexDetailShowMailAllWroks(Portal $portal, Request $request, UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $user = $userService->getUser($request->get('userId'));
        $user->setEmailVisible();
        $user->save();

        $relatedUsers = $user->getRelatedUserList();
        foreach($relatedUsers as $relatedUser){
            $relatedUser->setMailVisible();
            $relatedUser->save();
        }

        return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
            'portalId' => $request->get('portalId'),
            'userId' => $request->get('userId'),
        ]);
    }

    /**
     * @Route("/portal/{portalId}/settings/accountIndex/detail/{userId}/takeOver")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     */
    public function accountIndexDetailTakeOver(Portal $portal, Request $request, UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $session = $this->get('session');
        $user = $userService->getUser($request->get('userId'));
        $user_item = $user;
        $environment = $legacyEnvironment->getEnvironment();

        $legacyEnvironment = $environment;

        $sessionManager = $legacyEnvironment->getSessionManager();
        $sessionItem = $legacyEnvironment->getSessionItem();

        If(!is_null($sessionItem)){
            $sessionManager->delete($sessionItem->getSessionID());
            $legacyEnvironment->setSessionItem(null);

            $cookie = $session->get('cookie');
            $javascript = $session->get('javascript');
            $https = $session->get('https');
            $flash = $session->get('flash');
            $session_id = $session->getSessionID();
            $session = new \cs_session_item();
            $session->createSessionID($user_item->getUserID());
            $session->setValue('auth_source',$user_item->getAuthSource());
            $session->setValue('root_session_id',$session_id);
            if ( $cookie == '1' ) {
                $session->setValue('cookie',2);
            } elseif ( empty($cookie) ) {
                // do nothing, so CommSy will try to save cookie
            } else {
                $session->setValue('cookie',0);
            }
            if ($javascript == '1') {
                $session->setValue('javascript',1);
            } elseif ($javascript == '-1') {
                $session->setValue('javascript',-1);
            }
            if ($https == '1') {
                $session->setValue('https',1);
            } elseif ($https == '-1') {
                $session->setValue('https',-1);
            }
            if ($flash == '1') {
                $session->setValue('flash',1);
            } elseif ($flash == '-1') {
                $session->setValue('flash',-1);
            }

            // save portal id in session to be sure, that user didn't
            // switch between portals
            if ( $environment->inServer() ) {
                $session->setValue('commsy_id',$environment->getServerID());
            } else {
                $session->setValue('commsy_id',$environment->getCurrentPortalID());
            }
            $environment->setSessionItem($session);
            redirect($environment->getCurrentContextID(),'home','index',array());
        }

        return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
            'portalId' => $request->get('portalId'),
            'userId' => $request->get('userId'),
        ]);

    }

    /**
     * @Route("/portal/{portalId}/settings/accountIndex/detail/{userId}/assignWorkspace")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accountIndexDetailAssignWorkspace(Portal $portal, Request $request, UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $user = $userService->getUser($request->get('userId'));
        $userAssignWorkspace = new PortalUserAssignWorkspace();
        $userAssignWorkspace->setUserID($user->getUserID());
        $userAssignWorkspace->setName($user->getFullName());
        $userAssignWorkspace->setWorkspaceSelection('0');
        $form = $this->createForm(AccountIndexDetailAssignWorkspaceType::class, $userAssignWorkspace);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            if($form->get('save')->isClicked()){
                $var = 0;
            }elseif($form->get('search')->isClicked()){
                $user = $userService->getUser($request->get('userId'));
                $userAssignWorkspace = new PortalUserAssignWorkspace();
                $userAssignWorkspace->setUserID($user->getUserID());
                $userAssignWorkspace->setName($user->getFullName());
                $userAssignWorkspace->setWorkspaceSelection('0');

                $formData = $form->getData();

                $form = $this->createForm(AccountIndexDetailAssignWorkspaceType::class, $userAssignWorkspace);
//                $allRooms = $portal->getContinuousRoomList($legacyEnvironment); TODO this causes a 'can't serialize PDO' error.

                $projectRoomManager = $legacyEnvironment->getEnvironment()->getProjectManager();
                $projectRooms = $projectRoomManager->getRoomsByTitle($formData->getSearchForWorkspace(), $portal->getId());

                $choiceArray = array();

                foreach($projectRooms as $currentRoom){
                    $choiceArray[$currentRoom->getTitle()] = $currentRoom->getItemID();
                }

                $formOptions = [
                    'label' => 'Select workspace',
                    'expanded' => false,
                    'placeholder' => false,
                    'choices'  => $choiceArray,
                    'translation_domain' => 'portal',
                    'required' => false,
                ];

                $form->add('workspaceSelection', ChoiceType::class, $formOptions);

                return [
                    'portal' => $portal,
                    'form' => $form->createView(),
                    'user' => $user,
                ];
            }
        }

        return [
            'portal' => $portal,
            'form' => $form->createView(),
            'user' => $user,
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/accountIndex/detail/{userId}/changePassword")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accountIndexDetailChangePassword(Portal $portal,
                                                     Request $request,
                                                     UserService $userService,
                                                     LegacyEnvironment $legacyEnvironment,
                                                     UserPasswordEncoderInterface $passwordEncoder,
                                                     EntityManagerInterface $entityManager)
    {
        $user = $userService->getUser($request->get('userId'));
        $form_data = ['userName' => $user->getFullName(), 'userId' => $user->getUserID()];
        $form = $this->createForm(AccountIndexDetailChangePasswordType::class, $form_data);
        $form->handleRequest($request);

        $accountRepo = $entityManager->getRepository(Account::class);

        if($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();
            $submittedPassword = $data['password'];

            $userPwUpdate = $accountRepo->findOneByCredentialsShort($user->getUserID(),
                $user->getContextID());
            $userPwUpdate->setPasswordMd5(null);
            $userPwUpdate->setPassword($passwordEncoder->encodePassword($userPwUpdate, $submittedPassword));

            $entityManager->persist($userPwUpdate);
            $entityManager->flush();
        }

        return [
            'form' => $form->createView(),
            'user' => $user,
            'portal' => $portal,
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/translations/{translationId?}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     * @param Portal $portal
     * @param int $translationId
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function translations(
        Portal $portal,
        $translationId,
        Request $request,
        EntityManagerInterface $entityManager
    ) {
        $editForm = null;

        $repository = $entityManager->getRepository(Translation::class);

        $translation = null;
        if ($translationId) {
            $translation = $repository->find($translationId);

            if (!$translation) {
                throw new NotFoundHttpException('No translation found for given id');
            }

            $editForm = $this->createForm(TranslationType::class, $translation, []);

            $editForm->handleRequest($request);
            if ($editForm->isSubmitted() && $editForm->isValid()) {
                $entityManager->persist($translation);
                $entityManager->flush();

                return $this->redirectToRoute('app_portalsettings_translations', [
                    'portalId' => $portal->getId(),
                ]);
            }
        }

        $translations = $repository->findBy([
            'contextId' => $portal->getId(),
        ]);

        return [
            'form' => $editForm ? $editForm->createView() : null,
            'portal' => $portal,
            'translations' => $translations,
            'selectedTranslation' => $translation,
        ];
    }
}
