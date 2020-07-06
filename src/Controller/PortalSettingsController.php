<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\AccountIndex;
use App\Entity\AccountIndexSendMail;
use App\Entity\AccountIndexSendMergeMail;
use App\Entity\AccountIndexSendPasswordMail;
use App\Entity\AccountIndexUser;
use App\Entity\AuthSource;
use App\Entity\License;
use App\Entity\Portal;
use App\Entity\PortalUserAssignWorkspace;
use App\Entity\PortalUserChangeStatus;
use App\Entity\PortalUserEdit;
use App\Entity\Room;
use App\Entity\RoomCategories;
use App\Entity\Translation;
use App\Entity\User;
use App\Event\CommsyEditEvent;
use App\Form\DataTransformer\UserTransformer;
use App\Form\Type\Portal\AccountIndexDetailAssignWorkspaceType;
use App\Form\Type\Portal\AccountIndexDetailChangePasswordType;
use App\Form\Type\Portal\AccountIndexDetailChangeStatusType;
use App\Form\Type\Portal\AccountIndexDetailEditType;
use App\Form\Type\Portal\AccountIndexDetailType;
use App\Form\Type\Portal\AccountIndexSendMergeMailType;
use App\Form\Type\Portal\AccountIndexSendPasswordMailType;
use App\Form\Type\Portal\AccountIndexType;
use App\Form\Type\Portal\AnnouncementsType;
use App\Form\Type\Portal\CommunityRoomsCreationType;
use App\Form\Type\Portal\GeneralType;
use App\Form\Type\Portal\InactiveType;
use App\Form\Type\Portal\LicenseType;
use App\Form\Type\Portal\LicenseSortType;
use App\Form\Type\Portal\MandatoryAssignmentType;
use App\Form\Type\Portal\PortalhomeType;
use App\Form\Type\Portal\PrivacyType;
use App\Form\Type\Portal\ProjectRoomsCreationType;
use App\Form\Type\Portal\RoomCategoriesType;
use App\Form\Type\Portal\SupportRequestsType;
use App\Form\Type\Portal\SupportType;
use App\Form\Type\Portal\TermsType;
use App\Form\Type\Portal\TimeType;
use App\Form\Type\Portal\AccountIndexSendMailType;
use App\Form\Type\TranslationType;
use App\Repository\AuthSourceRepository;
use App\Services\LegacyEnvironment;
use App\Services\RoomCategoriesService;
use App\Utils\ItemService;
use App\Utils\MailAssistant;
use App\Utils\RoomService;
use App\Utils\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
     * @param Portal $portal
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     */
    public function general(Portal $portal, Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(GeneralType::class, $portal);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->getClickedButton()->getName() === 'save') {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
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
     * @param Portal $portal
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     */
    public function support(Portal $portal, Request $request, EntityManagerInterface $entityManager)
    {
        // support page form
        $supportPageForm = $this->createForm(SupportType::class, $portal);

        $supportPageForm->handleRequest($request);
        if ($supportPageForm->isSubmitted() && $supportPageForm->isValid()) {

            if ($supportPageForm->getClickedButton()->getName() === 'save') {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        // support requests form
        $supportRequestsForm = $this->createForm(SupportRequestsType::class, $portal);

        $supportRequestsForm->handleRequest($request);
        if ($supportRequestsForm->isSubmitted() && $supportRequestsForm->isValid()) {

            if ($supportRequestsForm->getClickedButton()->getName() === 'save') {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        return [
            'supportPageForm' => $supportPageForm->createView(),
            'supportRequestsForm' => $supportRequestsForm->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/portalhome")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @ParamConverter("environment", class="App\Services\LegacyEnvironment")
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     * @param Portal $portal
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     */
    public function portalhome(Portal $portal, Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(PortalhomeType::class, $portal);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->getClickedButton()->getName() === 'save') {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/roomcreation")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     * @param Portal $portal
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param RoomService $roomService
     */
    public function roomCreation(Portal $portal, Request $request, EntityManagerInterface $entityManager, RoomService $roomService)
    {
        // community rooms creation form
        $templateChoices = array_merge(['No template' => '-1'], $roomService->getAvailableTemplates(CS_COMMUNITY_TYPE));

        $communityRoomsForm = $this->createForm(CommunityRoomsCreationType::class, $portal, [
            'templateChoices' => $templateChoices,
        ]);

        $communityRoomsForm->handleRequest($request);
        if ($communityRoomsForm->isSubmitted() && $communityRoomsForm->isValid()) {

            if ($communityRoomsForm->getClickedButton()->getName() === 'save') {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        // project rooms creation form
        $templateChoices = array_merge(['No template' => '-1'], $roomService->getAvailableTemplates(CS_PROJECT_TYPE));

        $projectRoomsForm = $this->createForm(ProjectRoomsCreationType::class, $portal, [
            'templateChoices' => $templateChoices,
        ]);

        $projectRoomsForm->handleRequest($request);
        if ($projectRoomsForm->isSubmitted() && $projectRoomsForm->isValid()) {

            if ($projectRoomsForm->getClickedButton()->getName() === 'save') {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        return [
            'communityRoomsForm' => $communityRoomsForm->createView(),
            'projectRoomsForm' => $projectRoomsForm->createView(),
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
    public function roomCategories(
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
                $entityManager->flush();
            } else if ($clickedButtonName === 'delete') {
                $roomCategoriesService->removeRoomCategory($roomCategory);
                $entityManager->flush();
            }

            return $this->redirectToRoute('app_portalsettings_roomcategories', [
                'portalId' => $portal->getId(),
            ]);
        }

        $roomCategories = $repository->findBy([
            'context_id' => $portalId,
        ]);

        $dispatcher->dispatch(new CommsyEditEvent(null), CommsyEditEvent::EDIT);


        // ensure that room categories aren't mandatory if there currently aren't any room categories
        if (empty($roomCategories)) {
            $portal->setTagMandatory(false);
            $entityManager->persist($portal);
            $entityManager->flush();
        }


        // mandatory links form
        $linkForm = $this->createForm(MandatoryAssignmentType::class, $portal);

        $linkForm->handleRequest($request);
        if ($linkForm->isSubmitted() && $linkForm->isValid()) {

            if ($linkForm->getClickedButton()->getName() === 'save') {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }


        return [
            'editForm' => $editForm->createView(),
            'linkForm' => $linkForm->createView(),
            'portal' => $portal,
            'roomCategoryId' => $roomCategoryId,
            'roomCategories' => $roomCategories,
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/licenses/{licenseId?}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     * @param Portal $portal
     * @param int|null $licenseId
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param RoomService $roomService
     * @param EventDispatcherInterface $dispatcher
     * @param LegacyEnvironment $environment
     */
    public function licenses(
        Portal $portal,
        $licenseId,
        Request $request,
        EntityManagerInterface $entityManager,
        RoomService $roomService,
        EventDispatcherInterface $dispatcher,
        LegacyEnvironment $environment
    ) {
        $portalId = $portal->getId();

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(License::class);

        $license = new License();
        if ($licenseId) {
            $license = $repository->findOneById($licenseId);
            $license->setTitle(html_entity_decode($license->getTitle()));
        }

        $licenseForm = $this->createForm(LicenseType::class, $license);

        // determine title
        $pageTitle = '';
        if ($licenseForm->has('new')) {
            $pageTitle = 'Create new license';
        } elseif($licenseForm->has('update')) {
            $pageTitle = 'Edit license';
        }

        // handle new/edit form
        $licenseForm->handleRequest($request);
        if ($licenseForm->isSubmitted() && $licenseForm->isValid()) {
            if (!$licenseForm->has('cancel') || !$licenseForm->get('cancel')->isClicked()) {
                $license->setContextId($portalId);

                if (!$license->getPosition()) {
                    $position = 0;
                    $highestPosition = $repository->findHighestPosition($portalId);

                    if ($highestPosition) {
                        $highestPosition = $highestPosition[0];
                        $position = $highestPosition['position'] + 1;
                    }

                    $license->setPosition($position);
                }

                $em->persist($license);
                $em->flush();

                $dispatcher->dispatch(new CommsyEditEvent(null), 'commsy.edit');
            }

            return $this->redirectToRoute('app_portalsettings_licenses', [
                'portalId' => $portalId,
            ]);
        }

        // sort form
        $sortForm = $this->createForm(LicenseSortType::class, null, [
            'portalId' => $portalId,
        ]);
        $sortForm->handleRequest($request);

        if ($sortForm->isSubmitted() && $sortForm->isValid()) {
            $data = $sortForm->getData();

            /** @var ArrayCollection $delete */
            $delete = $data['license'];
            if (!$delete->isEmpty()) {
                $legacyEnvironment = $environment->getEnvironment();

                $materialManager = $legacyEnvironment->getMaterialManager();
                $materialManager->unsetLicenses($delete->get(0));

                $zzzMaterialManager = $legacyEnvironment->getZzzMaterialManager();
                $zzzMaterialManager->unsetLicenses($delete->get(0));

                $em->remove($delete->get(0));
                $em->flush();
            }

            $structure = $data['structure'];
            if ($structure) {
                $structure = json_decode($structure, true);

                // update position
                $repository->updatePositions($structure, $portalId);
            }

            return $this->redirectToRoute('app_portalsettings_licenses', [
                'portalId' => $portalId,
            ]);
        }

        return [
            'licenseForm' => $licenseForm->createView(),
            'licenseSortForm' => $sortForm->createView(),
            'portalId' => $portalId,
            'pageTitle' => $pageTitle,
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/privacy")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     * @param Portal $portal
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     */
    public function privacy(Portal $portal, Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(PrivacyType::class, $portal);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->getClickedButton()->getName() === 'save') {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/inactive")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     * @param Portal $portal
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     */
    public function inactive(Portal $portal, Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(InactiveType::class, $portal);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->getClickedButton()->getName() === 'save') {
                $entityManager->persist($portal);
                $entityManager->flush();
            }

            // TODO: inform the user how many inactive accounts would be locked/deleted due to the currently entered day values (see `configuration_inactive.php`)
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
     * @Route("/portal/{portalId}/settings/terms")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     * @param Portal $portal
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     */
    public function terms(Portal $portal, Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(TermsType::class, $portal);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->getClickedButton()->getName() === 'save') {
                $portal->setAGBChangeDate(new \DateTime());
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        return [
            'form' => $form->createView(),
            'portal' => $portal,
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/accountindex")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accountIndex(
        $portalId,
        Portal $portal,
        UserService $userService,
        Request $request,
        LegacyEnvironment $environment,
        \Swift_Mailer $mailer,
        PaginatorInterface $paginator)
    {
        $user = $userService->getCurrentUserItem();
        $portalUsers = $userService->getListUsers($portal->getId());
        $userList = [];
        foreach($portalUsers as $portalUser){
            $relatedUsers = $portalUser->getRelatedUserList();
            foreach($relatedUsers as $relatedUser){
                array_push($userList, $relatedUser);
            }
        }

        $accountIndex = new AccountIndex();

        $accountIndexUserList = [];
        $accountIndexUserIds = array();

        foreach($userList as $singleUser) {
            $singleAccountIndexUser = new AccountIndexUser();
            $singleAccountIndexUser->setName($singleUser->getFullName());
            $singleAccountIndexUser->setChecked(false);
            $singleAccountIndexUser->setItemId($singleUser->getItemID());
            $singleAccountIndexUser->setMail($singleUser->getEmail());
            $singleAccountIndexUser->setUserId($singleUser->getUserID());
            array_push($accountIndexUserList, $singleAccountIndexUser);
            $accountIndexUserIds[$singleUser->getItemID()] = false;
        }

        $accountIndex->setAccountIndexUsers($accountIndexUserList);
        $accountIndex->setIds($accountIndexUserIds);
        $form = $this->createForm(AccountIndexType::class, $accountIndex);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if($form->get('search')->isClicked()){

                $portalUsers = $userService->getListUsers($portal->getId());
                $tempUserList = [];
                $userList = [];
                foreach($portalUsers as $portalUser){
                    $relatedUsers = $portalUser->getRelatedUserList();
                    foreach($relatedUsers as $relatedUser){
                        array_push($tempUserList, $relatedUser);
                    }
                }
                $searchParam = $data->getAccountIndexSearchString();

                if(empty($searchParam)){
                    foreach($tempUserList as $singleUser){
                        if($this->meetsFilterChoiceCriteria($data->getUserIndexFilterChoice(), $singleUser, $portal, $environment)){
                            array_push($userList, $singleUser); //remove users not fitting the search string
                        }
                    }
                }else{
                    foreach($tempUserList as $singleUser){
                        if((strpos($singleUser->getUserID(), $searchParam) !== false) and $this->meetsFilterChoiceCriteria($data->getUserIndexFilterChoice(), $singleUser, $portal, $environment)){
                            array_push($userList, $singleUser); //remove users not fitting the search string
                        }
                    }
                }

                $accountIndex = new AccountIndex();

                $accountIndexUserList = [];
                $accountIndexUserIds = array();

                foreach($userList as $singleUser) {
                    $singleAccountIndexUser = new AccountIndexUser();
                    $singleAccountIndexUser->setName($singleUser->getFullName());
                    $singleAccountIndexUser->setChecked(false);
                    $singleAccountIndexUser->setItemId($singleUser->getItemID());
                    $singleAccountIndexUser->setMail($singleUser->getEmail());
                    $singleAccountIndexUser->setUserId($singleUser->getUserID());
                    array_push($accountIndexUserList, $singleAccountIndexUser);
                    $accountIndexUserIds[$singleUser->getItemID()] = false;
                }

                $accountIndex->setAccountIndexUsers($accountIndexUserList);
                $accountIndex->setIds($accountIndexUserIds);
                $form = $this->createForm(AccountIndexType::class, $accountIndex);
            }elseif($form->get('execute')->isClicked()){
                $data = $form->getData();
                $ids = $data->getIds();

                switch ($data->getIndexViewAction()) {
                    case 0:
                        break;
                    case 1: // user-delete
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                array_push($IdsMailRecipients, $id);
                                $user = $userService->getUser($id);
                                $user->delete();
                                $user->save();
                            }
                        }
                        $this->sendUserInfoMail($IdsMailRecipients, 'user-delete', $user, $mailer, $userService, $environment);
                        break;
                    case 2: // user-block
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                array_push($IdsMailRecipients, $id);
                                $user = $userService->getUser($id);
                                $user->lock();
                                $user->save();
                            }
                        }
                        $this->sendUserInfoMail($IdsMailRecipients, 'user-block', $user, $mailer, $userService, $environment);
                        break;
                    case 3: // user-confirm
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                array_push($IdsMailRecipients, $id);
                                $user = $userService->getUser($id);
                                $user->isNotActivated(); //TODO which function?
                                $user->save();
                            }
                        }
                        $this->sendUserInfoMail($IdsMailRecipients, 'user-confirm', $user, $mailer, $userService, $environment);
                        break;
                    case 4: // change user mail the next time he/she logs in
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                $user = $userService->getUser($id);
                                $user->setHasToChangeEmail();
                                $user->save();
                            }
                        }
                        break;
                    case 'user-status-reading-user':
                        foreach ($ids as $id) {
                            $user = $userService->getUser($id);
                            $user->setStatus(4);
                            $user->save();
                        }
                        break;

                    case 5: // 'user-status-user
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                array_push($IdsMailRecipients, $id);
                                $user = $userService->getUser($id);
                                $user->makeUser();
                                $user->save();
                            }
                        }
                        $this->sendUserInfoMail($IdsMailRecipients, 'user-status-user', $user, $mailer, $userService, $environment);
                        break;
                    case 6: // user-status-moderator
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                array_push($IdsMailRecipients, $id);
                                $user = $userService->getUser($id);
                                $user->setStatus(3);
                                //$user->save();
                            }
                        }
                        $this->sendUserInfoMail($IdsMailRecipients, 'user-status-moderator', $user, $mailer, $userService, $environment);
                        break;
                    case 7: //user-contact
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                array_push($IdsMailRecipients, $id);
                                $user = $userService->getUser($id);
                                $user->makeContactPerson();
                                $user->save();
                            }
                        }
                        $this->sendUserInfoMail($IdsMailRecipients, 'user-contact', $user, $mailer, $userService, $environment);
                        break;
                    case 8: // user-contact-remove
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                array_push($IdsMailRecipients, $id);
                                $user = $userService->getUser($id);
                                $user->makeContactPerson();
                                $user->save();
                            }
                        }
                        $this->sendUserInfoMail($IdsMailRecipients, 'user-contact-remove', $user, $mailer, $userService, $environment);
                        break;
                    case 9: // send mail
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                array_push($IdsMailRecipients, $id);
                            }
                        }
                        return $this->redirectToRoute('app_portalsettings_accountindexsendmail', [
                            'portalId' => $portalId,
                            'recipients' => implode(", ",$IdsMailRecipients),
                        ]);
                        break;
                    case 10: // send mail userID and password
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                array_push($IdsMailRecipients, $id);
                            }
                        }
                        return $this->redirectToRoute('app_portalsettings_accountindexsendpasswordmail', [
                            'portalId' => $portalId,
                            'recipients' => implode(", ",$IdsMailRecipients),
                        ]);
                        break;
                    case 11: // send mail merge userIDs
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                array_push($IdsMailRecipients, $id);
                            }
                        }
                        return $this->redirectToRoute('app_portalsettings_accountindexsendmergemail', [
                            'portalId' => $portalId,
                            'recipients' => implode(", ",$IdsMailRecipients),
                        ]);
                        break;
                    case 12: // hide mail
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                $user = $userService->getUser($id);
                                $user->setDefaultMailNotVisible();
                                $user->save();
                            }
                        }
                        break;
                    case 13: // hide mail everywhere
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                $user = $userService->getUser($id);
                                $user->setDefaultMailNotVisible();
                                $user->save();
                                $allRelatedUsers = $user->getRelatedPortalUserItem();
                                foreach($allRelatedUsers as $relatedUser){
                                    $relatedUser->setDefaultMailNotVisible();
                                    $relatedUser->save();
                                }
                            }
                        }
                        break;
                    case 14: // show mail
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                $user = $userService->getUser($id);
                                $user->setDefaultMailVisible();
                                $user->save();
                            }
                        }
                        break;
                    case 15: // hide mail everywhere
                        foreach ($ids as $id => $checked) {
                            if($checked){
                                $user = $userService->getUser($id);
                                $user->setDefaultMailVisible();
                                $user->save();
                                $allRelatedUsers = $user->getRelatedPortalUserItem();
                                foreach($allRelatedUsers as $relatedUser){
                                    $relatedUser->setDefaultMailVisible();
                                    $relatedUser->save();
                                }
                            }
                        }
                        break;
                }

                $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
                    'portalId' => $portal->getId(),
                    'userId' => $user->getItemID(),
                ]);

                $this->addFlash('performedSuccessfully', $returnUrl);
            }
        }
        $pagination = $paginator->paginate(
            $userList, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            20 /*limit per page*/
        );

        return [
            'form' => $form->createView(),
            'userList' => $userList,
            'portal' => $portal,
            'pagination' => $pagination,
        ];
    }


    private function meetsFilterChoiceCriteria($filterChoice, $userInQuestion, $portal, LegacyEnvironment $environment){
        $meetsCriteria = false;
        switch ($filterChoice) {
            case 0: //no selection
                $meetsCriteria = true;
                break;
            case 1: // Members
                if($userInQuestion->isRoomMember()){
                    $meetsCriteria = true;
                }
                break;
            case 2: // locked
                if($userInQuestion->isLocked()){
                    $meetsCriteria = true;
                }
                break;
            case 3: // In activation
                $meetsCriteria = true;
                break;
            case 4: // User
                if($userInQuestion->isUser()){
                    $meetsCriteria = true;
                }
                break;
            case 5: // Moderator
                if($userInQuestion->isModerator()){
                    $meetsCriteria = true;
                }
                break;
            case 6: // Contact
                if($userInQuestion->isContact()){
                    $meetsCriteria = true;
                }
                break;
            case 7: // Community workspace moderator

                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);

                foreach($continuousWorkspaces as $continuousWorkspace){
                    if($continuousWorkspace->getItemID() == $userInQuestion->getContextItem()->getItemID()
                    and $userInQuestion->isModerator()
                    and $continuousWorkspace->getType() == 'community'){
                        $meetsCriteria = true;
                    }
                }
                break;
            case 8: // Community workspace contact
                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);

                foreach($continuousWorkspaces as $continuousWorkspace){
                    if($continuousWorkspace->getItemID() == $userInQuestion->getContextItem()->getItemID()
                        and $userInQuestion->isContact()
                        and $continuousWorkspace->getType() == 'community'){
                        $meetsCriteria = true;
                    }
                }
                break;
            case 9: // Project workspace moderator
                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);

                foreach($continuousWorkspaces as $continuousWorkspace){
                    if($continuousWorkspace->getItemID() == $userInQuestion->getContextItem()->getItemID()
                        and $userInQuestion->isModerator()
                        and $continuousWorkspace->getType() == 'project'){
                        $meetsCriteria = true;
                    }
                }
                break;
            case 10: // project workspace contact
                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);

                foreach($continuousWorkspaces as $continuousWorkspace){
                    if($continuousWorkspace->getItemID() == $userInQuestion->getContextItem()->getItemID()
                        and $userInQuestion->isContact
                        and $continuousWorkspace->getType() == 'project'){
                        $meetsCriteria = true;
                    }
                }
                break;
            case 11: // moderator of any workspace
                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);
                foreach($continuousWorkspaces as $continuousWorkspace){
                    if($continuousWorkspace->getItemID() == $userInQuestion->getContextItem()->getItemID()
                        and $userInQuestion->isModerator()){
                        $meetsCriteria = true;
                    }
                }
                break;
            case 12: // contact of any workspace
                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);

                foreach($continuousWorkspaces as $continuousWorkspace){
                    if($continuousWorkspace->getItemID() == $userInQuestion->getContextItem()->getItemID()
                        and $userInQuestion->isCOntact){
                        $meetsCriteria = true;
                    }
                }
                break;
            case 13: // no workspace membership
                if(!$userInQuestion->isRoomMember()){
                    $meetsCriteria = true;
                }
                break;
        }
        return $meetsCriteria;
    }

    private function getContinuousRoomList($environment, $portal){
        $manager = $environment->getEnvironment()->getRoomManager();
        $manager->reset();
        $manager->resetLimits();
        $manager->setContextLimit($portal->getId());
        $manager->setContinuousLimit();
        $manager->select();
        return $manager->get();
    }

    /**
     * @Route("/portal/{portalId}/settings/accountindex/sendmail/{recipients}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accountIndexSendMail(
        $portalId,
        $recipients,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        MailAssistant $mailAssistant,
        UserService $userService,
        UserTransformer $userTransformer,
        ItemService $itemService,
        \Swift_Mailer $mailer,
        Portal $portal
    ) {
        $user = $userService->getCurrentUserItem();
        $recipientArray = [];
        $recipients = explode(', ', $recipients);
        foreach($recipients as $recipient){
            $currentUser = $userService->getUser($recipient);
            array_push($recipientArray, $currentUser);
        }

        $sendMail = new AccountIndexSendMail();
        $sendMail->setRecipients($recipientArray);
        $body = $this->generateBody($userService->getCurrentUserItem(), 'user-account_send_mail', $legacyEnvironment);
        $sendMail->setMessage($body);

        $form = $this->createForm(AccountIndexSendMailType::class, $sendMail);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $mailRecipients = $data->getRecipients();

            $countTo = 0;
            $countCc = 0;
            $countBcc = 0;

            foreach($mailRecipients as $mailRecipient){
                $item = $itemService->getTypedItem($mailRecipient->getItemId());
                $message = $mailAssistant->getSwiftMailForAccountIndexSendMail($form, $item, false);
                $mailer->send($message);

                if(!is_null($message->getTo())){
                    $countTo += count($message->getTo());
                }
                if(!is_null($message->getCc())){
                    $countTo += count($message->getCc());
                }
                if(!is_null($message->getBcc())){
                    $countTo += count($message->getBcc());
                }
            }

            $recipientCount = $countTo + $countCc + $countBcc;
            $this->addFlash('recipientCount', $recipientCount);

            $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
                'portalId' => $portal->getId(),
                'userId' => $user->getItemID(),
            ]);
            $this->addFlash('savedSuccess', $returnUrl);
        }

        return [
            'user' => $user,
            'form' => $form->createView(),
            'recipients' => $recipientArray,
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/accountindex/sendpasswordmail/{recipients}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accountIndexSendPasswordMail(
        Portal $portal,
        $portalId,
        $recipients,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        MailAssistant $mailAssistant,
        UserService $userService,
        UserTransformer $userTransformer,
        ItemService $itemService,
        \Swift_Mailer $mailer
    ){
        $recipientArray = [];
        $recipients = explode(', ', $recipients);
        foreach($recipients as $recipient){
            $currentUser = $userService->getUser($recipient);
            array_push($recipientArray, $currentUser);
        }

        $sendMail = new AccountIndexSendPasswordMail();
        $sendMail->setRecipients($recipientArray);

        $user = $legacyEnvironment->getEnvironment()->getCurrentUser();
        $action = 'user-account_password';
        $subject = $this->generateSubject($legacyEnvironment, $action);
        $body = $this->generateBody($user, $action, $legacyEnvironment);
        $sendMail->setSubject($subject);
        $sendMail->setMessage($body);

        $form = $this->createForm(AccountIndexSendPasswordMailType::class, $sendMail);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $mailRecipients = $data->getRecipients();

            $countTo = 0;
            $countCc = 0;
            $countBcc = 0;

            foreach($mailRecipients as $mailRecipient){

                $item = $itemService->getTypedItem($mailRecipient->getItemId());
                $message = $mailAssistant->getSwiftMailForAccountIndexSendPasswordMail($form, $item, true);
                $mailer->send($message);

                if(!is_null($message->getTo())){
                    $countTo += count($message->getTo());
                }
                if(!is_null($message->getCc())){
                    $countTo += count($message->getCc());
                }
                if(!is_null($message->getBcc())){
                    $countTo += count($message->getBcc());
                }
            }

            $recipientCount = $countTo + $countCc + $countBcc;
            $this->addFlash('recipientCount', $recipientCount);

            $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
                'portalId' => $portal->getId(),
                'userId' => $user->getItemID(),
            ]);
            $this->addFlash('savedSuccess', $returnUrl);

        }

        return [
            'portal' => $portal,
            'form' => $form->createView(),
            'recipients' => $recipientArray,
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/accountindex/sendmergemail/{recipients}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accountIndexSendMergeMail(
        Portal $portal,
        $portalId,
        $recipients,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        MailAssistant $mailAssistant,
        UserService $userService,
        UserTransformer $userTransformer,
        ItemService $itemService,
        \Swift_Mailer $mailer
    ){
        $recipientArray = [];
        $recipients = explode(', ', $recipients);
        foreach($recipients as $recipient){
            $currentUser = $userService->getUser($recipient);
            array_push($recipientArray, $currentUser);
        }

        $sendMail = new AccountIndexSendMergeMail();
        $sendMail->setRecipients($recipientArray);

        $user = $legacyEnvironment->getEnvironment()->getCurrentUser();

        $action = 'user-account-merge';
        $subject = $this->generateSubject($legacyEnvironment, $action);
        $body = $this->generateBody($user, $action, $legacyEnvironment);
        $sendMail->setSubject($subject);
        $sendMail->setMessage($body);

        $form = $this->createForm(AccountIndexSendMergeMailType::class, $sendMail);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $mailRecipients = $data->getRecipients();

            $countTo = 0;
            $countCc = 0;
            $countBcc = 0;

            foreach($mailRecipients as $mailRecipient){

                $item = $itemService->getTypedItem($mailRecipient->getItemId());
                $message = $mailAssistant->getSwiftMailForAccountIndexSendPasswordMail($form, $item, true);
                $mailer->send($message);

                if(!is_null($message->getTo())){
                    $countTo += count($message->getTo());
                }
                if(!is_null($message->getCc())){
                    $countTo += count($message->getCc());
                }
                if(!is_null($message->getBcc())){
                    $countTo += count($message->getBcc());
                }

            }

            $recipientCount = $countTo + $countCc + $countBcc;
            $this->addFlash('recipientCount', $recipientCount);

            $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
                'portalId' => $portal->getId(),
                'userId' => $user->getItemID(),
            ]);
            $this->addFlash('savedSuccess', $returnUrl);
        }

        return [
            'portal' => $portal,
            'form' => $form->createView(),
            'recipients' => $recipientArray,
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/accountindex/detail/{userId}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accountIndexDetail(
        Portal $portal,
        Request $request,
        UserService $userService,
        LegacyEnvironment $legacyEnvironment)
    {
        $userList = $userService->getListUsers($portal->getId());
        $form = $this->createForm(AccountIndexDetailType::class, $portal);
        $form->handleRequest($request);
        $user = $userService->getUser($request->get('userId'));
        $authSource = $user->getAuthSource();
        $authRepo = $this->getDoctrine()->getRepository(AuthSource::class);
        $authSourceItem = $authRepo->find($authSource); //TODO: could be useful for authsource settings, but it is not even used in legacy code?

        $communities = $user->getRelatedCommunityList();
        $communityListNames = [];
        foreach($communities as $community){
            array_push($communityListNames, $community->getTitle());
        }
        $projects = $user->getRelatedProjectList();
        $projectsListNames = [];
        foreach($projects as $project){
            array_push($projectsListNames, $project->getTitle());
        }

        $communities = $user->getRelatedCommunityList();
        $communityArchivedListNames = [];
        foreach($communities as $community){
            if($community->getStatus() == '2'){
                array_push($communityArchivedListNames, $community->getTitle());
            };
        }
        $projects = $user->getRelatedProjectList();
        $projectsArchivedListNames = [];
        foreach($projects as $project){
            if($project->getStatus() == '2'){
                array_push($projectsArchivedListNames, $project->getTitle());
            };
        }

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
                    'communities'  => implode(', ', $communityListNames),
                    'projects' => implode(', ', $projectsListNames),
                    'communitiesArchived'  => implode(', ', $communityArchivedListNames),
                    'projectsArchived' => implode(', ', $projectsArchivedListNames),
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
            'communities'  => implode(', ', $communityListNames),
            'projects' => implode(', ', $projectsListNames),
            'communitiesArchived'  => implode(', ', $communityArchivedListNames),
            'projectsArchived' => implode(', ', $projectsArchivedListNames),
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


        $uploadUrl = $this->generateUrl('app_upload_upload', array(
            'roomId' => $portal->getId(),
            'itemId' => $user->getItemID(),
        ));

        $userEdit->setUploadUrl($uploadUrl);

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

            $returnUrl = $this->generateUrl('app_portalsettings_accountindexdetail', [
                'portalId' => $portal->getId(),
                'userId' => $user->getItemID(),
            ]);
            $user->save();
            $this->addFlash('savedSuccess', $returnUrl);
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

        $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
            'portalId' => $portal->getId(),
            'userId' => $user->getItemID(),
        ]);

        $this->addFlash('performedSuccessfully', $returnUrl);

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

        $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
            'portalId' => $portal->getId(),
            'userId' => $user->getItemID(),
        ]);

        $this->addFlash('performedSuccessfully', $returnUrl);

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

        $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
            'portalId' => $portal->getId(),
            'userId' => $user->getItemID(),
        ]);

        $this->addFlash('performedSuccessfully', $returnUrl);

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

        $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
            'portalId' => $portal->getId(),
            'userId' => $user->getItemID(),
        ]);

        $this->addFlash('performedSuccessfully', $returnUrl);

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
            $relatedUser->setEmailVisible();
            $relatedUser->save();
        }

        $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
            'portalId' => $portal->getId(),
            'userId' => $user->getItemID(),
        ]);

        $this->addFlash('performedSuccessfully', $returnUrl);

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

        $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
            'portalId' => $portal->getId(),
            'userId' => $user->getItemID(),
        ]);

        $this->addFlash('notYetImplemented', $returnUrl);

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

        if($form->isSubmitted()){

            if($form->get('save')->isClicked()){ //TODO: $form->isValid() returns false, even if it is.

                $user = $userService->getUser($request->get('userId'));
                $formData = $form->getData();
                $newUser = $user->cloneData();
                $choiceWorkspaceId = $form->get('workspaceSelection')->getViewData();
                $projectRoomManager = $legacyEnvironment->getEnvironment()->getProjectManager();
                $newAssignedRoom = $projectRoomManager->getItem($choiceWorkspaceId);
                $newUser->setContextID($newAssignedRoom->getItemID());
                $newUser->setUserComment($formData->getDescriptionOfParticipation());
                $newUser->save();

            }elseif($form->get('search')->isClicked()){
                $user = $userService->getUser($request->get('userId'));
                $userAssignWorkspace = new PortalUserAssignWorkspace();
                $userAssignWorkspace->setUserID($user->getUserID());
                $userAssignWorkspace->setName($user->getFullName());
                $userAssignWorkspace->setWorkspaceSelection('0');

                $formData = $form->getData();

                $form = $this->createForm(AccountIndexDetailAssignWorkspaceType::class, $userAssignWorkspace);

                $projectRoomManager = $legacyEnvironment->getEnvironment()->getProjectManager();
                $projectRooms = $projectRoomManager->getRoomsByTitle($formData->getSearchForWorkspace(), $portal->getId());

                if($projectRooms->getCount()< 1){
                    $repository = $this->getDoctrine()->getRepository(Room::class);
                    $projectRooms = $repository->findAll();

                }

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

    private function sendUserInfoMail($userIds, $action, \cs_user_item $user, \Swift_Mailer $mailer, UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $fromAddress = $user->getEmail();
        $currentUser = $user;
        $fromSender = $user->getFullName();

        $validator = new EmailValidator();
        $replyTo = [];
        $currentUserEmail = $currentUser->getEmail();
        if ($validator->isValid($currentUserEmail, new RFCValidation())) {
            if ($currentUser->isEmailVisible()) {
                $replyTo[$currentUserEmail] = $currentUser->getFullName();
            }
        }

        $users = [];
        $failedUsers = [];
        foreach ($userIds as $userId) {
            $user = $userService->getUser($userId);

            $userEmail = $user->getEmail();
            if (!empty($userEmail) && $validator->isValid($userEmail, new RFCValidation())) {
                $to = [$userEmail => $user->getFullname()];
                $subject = $this->generateSubject($legacyEnvironment, $action);
                $body = $this->generateBody($user, $action, $legacyEnvironment);

                $mailMessage = (new \Swift_Message())
                    ->setSubject($subject)
                    ->setBody($body, 'text/plain')
                    ->setFrom([$fromAddress => $fromSender])
                    ->setReplyTo($replyTo);

                if ($user->isEmailVisible()) {
                    $mailMessage->setTo($to);
                } else {
                    $mailMessage->setBcc($to);
                }

                // send mail
                $failedRecipients = [];
                $mailer->send($mailMessage, $failedRecipients);
            } else {
                $failedUsers[] = $user;
            }
        }

        foreach ($failedUsers as $failedUser) {
            $this->addFlash('failedRecipients', $failedUser->getUserId());
        }

        foreach ($failedRecipients as $failedRecipient) {
            $failedUser = array_filter($users, function($user) use ($failedRecipient) {
                return $user->getEmail() == $failedRecipient;
            });

            if ($failedUser) {
                $this->addFlash('failedRecipients', $failedUser[0]->getUserId());
            }
        }
    }

    public function generateSubject($legacyEnvironment, $action)
    {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();
        $legacyTranslator = $legacyEnvironment->getTranslationObject();
        $room = $legacyEnvironment->getCurrentContextItem();

        switch ($action) {
            case 'user-delete':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_DELETE', $room->getTitle());

                break;

            case 'user-block':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_LOCK', $room->getTitle());

                break;

            case 'user-confirm':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE', $room->getTitle());

                break;

            case 'user-status-user':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_STATUS_USER', $room->getTitle());

                break;

            case 'user-status-moderator':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_STATUS_MODERATOR', $room->getTitle());

                break;

            case 'user-status-reading-user':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_STATUS_READ_ONLY_USER', $room->getTitle());

                break;

            case 'user-contact':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_MAKE_CONTACT_PERSON', $room->getTitle());

                break;

            case 'user-contact-remove':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_UNMAKE_CONTACT_PERSON', $room->getTitle());

                break;

            case 'user-account-merge':
                $subject = $legacyTranslator->getMessage('MAIL_CHOICE_USER_ACCOUNT_MERGE', $room->getTitle());

                break;

            case 'user-account_password':
                $subject = $legacyTranslator->getMessage('MAIL_CHOICE_USER_ACCOUNT_PASSWORD', $room->getTitle());

                break;
        }

        return $subject;
    }

    private function getPicture(){

    }

    private function generateBody($user, $action, $legacyEnvironment)
    {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();
        $legacyTranslator = $legacyEnvironment->getTranslationObject();
        $room = $legacyEnvironment->getCurrentContextItem();

        $body = $legacyTranslator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
        $body .= "\n\n";

        $moderator = $legacyEnvironment->getCurrentUserItem();

        $absoluteRoomUrl = $this->generateUrl('app_room_home', [
            'roomId' => $legacyEnvironment->getCurrentContextID(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        switch ($action) {
            case 'user-delete':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE', $user->getUserID(), $room->getTitle());

                break;

            case 'user-block':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK', $user->getUserID(), $room->getTitle());

                break;

            case 'user-confirm':
            case 'user-status-user':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_USER', $user->getUserID(), $room->getTitle());

                break;

            case 'user-status-moderator':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR', $user->getUserID(), $room->getTitle());

                break;

            case 'user-status-reading-user':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_READ_ONLY', $user->getUserID(), $room->getTitle());

                break;

            case 'user-contact':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON', $user->getUserID(), $room->getTitle());

                break;

            case 'user-contact-remove':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON', $user->getUserID(), $room->getTitle());

                break;
            case 'user-account-merge':
                $sameIDsPerRoom = [];
                $relatedUsers = $user->getRelatedUserList();
                foreach($relatedUsers as $relatedUser){
                    if($relatedUser->isRoomMember()){
                        array_push($sameIDsPerRoom, $relatedUser->getUserID());
                    }
                }
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_MERGE_PO',$user->getEmail(), $room->getTitle(), implode(", ", $sameIDsPerRoom));

                break;

            case 'user-account_password':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_PASSWORD_PO', $room->getTitle(), $user->getUserID());

                break;

            case 'user-account_send_mail':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_CIAO_PO', $room->getTitle(), $user->getUserID());

                break;
        }

        $body .= "\n\n";
        $body .= $absoluteRoomUrl;
        $body .= "\n\n";
        $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_CIAO', $moderator->getFullname(), $room->getTitle());

        return $body;
    }
}
