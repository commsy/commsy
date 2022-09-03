<?php
namespace Tests\Unit\Cron\Tasks;

use App\Cron\Tasks\CronUpdateActivityState;
use App\Entity\Account;
use App\Entity\Room;
use App\Entity\ZzzRoom;
use App\Repository\AccountsRepository;
use App\Repository\RoomRepository;
use App\Repository\ZzzRoomRepository;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Tests\Support\UnitTester;

class CronUpdateActivityStateTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testCronDeliversMessages()
    {
        $accountRepositoryStub = $this->makeEmpty(AccountsRepository::class, [
            'findAllExceptRoot' => [$this->make(Account::class, ['id' => 1])],
        ]);
        $roomRepositoryStub = $this->makeEmpty(RoomRepository::class, [
            'findAll' => [$this->make(Room::class, ['itemId' => 1])],
        ]);
        $zzzRoomRepositoryStub = $this->makeEmpty(ZzzRoomRepository::class, [
            'findAll' => [$this->make(ZzzRoom::class, ['itemId' => 2])],
        ]);
        $messageBus = $this->tester->grabService('messenger.default_bus');

        $cronTask = new CronUpdateActivityState(
            $accountRepositoryStub,
            $roomRepositoryStub,
            $zzzRoomRepositoryStub,
            $messageBus
        );

        $cronTask->run(new DateTimeImmutable());

        /** @var InMemoryTransport $transport */
        $transport = $this->tester->grabService('messenger.transport.async');
        $this->tester->assertCount(2, $transport->getSent());
    }
}
