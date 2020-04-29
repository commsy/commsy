<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Entity\Translation;
use App\Form\Type\Portal\AnnouncementsType;
use App\Form\Type\Portal\GeneralType;
use App\Form\Type\Portal\InactiveType;
use App\Form\Type\Portal\PortalhomeType;
use App\Form\Type\Portal\SupportType;
use App\Form\Type\Portal\TimeType;
use App\Form\Type\TranslationType;
use App\Services\LegacyEnvironment;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormError;


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
     * @Route("/portal/{portalId}/settings/accounts")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accounts(Portal $portal, Request $request)
    {

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
