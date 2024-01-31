<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Files;
use App\WOPI\ActionUrlBuilder;
use App\WOPI\Auth\AccessTokenGenerator;
use App\WOPI\Discovery\DiscoveryService;
use App\WOPI\Permission\PermissionResolver;
use App\WOPI\Permission\WOPIPermission;
use App\WOPI\REST\WOPIFileId;
use App\WOPI\REST\WOPISrc;
use DateTimeImmutable;
use Exception;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\LocaleSwitcher;

class WOPIController extends AbstractController
{
    #[Route(path: '/room/{roomId}/item/{itemId}/file/{fileId}/wopi')]
    #[IsGranted('ITEM_ENTER', subject: 'roomId')]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
    public function host(
        DiscoveryService $discoveryService,
        PermissionResolver $permissionResolver,
        LocaleSwitcher $localeSwitcher,
        WOPISrc $wopiSrc,
        AccessTokenGenerator $tokenGenerator,
        int $roomId,
        int $itemId,
        #[MapEntity(id: 'fileId')]
        Files $file
    ): Response
    {
        $permission = $permissionResolver->resolve($file);

        $discovery = $discoveryService->getWOPIDiscovery();
        $extension = pathinfo($file->getFilepath(), PATHINFO_EXTENSION);
        $app = $discoveryService->findApp($discovery, $extension, $permission->value);
        if (!$app) {
            // fallback to view permission, this might happen on non-editable files like ppt (not pptx) where
            // we might have the permissions to edit the file but the file format only allows for viewing
            // TODO: refactore app and action resolving
            $permission = WOPIPermission::VIEW;
            $app = $discoveryService->findApp($discovery, $extension, $permission->value);
            if (!$app) {
                throw new Exception('No matching app found.');
            }
        }

        $action = $discoveryService->findAction($app, $extension, $permission->value);
        if (!$action) {
            throw new Exception('No matching action found.');
        }

        $actionUrlBuilder = new ActionUrlBuilder();
        $actionUrl = $actionUrlBuilder
            ->setLanguage($localeSwitcher->getLocale())
            ->setDisableChat(true)
            ->setHostSessionId('commsy')
            ->setWOPISource($wopiSrc->getUrl(WOPIFileId::fromCommSyFile($file)))
            ->build($action->getUrlSrc());

        $account = $this->getUser();
        if (!$account instanceof Account) {
            throw new Exception('No valid account found for token generation.');
        }

        $token = $tokenGenerator->generateToken($account, $file, $permission);

        $ttlDate = new DateTimeImmutable("+" . AccessTokenGenerator::TOKEN_VALID_NUM_HOURS . " hour");
        $response = $this->render('wopi/host.html.twig', [
            'actionUrl' => $actionUrl,
            'access_token' => $token,
            'access_token_ttl' => $ttlDate->format('Uv'),
            'favicon_url' => $app->getFavIconUrl(),
        ]);
        $response->setCache(['no_cache' => true, 'no_store' => true,]);
        return $response;
    }
}
