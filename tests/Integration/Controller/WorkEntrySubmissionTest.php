<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\CorrectionProposal;
use App\Entity\User;
use App\Entity\WorkEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class WorkEntrySubmissionTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
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

    public function testPostWorkEntryForcesStatusPending(): void
    {
        $client = static::createClient();
        $user = $this->createUser(['ROLE_USER'], 'user@example.com', 'regularuser');

        $client->loginUser($user);
        $client->request('GET', '/');

        $client->request('POST', '/work-entries', [
            'title' => 'My Work Entry',
            'status' => 'PUBLISHED',
            '_csrf_token' => $this->csrfToken($client, 'work_entry_submit'),
        ]);

        $this->assertResponseRedirects('/');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $entries = $em->getRepository(WorkEntry::class)->findBy(['title' => 'My Work Entry']);
        $this->assertCount(1, $entries);
        $this->assertSame('PENDING', $entries[0]->getStatus());
    }

    public function testPostWorkEntryWithInvalidCsrfReturns403(): void
    {
        $client = static::createClient();
        $user = $this->createUser(['ROLE_USER'], 'user2@example.com', 'regularuser2');

        $client->loginUser($user);
        $client->request('POST', '/work-entries', [
            'title' => 'Test',
            '_csrf_token' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPostWorkEntryUnauthenticatedRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('POST', '/work-entries', [
            'title' => 'Test',
            '_csrf_token' => 'any',
        ]);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('connexion', $client->getResponse()->headers->get('Location'));
    }

    public function testPostCorrectionForcesStatusPending(): void
    {
        $client = static::createClient();
        $user = $this->createUser(['ROLE_USER'], 'user3@example.com', 'regularuser3');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $entry = new WorkEntry('Original Entry', $user);
        $em->persist($entry);
        $em->flush();

        $client->loginUser($user);
        $client->request('GET', '/');

        $entryId = (string) $entry->getId();
        $client->request('POST', '/work-entries/'.$entryId.'/corrections', [
            'proposedContent' => 'This should be corrected.',
            '_csrf_token' => $this->csrfToken($client, 'correction_submit_'.$entryId),
        ]);

        $this->assertResponseRedirects('/');

        $proposals = $em->getRepository(CorrectionProposal::class)->findAll();
        $this->assertCount(1, $proposals);
        $this->assertSame('PENDING', $proposals[0]->getStatus());
    }
}
