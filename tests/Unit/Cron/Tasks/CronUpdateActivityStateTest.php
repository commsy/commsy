<?php
namespace App\Tests\Cron\Tasks;

use App\Cron\Tasks\CronUpdateActivityState;
use App\Entity\Account;
use App\Entity\Room;
use App\Entity\ZzzRoom;
use App\Repository\AccountsRepository;
use App\Repository\RoomRepository;
use App\Repository\ZzzRoomRepository;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

class CronUpdateActivityStateTest extends Unit
{
    /**
     * @var \App\Tests\UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testCronCallsApply()
    {
        $accountRepositoryStub = $this->makeEmpty(AccountsRepository::class, [
            'findAll' => [$this->make(Account::class)],
        ]);
        $roomRepositoryStub = $this->makeEmpty(RoomRepository::class, [
            'findAll' => [$this->make(Room::class)],
        ]);
        $zzzRoomRepositoryStub = $this->makeEmpty(ZzzRoomRepository::class, [
            'findAll' => [$this->make(ZzzRoom::class)],
        ]);
        $entityManagerStub = $this->makeEmpty(EntityManagerInterface::class);
        $workflowAccountActivityStateMachineStub = $this->makeEmpty(WorkflowInterface::class, [
            'getEnabledTransitions' => [
                $this->make(Transition::class, [
                    'name' => 'test',
                ])
            ],
            'can' => true,
            'apply' => Expected::once(),
        ]);
        $workflowRoomActivityStateMachineStub = $this->makeEmpty(WorkflowInterface::class, [
            'getEnabledTransitions' => [
                $this->make(Transition::class, [
                    'name' => 'test',
                ])
            ],
            'can' => true,
            'apply' => Expected::exactly(2),
        ]);

        $cronTask = new CronUpdateActivityState(
            $accountRepositoryStub,
            $roomRepositoryStub,
            $zzzRoomRepositoryStub,
            $entityManagerStub,
            $workflowAccountActivityStateMachineStub,
            $workflowRoomActivityStateMachineStub
        );

        $cronTask->run(new DateTimeImmutable());
    }
}