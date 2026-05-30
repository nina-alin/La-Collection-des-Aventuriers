<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GoogleOAuthAccountLinkingTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testExistingEmailPasswordUserLinkingPersistsGoogleId(): void
    {
        $user = new User();
        $user->setEmail('existing@example.com');
        $user->setPseudo('existing_user');
        $user->setPassword('$2y$13$hash');
        $user->setIsEmailVerified(true);

        $this->em->persist($user);
        $this->em->flush();

        $user->setGoogleId('google-123');
        $user->setDisplayName('Existing User');
        $user->setAvatarUrl('https://example.com/avatar.jpg');
        $user->setIsEmailVerified(true);
        $this->em->flush();

        $this->em->clear();
        $refreshed = $this->em->getRepository(User::class)->findOneBy(['email' => 'existing@example.com']);

        $this->assertSame('google-123', $refreshed->getGoogleId());
        $this->assertTrue($refreshed->isEmailVerified());

        $this->em->remove($refreshed);
        $this->em->flush();
    }
}
