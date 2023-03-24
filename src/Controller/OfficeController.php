<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Files;
use App\Repository\FilesRepository;
use App\Utils\FileService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OfficeController extends AbstractController
{
    #[Route(path: '/room/{roomId}/announcement/{itemId}/office')]
    public function office(
        FilesRepository $filesRepository,
        HttpClientInterface $httpClient
    ): Response
    {
        // file
        /** @var Files $file */
        $file = $filesRepository->find(47);

        // WOPI discovery
        $discoveryEndpoint = 'http://onlyoffice/hosting/discovery';
        $discoveryContent = $httpClient->request('GET', $discoveryEndpoint)->getContent();

        $serializer = new Serializer([new ObjectNormalizer()], [new XmlEncoder()]);
        $discovery = $serializer->decode($discoveryContent, 'xml');

        // build action url
        $wordApp = $discovery['net-zone']['app'][0];
        $wordEditAction = array_values(array_filter($wordApp['action'], fn($action) => $action['@name'] === 'edit' && $action['@ext'] === 'docx'))[0];
        $actionUrl = $wordEditAction['@urlsrc'];
        $wopiSource = "http://caddy/api/wopi/files/{$file->getFilesId()}";
        $actionUrl = str_replace('http://onlyoffice', 'http://localhost:8080', $actionUrl);
        $placeholder = [
            'DC_LLCC' => 'de-DE',
            'DISABLE_CHAT' => 1,
            'EMBEDDED' => 'true',
            'FULLSCREEN' => 'true',
            'HOST_SESSION_ID' => 'commsy',
            'RECORDING' => 'false',
            'SESSION_CONTEXT' => 'session context',
            'THEME_ID' => '1',
            'UI_LLCC' => 'de-DE',
            'WOPI_SOURCE' => $wopiSource,
        ];
        $placeholder = array_map(fn ($el) => urlencode($el), $placeholder);

        $actionUrl = preg_replace_callback('/<(.*?)=(.*?)(&+)>/', function ($matches) use ($placeholder) {
            return $matches[1] . '=' . $placeholder[$matches[2]] . $matches[3];
        }, $actionUrl);

        return $this->render('office/host.html.twig', [
            'actionUrl' => $actionUrl,
            'access_token' => 'some token',
            'access_token_ttl' => 10 * 60 * 60 * 1000,
        ]);
    }

    #[Route(path: '/api/wopi/files/{fileId}', methods: ['GET'])]
    public function wopiCheckFileInfo(
        FilesRepository $filesRepository,
        int $fileId
    ): Response
    {
        error_log('CheckFileInfo');

        /** @var Files $file */
        $file = $filesRepository->find($fileId);

        return $this->json([
            'BaseFileName' => $file->getFilename(),
            'OwnerId' => $file->getCreatorId(),
            //'Size' => $file->getSize(),
            //'UserId' => 'my user id',
            'Version' => uniqid(),

            'UserFriendlyName' => 'Christoph',

            'ReadOnly' => false,
            'UserCanWrite' => true,
        ]);
    }

    #[Route(path: '/api/wopi/files/{fileId}', methods: ['POST'])]
    public function wopiLock(
        FilesRepository $filesRepository,
        int $fileId
    ): Response
    {
        error_log('Lock');

        return $this->json([]);
    }

    #[Route(path: '/api/wopi/files/{fileId}/contents', methods: ['GET'])]
    public function wopiGetFile(
        FilesRepository $filesRepository,
        FileService $fileService,
        int $fileId
    ): Response
    {
        error_log('GetFile');

        /** @var Files $file */
        $file = $filesRepository->find($fileId);

        $absolutePath = $fileService->makeAbsolute($file);

        return new BinaryFileResponse($absolutePath);
    }

    #[Route(path: '/api/wopi/files/{fileId}/contents', methods: ['POST'])]
    public function wopiPutFile(
        Request $request,
        FilesRepository $filesRepository,
        FileService $fileService,
        int $fileId
    ): Response
    {
        error_log('PutFile');

        /** @var Files $file */
        $file = $filesRepository->find($fileId);

        $absolutePath = $fileService->makeAbsolute($file);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($absolutePath, $request->getContent());

        return $this->json([]);
    }
}
