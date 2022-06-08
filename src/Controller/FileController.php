<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Entity\Server;
use App\Services\LegacyEnvironment;
use App\Utils\FileService;
use App\Utils\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sylius\Bundle\ThemeBundle\Context\SettableThemeContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Handler\DownloadHandler;

class FileController extends AbstractController
{
    /**
     * @Route("/file/{fileId}/{disposition}")
     * @Security("is_granted('FILE_DOWNLOAD', fileId)")
     * @param FileService $fileService
     * @param RoomService $roomService
     * @param int $fileId
     * @param string $disposition
     * @return Response
     */
    public function getFileAction(
        FileService $fileService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        ParameterBagInterface $params,
        int $fileId,
        string $disposition = 'attachment'
    ) {
        $file = $fileService->getFile($fileId);
        $rootDir = $params->get('kernel.root_dir') . '/';

        // fix for archived rooms
        if (!$file->getPortalID()) {
            $roomItem = $roomService->getArchivedRoomItem($file->getContextID());

            if ($roomItem) {
                $file->setPortalID($roomItem->getContextId());
            }
        }
        // ~fix for archived rooms

        if (file_exists($rootDir . $file->getDiskFileName())) {
            $content = file_get_contents($rootDir . $file->getDiskFileName());
        } else {
            // fix for userrooms
            if ($legacyEnvironment->getEnvironment()->getCurrentContextItem()->getType() == 'userroom') {
                $file->setPortalID($legacyEnvironment->getEnvironment()->getCurrentPortalID());
            }
            $content = file_get_contents($rootDir . $file->getDiskFileName());
            if (!file_exists($rootDir . $file->getDiskFileName())) {
                throw $this->createNotFoundException('The requested file does not exist');
            }
        }
        $response = new Response($content, Response::HTTP_OK, array('content-type' => $file->getMime()));

        $fileName = $file->getFileName();

        // NOTE: makeDisposition() (which generates the HTTP header's Content-Disposition field-value) requires a fallback filename
        // (for legacy user agents that do not support the "filename*" form); the fallback filename must be ASCII-only and must not
        // contain any percent characters or path separators, thus we strip these characters here
        $fallbackFileName = str_replace(array('%', '/', '\\'), '', $fileName);
        $fallbackFileName = mb_convert_encoding($fallbackFileName, 'US-ASCII', 'UTF-8');

        if ($disposition == 'inline') {
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName,
                $fallbackFileName);
        } else {
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName, $fallbackFileName);
        }
        $response->headers->set('Content-Disposition', $contentDisposition);

        return $response;
    }


    /**
     * @Route("/room/{roomId}/logo", name="getLogo")
     * @param RoomService $roomService
     * @param int $roomId
     * @return Response
     */
    public function getLogoAction(
        RoomService $roomService,
        int $roomId
    ) {
        $roomItem = $roomService->getRoomItem($roomId);

        $fileName = $roomItem->getLogoFilename();
        $filePath = $this->getParameter('files_directory') . "/" . $roomService->getRoomFileDirectory($roomId) . "/" . $fileName;

        if (file_exists($filePath)) {
            if (!$fileName) {
                $fileName = "customBgPlaceholder.png";
                $filePath = $this->getParameter("themes_directory") . "/" . $fileName;
            }
            $content = file_get_contents($filePath);

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);

            $response = new Response($content, Response::HTTP_OK, array('content-type' => $mimeType));
            $response->headers->set('Content-Disposition',
                $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName));
        } else {
            $response = new Response("Logo image not found!", Response::HTTP_NOT_FOUND);
        }

        return $response;
    }

    /**
     * @Route(
    "/room/{roomId}/{imageType}/background/",
    name="getBackground",
    defaults={"imageType": "theme"},
    requirements={
    "imageType": "custom|theme"
    }
    )
     * @param RoomService $roomService
     * @param int $roomId
     * @param $imageType
     * @return Response
     * @return Response
     */
    public function getBackgroundImageAction(
        RoomService $roomService,
        int $roomId,
        $imageType,
        SettableThemeContext $themeContext
    ) {
        $roomItem = $roomService->getRoomItem($roomId);
        $filename = $roomItem->getBGImageFilename();
        $themesDir = $this->getParameter("themes_directory");

        if ($imageType == 'theme') {
            $currentTheme = $themeContext->getTheme();
            $themeName = $currentTheme ? substr($currentTheme->getName(), 7) : 'default';
            $completePath = $themesDir . "/" . $themeName . "/bg.jpg";

            if (!file_exists($completePath)) {
                $completePath = $themesDir . "/" . mb_strtolower($roomItem->getColorArray()['schema']) . "/bg.jpg";
            }
        } elseif ($imageType == 'custom') {
            if ($filename != '') {
                $filepath = $this->getParameter('files_directory') . "/" . $roomService->getRoomFileDirectory($roomId);
                $completePath = $filepath . "/" . $filename;
            } else {
                $completePath = $themesDir . "/customBgPlaceholder.png";
            }
        }

        if (file_exists($completePath)) {
            $content = file_get_contents($completePath);

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $completePath);

            $response = new Response($content, Response::HTTP_OK, array('content-type' => $mimeType));
            $response->headers->set('Content-Disposition',
                $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename));
        } else {
            $response = new Response("Background image not found!", Response::HTTP_NOT_FOUND);
        }

        return $response;
    }

    /**
     * @Route("/theme/{theme}/background", name="getThemeBackground")
     * @param $theme
     * @return Response
     */
    public function getThemeBackgroundAction(
        $theme
    ): Response {
        $themesDir = $this->getParameter("themes_directory");
        $filePath = $themesDir . "/" . $theme . "/bg.jpg";

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);

            $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, 'bg.jpg');

            $response = new Response($content, Response::HTTP_OK, ['content-type' => $mimeType]);
            $response->headers->set('Content-Disposition', $disposition);
        } else {
            $response = new Response("Could not find background image for selected theme!", Response::HTTP_NOT_FOUND);
        }
        return $response;
    }

    /**
     * @Route("/logo/server")
     *
     * @param EntityManagerInterface $entityManager
     * @param DownloadHandler $downloadHandler
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function serverLogo(EntityManagerInterface $entityManager, DownloadHandler $downloadHandler)
    {
        $server = $entityManager->getRepository(Server::class)->getServer();
        if (!$server->getLogoImageFile()) {
            throw $this->createNotFoundException('logo not found');
        }

        return $downloadHandler->downloadObject($server, 'logoImageFile', null, null, false);
    }

    /**
     * @Route("/logo/portal/{portalId}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     *
     * @param EntityManagerInterface $entityManager
     * @param DownloadHandler $downloadHandler
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function portalLogo(Portal $portal, EntityManagerInterface $entityManager, DownloadHandler $downloadHandler)
    {
        if (!$portal->getLogoFile()) {
            throw $this->createNotFoundException('logo not found');
        }
        return $downloadHandler->downloadObject($portal, 'logoFile', null, null, false);
    }
}