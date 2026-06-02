<?php

namespace App\Tests\Notification\Entity;

use App\Entity\Collection;
use App\Entity\User;
use App\Entity\UserCollectionSubscription;
use PHPUnit\Framework\TestCase;

class UserCollectionSubscriptionTest extends TestCase
{
    public function testConstructorSetsUserAndCollection(): void
    {
        $user = $this->createMock(User::class);
        $collection = $this->createMock(Collection::class);

        $sub = new UserCollectionSubscription($user, $collection);

        $this->assertSame($user, $sub->getUser());
        $this->assertSame($collection, $sub->getCollection());
    }

    public function testCreatedAtIsUtcDateTimeImmutable(): void
    {
        $sub = new UserCollectionSubscription(
            $this->createMock(User::class),
            $this->createMock(Collection::class),
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $sub->getCreatedAt());
        $this->assertSame('UTC', $sub->getCreatedAt()->getTimezone()->getName());
    }
}
