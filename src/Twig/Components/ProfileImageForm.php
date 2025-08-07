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

namespace App\Twig\Components;

use App\Entity\Account;
use App\Files\ProfileHelper;
use App\Form\Type\Profile\RoomProfileGeneralType;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_room_item;
use cs_user_item;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
final class ProfileImageForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public int $userId;

    #[LiveProp]
    public ?array $formData = null;

    public function __construct(
        private readonly UserService $userService,
        private readonly RoomService $roomService,
        private readonly CropperInterface $cropper,
        private readonly ProfileHelper $profileHelper,
    ) {}

    #[PostMount]
    public function postMount(): void
    {
        $user = $this->userService->getUser($this->userId);

        if (!$this->formData) {
            /** @var ?Account $account */
            $account = $this->getUser();
            $imagePath = $this->profileHelper->getTempProfileImagePath($account, $user->getContextID());

            $this->formData = [
                'useProfileImage' => !empty($user->getPicture()) || file_exists($imagePath),
            ];
        }
    }

    #[ExposeInTemplate]
    public function getUserItem(): cs_user_item
    {
        return $this->userService->getUser($this->userId);
    }

    #[ExposeInTemplate]
    public function getRoomItem(): cs_room_item
    {
        return $this->roomService->getRoomItem($this->getUserItem()->getContextID());
    }

    protected function instantiateForm(): FormInterface
    {
        /** @var ?Account $account */
        $account = $this->getUser();
        $user = $this->userService->getUser($this->userId);

        $imagePath = $this->profileHelper->getTempProfileImagePath($account, $user->getContextID());
        $crop = $this->cropper->createCrop($imagePath ?? '');
        $crop->setCroppedMaxSize(200, 200);

        $formData = array_merge($this->formData, ['crop' => $crop]);

        return $this->createForm(RoomProfileGeneralType::class, $formData, [
            'uploadUrl' => $this->generateUrl('app_upload_uploadtousertemp', [
                'roomId' => $user->getContextID(),
                'filename' => $this->profileHelper->getProfileImageBaseName($account, $user->getContextID()),
            ]),
            'cropPublicUrl' => $this->generateUrl('app_file_getusertempprofileimage', [
                'contextId' => $user->getContextID(),
            ]),
            'cropPath' => $imagePath,
        ]);
    }

    /**
     * This action is called when upload finished
     */
    #[LiveAction]
    public function refreshFiles(): RedirectResponse
    {
        $user = $this->userService->getUser($this->userId);

        return $this->redirectToRoute('app_profile_general', [
            'roomId' => $user->getContextID(),
            'itemId' => $user->getItemID(),
        ]);
    }
}
