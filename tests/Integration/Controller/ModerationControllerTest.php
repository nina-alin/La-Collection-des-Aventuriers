<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use App\Entity\WorkEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ModerationControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\ModerationLog m')->execute();
        $em->createQuery('DELETE FROM App\Entity\CorrectionProposal c')->execute();
        $em->createQuery('DELETE FROM App\Entity\WorkEntry w')->execute();
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();

        parent::tearDown();
    }

    private function createUser(array $roles, string $email, string $pseudo): User
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function csrfToken(object $client, string $tokenId): string
    {
        $client->request('GET', '/test-tokens/csrf/'.$tokenId);
        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    public function testGetModerationAsModerator(): void
    {
        $client = static::createClient();
        $mod = $this->createUser(['ROLE_MODERATOR'], 'mod@example.com', 'moduser');

        $client->loginUser($mod);
        $client->request('GET', '/moderation');
        $this->assertResponseIsSuccessful();
    }

    public function testRoleUserOnModerationReturns403(): void
    {
        $client = static::createClient();
        $user = $this->createUser(['ROLE_USER'], 'user@example.com', 'regularuser');

        $client->loginUser($user);
        $client->request('GET', '/moderation');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testApproveWorkEntryWithValidCsrf(): void
    {
        $client = static::createClient();
        $mod = $this->createUser(['ROLE_MODERATOR'], 'mod2@example.com', 'moduser2');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $entry = new WorkEntry('Test Entry', $mod);
        $em->persist($entry);
        $em->flush();

        $client->loginUser($mod);
        $client->request('GET', '/moderation');

        $entryId = (string) $entry->getId();
        $client->request('POST', '/moderation/work-entry/'.$entryId.'/approve', [
            '_csrf_token' => $this->csrfToken($client, 'moderate_'.$entryId),
        ]);

        $this->assertResponseRedirects('/moderation');

        $entry = static::getContainer()->get(EntityManagerInterface::class)->find(WorkEntry::class, $entry->getId());
        $this->assertSame('PUBLISHED', $entry->getStatus());
    }

    public function testPostWithInvalidCsrfReturns403(): void
    {
        $client = static::createClient();
        $mod = $this->createUser(['ROLE_MODERATOR'], 'mod3@example.com', 'moduser3');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $entry = new WorkEntry('Test', $mod);
        $em->persist($entry);
        $em->flush();

        $client->loginUser($mod);
        $client->request('POST', '/moderation/work-entry/'.(string)$entry->getId().'/approve', [
            '_csrf_token' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRejectWithReasonPersistsStatus(): void
    {
        $client = static::createClient();
        $mod = $this->createUser(['ROLE_MODERATOR'], 'mod4@example.com', 'moduser4');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $entry = new WorkEntry('Entry to reject', $mod);
        $em->persist($entry);
        $em->flush();

        $client->loginUser($mod);
        $client->request('GET', '/moderation');

        $entryId = (string) $entry->getId();
        $client->request('POST', '/moderation/work-entry/'.$entryId.'/reject', [
            '_csrf_token' => $this->csrfToken($client, 'moderate_'.$entryId),
            'reason' => 'content policy violation',
        ]);

        $this->assertResponseRedirects('/moderation');

        $entry = static::getContainer()->get(EntityManagerInterface::class)->find(WorkEntry::class, $entry->getId());
        $this->assertSame('REJECTED', $entry->getStatus());
    }
}
