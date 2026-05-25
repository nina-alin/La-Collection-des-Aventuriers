<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\CollectionRepository;
use App\Service\CollectionSlugger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

class CollectionSluggerTest extends TestCase
{
    private CollectionRepository&MockObject $repo;
    private CollectionSlugger $slugger;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(CollectionRepository::class);
        $this->slugger = new CollectionSlugger(new AsciiSlugger(), $this->repo);
    }

    public function testNormalSlugNoCollision(): void
    {
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $result = $this->slugger->generateUnique('Défis Fantastiques');
        $this->assertSame('defis-fantastiques', $result);
    }

    public function testFirstCollision(): void
    {
        $this->repo->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(
                new \stdClass(),
                null,
            );
        $result = $this->slugger->generateUnique('Défis Fantastiques');
        $this->assertSame('defis-fantastiques-2', $result);
    }

    public function testTwoConsecutiveCollisions(): void
    {
        $this->repo->expects($this->exactly(3))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(
                new \stdClass(),
                new \stdClass(),
                null,
            );
        $result = $this->slugger->generateUnique('Défis Fantastiques');
        $this->assertSame('defis-fantastiques-3', $result);
    }
}
