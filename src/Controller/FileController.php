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

namespace App\Controller;

use App\Entity\Portal;
use App\Entity\Server;
use App\Repository\FilesRepository;
use App\Utils\FileService;
use App\Utils\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Sylius\Bundle\ThemeBundle\Context\SettableThemeContext;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Handler\DownloadHandler;

class FileController extends AbstractController
{
    #[Route(path: '/file/{fileId}/{disposition}')]
    #[IsGranted('FILE_DOWNLOAD', subject: 'fileId')]
    public function getFile(
        Request $request,
        FileService $fileService,
        FilesRepository $filesRepository,
        int $fileId,
        string $disposition = 'attachment'
    ): Response {
        $file = $filesRepository->findOneBy(['filesId' => $fileId]);
        if (!$file) {
            throw $this->createNotFoundException('The requested file does not exist');
        }

        $absolutePath = $fileService->makeAbsolute($file);
        if (!file_exists($absolutePath)) {
            throw $this->createNotFoundException('The requested file does not exist');
        }

        $response = new BinaryFileResponse($absolutePath);

        $fileName = $file->getFileName();

        // NOTE: makeDisposition() (which generates the HTTP header's Content-Disposition field-value) requires a fallback filename
        // (for legacy user agents that do not support the "filename*" form); the fallback filename must be ASCII-only and must not
        // contain any percent characters or path separators, thus we strip these characters here
        $fallbackFileName = str_replace(['%', '/', '\\'], '', (string) $fileName);
        $fallbackFileName = mb_convert_encoding($fallbackFileName, 'US-ASCII', 'UTF-8');

        $contentDisposition = $response->headers->makeDisposition(
            ('inline' === $disposition) ?
                ResponseHeaderBag::DISPOSITION_INLINE :
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName,
            $fallbackFileName
        );

        $response->headers->set('Content-Disposition', $contentDisposition);
        $response->prepare($request);

        return $response;
    }

    #[Route(path: '/room/{roomId}/logo', name: 'getLogo')]
    public function getLogo(
        RoomService $roomService,
        int $roomId
    ): Response {
        $roomItem = $roomService->getRoomItem($roomId);

        $fileName = $roomItem->getLogoFilename();
        $filePath = $this->getParameter('files_directory').'/'.$roomService->getRoomFileDirectory($roomId).'/'.$fileName;

        if (file_exists($filePath)) {
            if (!$fileName) {
                $fileName = 'customBgPlaceholder.png';
                $filePath = $this->getParameter('themes_directory').'/'.$fileName;
            }
            $content = file_get_contents($filePath);

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);

            $response = new Response($content, Response::HTTP_OK, ['content-type' => $mimeType]);
            $response->headers->set('Content-Disposition',
                $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName));
        } else {
            $response = new Response('Logo image not found!', Response::HTTP_NOT_FOUND);
        }

        return $response;
    }

    #[Route(path: '/room/{roomId}/{imageType}/background/', name: 'getBackground', defaults: ['imageType' => 'theme'], requirements: ['imageType' => 'custom|theme'])]
    public function getBackgroundImage(
        RoomService $roomService,
        int $roomId,
        string $imageType,
        SettableThemeContext $themeContext
    ): Response {
        $completePath = null;
        $roomItem = $roomService->getRoomItem($roomId);
        $filename = $roomItem->getBGImageFilename();
        $themesDir = $this->getParameter('themes_directory');

        if ('theme' == $imageType) {
            $currentTheme = $themeContext->getTheme();
            $themeName = $currentTheme ? substr($currentTheme->getName(), 7) : 'default';
            $completePath = $themesDir.'/'.$themeName.'/bg.jpg';

            if (!file_exists($completePath)) {
                $completePath = $themesDir.'/'.mb_strtolower((string) $roomItem->getColorArray()['schema']).'/bg.jpg';
            }
        } elseif ('custom' == $imageType) {
            if ('' != $filename) {
                $filepath = $this->getParameter('files_directory').'/'.$roomService->getRoomFileDirectory($roomId);
                $completePath = $filepath.'/'.$filename;
            } else {
                $completePath = $themesDir.'/customBgPlaceholder.png';
            }
        }

        if (file_exists($completePath)) {
            $content = file_get_contents($completePath);

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $completePath);

            $response = new Response($content, Response::HTTP_OK, ['content-type' => $mimeType]);
            $response->headers->set('Content-Disposition',
                $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename));
        } else {
            $response = new Response('Background image not found!', Response::HTTP_NOT_FOUND);
        }

        return $response;
    }

    #[Route(path: '/theme/{theme}/background', name: 'getThemeBackground')]
    public function getThemeBackground(
        $theme
    ): Response {
        $themesDir = $this->getParameter('themes_directory');
        $filePath = $themesDir.'/'.$theme.'/bg.jpg';

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);

            $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, 'bg.jpg');

            $response = new Response($content, Response::HTTP_OK, ['content-type' => $mimeType]);
            $response->headers->set('Content-Disposition', $disposition);
        } else {
            $response = new Response('Could not find background image for selected theme!', Response::HTTP_NOT_FOUND);
        }

        return $response;
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route(path: '/logo/server')]
    public function serverLogo(EntityManagerInterface $entityManager, DownloadHandler $downloadHandler): StreamedResponse
    {
        $server = $entityManager->getRepository(Server::class)->getServer();
        if (!$server->getLogoImageFile()) {
            throw $this->createNotFoundException('logo not found');
        }

        return $downloadHandler->downloadObject($server, 'logoImageFile', null, null, false);
    }

    /**
     * @return StreamedResponse
     *
     */
    #[Route(path: '/logo/portal/{portalId}')]
    public function portalLogo(
        #[MapEntity(id: 'portalId')]
        Portal $portal,
        DownloadHandler $downloadHandler
    ): Response
    {
        if (!$portal->getLogoFile()) {
            throw $this->createNotFoundException('logo not found');
        }

        return $downloadHandler->downloadObject($portal, 'logoFile', null, null, false);
    }
}
