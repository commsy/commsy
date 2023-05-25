<?php
namespace Tests\Support;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Files;
use App\Entity\Portal;
use App\Entity\Room;
use App\Repository\FilesRepository;
use Codeception\Actor;
use DateTime;
use DateTimeInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class ApiTester extends Actor
{
    use _generated\ApiTesterActions;

    public function havePortal(string $title, AuthSource $authSource = null): Portal
    {
        $authSource = $authSource ?: new AuthSourceLocal();
        $this->haveInRepository($authSource, [
            'title' => 'Lokal',
            'enabled' => true,
            'default' => true,
            'createRoom' => true,
            'description' => 'desc',
        ]);

        $portal = new Portal();
        $portal->addAuthSource($authSource);
        $this->haveInRepository($portal, [
            'title' => $title,
            'status' => 1,
            'descriptionGerman' => 'descDE',
            'descriptionEnglish' => 'descEN',
        ]);

        return $portal;
    }

    public function haveRoom(string $title, Portal $portal): Room
    {
        $room = new Room();
        $this->haveInRepository($room, [
            'contextId' => $portal->getId(),
            'creator_id' => 99,
            'modifier_id' => 99,
            'title' => $title,
            'status' => 1,
            'archived' => false,
            'roomDescription' => 'desc',
        ]);

        return $room;
    }

    public function haveAccount(AuthSource $authSource, string $username, string $password): Account
    {
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $this->grabService(UserPasswordHasherInterface::class);

        $account = new Account();
        $this->haveInRepository($account, [
            'contextId' => $authSource->getPortal()->getId(),
            'authSource' => $authSource,
            'username' => $username,
            'email' => 'some@mail.example',
            'password' => $passwordHasher->hashPassword($account, $password),
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'language' => 'de',
        ]);

        return $account;
    }

    public function haveFile(
        Portal $portal,
        ?string $lockingId = null,
        ?DateTimeInterface $lockingDate = null,
        string $filename = 'file.docx'): Files
    {
        $fileId = $this->haveInRepository(Files::class, [
            'contextId' => 999,
            'creationDate' => new DateTime(),
            'filename' => $filename,
            'filepath' => "files/{$portal->getId()}/999_/$filename",
            'lockingId' => $lockingId,
            'lockingDate' => $lockingDate,
            'portal' => $portal,
        ]);

        /** @var FilesRepository $fileRepository */
        $fileRepository = $this->grabService(FilesRepository::class);
        return $fileRepository->find($fileId);
    }

    public function amFullAuthenticated()
    {
        $this->sendPostAsJson('/v2/login_check', [
            'username' => 'api_write',
            'password' => 'apiwrite',
        ]);
        $token = $this->grabDataFromResponseByJsonPath('$.token')[0];
        $this->amBearerAuthenticated($token);
    }

    public function amReadOnlyAuthenticated()
    {
        $this->sendPostAsJson('/v2/login_check', [
            'username' => 'api_read',
            'password' => 'apiread',
        ]);
        $token = $this->grabDataFromResponseByJsonPath('$.token')[0];
        $this->amBearerAuthenticated($token);
    }
}
