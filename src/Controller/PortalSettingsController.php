<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Entity\Translation;
use App\Form\Type\Portal\AnnouncementsType;
use App\Form\Type\Portal\GeneralType;
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
     * @Route("/portal/{portalId}/settings/time")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @ParamConverter("environment", class="App\Services\LegacyEnvironment")
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function time(Portal $portal, Request $request, EntityManagerInterface $entityManager, LegacyEnvironment $environment)
    {
        $defaultData = ['showTime' => 0];
        $form = $this->createForm(TimeType::class, $defaultData);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
//
//            // get room item and current user
//            $room_item = $environment->getCurrentContextItem();
//            $current_user = $environment->getCurrentUserItem();
//            $is_saved = false;
//
//            // Check access rights
//            if ( !$room_item->isOpen() ) {
//                $params = array();
//                $params['environment'] = $environment;
//                $params['with_modifying_actions'] = true;
//                $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
//                unset($params);
//                $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
//                $page->add($errorbox);
//            } elseif ( ($room_item->isProjectRoom()) or
//                ($room_item->isCommunityRoom()) or
//                ($room_item->isPortal() and !$current_user->isModerator()) or
//                ($room_item->isServer())
//            ) {
//                $params = array();
//                $params['environment'] = $environment;
//                $params['with_modifying_actions'] = true;
//                $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
//                unset($params);
//                $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
//                $page->add($errorbox);
//            }
//
//            // Access granted
//            else {
//
//                // Find out what to do
//                if ( isset($_POST['option']) ) {
//                    $command = $_POST['option'];
//                } else {
//                    $command = '';
//                }
//
//
//                // Initialize the form
//                $form = $class_factory->getClass(CONFIGURATION_TIME_FORM,array('environment' => $environment));
//                // Display form
//                $params = array();
//                $params['environment'] = $environment;
//                $params['with_modifying_actions'] = true;
//                $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
//                unset($params);
//                $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
//
//                // ad clock pulse
//                if ( isOption($command, $translator->getMessage('CONFIGURATION_TIME_FORM_ELEMENT_AD_TITLE')) ) {
//                    $counter = 1;
//                    if (isset($_POST['clock_pulse'])) {
//                        $counter = count($_POST['clock_pulse']);
//                    }
//                    $counter++;
//                    $form->setCounter($counter);
//                    unset($counter);
//                }
//
//                // remove clock pulse
//                if (isset($_POST['clock_pulse'])) {
//                    $counter = count($_POST['clock_pulse']);
//                    for ($i=1; $i<=$counter; $i++) {
//                        if (isset($_POST['delete_'.$i])) {
//                            $new_counter = $counter-1;
//                            $delete_i = $i;
//                        }
//                    }
//                    if (isset($new_counter)) {
//                        $form->setCounter($new_counter);
//                    }
//                }
//
//                // Load form data from postvars
//                if ( !empty($_POST) ) {
//                    $values = $_POST;
//
//                    // remove clock pulse values
//                    if (isset($delete_i)) {
//                        unset($values['clock_pulse'][$delete_i]);
//                        for ($i=$delete_i; $i<=$counter; $i++) {
//                            if (isset($values['clock_pulse'][$i+1])) {
//                                $values['clock_pulse'][$i] = $values['clock_pulse'][$i+1];
//                            }
//                        }
//                        unset($values['clock_pulse'][$counter]);
//                    }
//
//                    $form->setFormPost($values);
//                    unset($values);
//                } elseif ( isset($room_item) ) {
//                    $form->setItem($room_item);
//                }
//                $form->prepareForm();
//                $form->loadValues();
//
//                // Save item
//                if ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
//                    $correct = $form->check();
//                    if ( $correct and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
//
//                        // show time
//                        if ( isset($_POST['show_time']) and !empty($_POST['show_time']) ) {
//                            if ( $_POST['show_time'] == 1 ) {
//                                $room_item->setShowTime();
//                            } elseif ( $_POST['show_time'] == -1 ) {
//                                $room_item->setNotShowTime();
//                            }
//                        }
//
//                        if (isset($_POST['name']) and !empty($_POST['name'])) {
//                            $room_item->setTimeNameArray($_POST['name']);
//                        }
//
//                        if (isset($_POST['clock_pulse']) and !empty($_POST['clock_pulse'])) {
//                            $room_item->setTimeTextArray($_POST['clock_pulse']);
//                        }
//
//                        if (isset($_POST['future']) and !empty($_POST['future'])) {
//                            $room_item->setTimeInFuture($_POST['future']);
//                        }
//
//                        // save room_item
//                        $room_item->save();
//
//                        // change (insert) time labels
//                        $clock_pulse_array = array();
//                        if (isset($_POST['clock_pulse']) and !empty($_POST['clock_pulse']) and !empty($_POST['clock_pulse'][1])) {
//                            $current_year = date('Y');
//                            $current_date = getCurrentDate();
//                            $ad_year = 0;
//                            $first = true;
//                            foreach ($_POST['clock_pulse'] as $key => $clock_pulse) {
//                                $date_string = $clock_pulse['BEGIN'];
//                                $month = $date_string[3].$date_string[4];
//                                $day = $date_string[0].$date_string[1];
//                                $begin = $month.$day;
//
//                                $date_string = $clock_pulse['END'];
//                                $month = $date_string[3].$date_string[4];
//                                $day = $date_string[0].$date_string[1];
//                                $end = $month.$day;
//
//                                $begin2 = ($current_year+$ad_year).$begin;
//                                if ($end < $begin) {
//                                    $ad_year++;
//                                    $ad_year_pos = $key;
//                                }
//                                $end2 = ($current_year+$ad_year).$end;
//
//                                if ($first) {
//                                    $first = false;
//                                    $begin_first = $begin2;
//                                }
//
//                                if ( $begin2 <= $current_date
//                                    and $current_date <= $end2) {
//                                    $current_pos = $key;
//                                }
//                            }
//
//                            $year = $current_year;
//
//                            if ($current_date < $begin_first) {
//                                $year--;
//                                $current_pos = count($_POST['clock_pulse']);
//                            }
//
//                            $count = count($_POST['clock_pulse']);
//                            $position = 1;
//                            for ($i=0; $i<$_POST['future']+$current_pos; $i++) {
//                                $clock_pulse_array[] = $year.'_'.$position;
//                                $position++;
//                                if ($position > $count) {
//                                    $position = 1;
//                                    $year++;
//                                }
//                            }
//                        }
//
//                        if (!empty($clock_pulse_array)) {
//                            $done_array = array();
//                            $time_manager = $environment->getTimeManager();
//                            $time_manager->reset();
//                            $time_manager->setContextLimit($environment->getCurrentContextID());
//                            $time_manager->setDeleteLimit(false);
//                            $time_manager->select();
//                            $time_list = $time_manager->get();
//                            if ($time_list->isNotEmpty()) {
//                                $time_label = $time_list->getFirst();
//                                while ($time_label) {
//                                    if (!in_array($time_label->getTitle(),$clock_pulse_array)) {
//                                        $first_new_clock_pulse = $clock_pulse_array[0];
//                                        $last_new_clock_pulse = array_pop($clock_pulse_array);
//                                        $clock_pulse_array[] = $last_new_clock_pulse;
//                                        if ($time_label->getTitle() < $first_new_clock_pulse) {
//                                            $temp_clock_pulse_array = explode('_',$time_label->getTitle());
//                                            $clock_pulse_pos = $temp_clock_pulse_array[1];
//                                            if ($clock_pulse_pos > $count) {
//                                                if (!$time_label->isDeleted()) {
//                                                    $time_label->setDeleterItem($environment->getCurrentUserItem());
//                                                    $time_label->delete();
//                                                }
//                                            } else {
//                                                if ($time_label->isDeleted()) {
//                                                    $time_label->setModificatorItem($environment->getCurrentUserItem());
//                                                    $time_label->unDelete();
//                                                }
//                                            }
//                                        } elseif ($time_label->getTitle() > $last_new_clock_pulse) {
//                                            if (!$time_label->isDeleted()) {
//                                                $time_label->setDeleterItem($environment->getCurrentUserItem());
//                                                $time_label->delete();
//                                            }
//                                        } else {
//                                            if (!$time_label->isDeleted()) {
//                                                $time_label->setDeleterItem($environment->getCurrentUserItem());
//                                                $time_label->delete();
//                                            }
//                                        }
//                                    } else {
//                                        if ($time_label->isDeleted()) {
//                                            $time_label->setModificatorItem($environment->getCurrentUserItem());
//                                            $time_label->unDelete();
//                                        }
//                                        $done_array[] = $time_label->getTitle();
//                                    }
//                                    $time_label = $time_list->getNext();
//                                }
//                            }
//
//                            foreach ($clock_pulse_array as $clock_pulse) {
//                                if (!in_array($clock_pulse,$done_array)) {
//                                    $time_label = $time_manager->getNewItem();
//                                    $time_label->setContextID($environment->getCurrentContextID());
//                                    $user = $environment->getCurrentUserItem();
//                                    $time_label->setCreatorItem($user);
//                                    $time_label->setModificatorItem($user);
//                                    $time_label->setTitle($clock_pulse);
//                                    $time_label->save();
//                                }
//                            }
//                        } else {
//                            $time_manager = $environment->getTimeManager();
//                            $time_manager->reset();
//                            $time_manager->setContextLimit($environment->getCurrentContextID());
//                            $time_manager->select();
//                            $time_list = $time_manager->get();
//                            if ($time_list->isNotEmpty()) {
//                                $time_label = $time_list->getFirst();
//                                while ($time_label) {
//                                    $time_label->setDeleterItem($environment->getCurrentUserItem());
//                                    $time_label->delete();
//                                    $time_label = $time_list->getNext();
//                                }
//                            }
//                        }
//
//                        // renew links to continuous rooms
//                        $current_context = $environment->getCurrentContextItem();
//                        $room_list = $current_context->getContinuousRoomList();
//                        if ($room_list->isNotEmpty()) {
//                            $room_item2 = $room_list->getFirst();
//                            while ($room_item2) {
//                                if ($room_item2->isOpen()) {
//                                    $room_item2->setContinuous();
//                                    $room_item2->saveWithoutChangingModificationInformation();
//                                }
//                                $room_item2 = $room_list->getNext();
//                            }
//                        }
//
//                        $form_view->setItemIsSaved();
//                        $is_saved = true;
//                    }
//                }
//
//                $form_view->setForm($form);
//                if ( $environment->inPortal() or $environment->inServer() ) {
//                    $page->addForm($form_view);
//                } else {
//                    $page->add($form_view);
//                }
//            }

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
