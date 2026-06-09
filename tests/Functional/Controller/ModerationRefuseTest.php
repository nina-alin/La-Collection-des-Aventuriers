<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\SuggestionEntityType;
use App\Entity\Enum\SuggestionMode;
use App\Entity\Enum\SuggestionStatus;
use App\Entity\Suggestion;
use App\Entity\SuggestionRefusal;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ModerationRefuseTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\SuggestionRefusal sr')->execute();
        $em->createQuery('DELETE FROM App\Entity\Suggestion s')->execute();
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();
        parent::tearDown();
    }

    private function createModerator(): User
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('mod-' . uniqid() . '@example.com');
        $user->setPseudo('moderator-' . uniqid());
        $user->setRoles(['ROLE_MODERATOR']);
        $user->setStatus('active');
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createRegularUser(): User
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('user-' . uniqid() . '@example.com');
        $user->setPseudo('user-' . uniqid());
        $user->setRoles(['ROLE_USER']);
        $user->setStatus('active');
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createSuggestion(User $user): Suggestion
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $suggestion = new Suggestion();
        $suggestion->setUser($user);
        $suggestion->setEntityType(SuggestionEntityType::BOOK);
        $suggestion->setMode(SuggestionMode::CORRECTION);
        $suggestion->setFormData(['title' => 'Test Book']);
        $em->persist($suggestion);
        $em->flush();

        return $suggestion;
    }

    private function getCsrfToken(object $client, string $tokenId): string
    {
        $client->request('GET', '/test-tokens/csrf/' . $tokenId);
        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    public function testRefuseWithXhrAndValidReasonReturns200Json(): void
    {
        $client = static::createClient();
        $moderator = $this->createModerator();
        $user = $this->createRegularUser();
        $suggestion = $this->createSuggestion($user);
        $id = $suggestion->getId()->toRfc4122();

        $client->loginUser($moderator);
        $csrfToken = $this->getCsrfToken($client, 'moderate_' . $id);

        $client->request('POST', "/moderation/suggestion/{$id}/refuse", [
            '_csrf_token' => $csrfToken,
            'reason' => 'Données incorrectes',
        ], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $refreshed = $em->find(Suggestion::class, $suggestion->getId());
        $this->assertSame(SuggestionStatus::REFUSED, $refreshed->getStatus());

        $refusal = $em->getRepository(SuggestionRefusal::class)->findOneBy(['suggestion' => $suggestion->getId()]);
        $this->assertNotNull($refusal);
        $this->assertSame('Données incorrectes', $refusal->getReason());
    }

    public function testRefuseWithMissingReasonReturns422(): void
    {
        $client = static::createClient();
        $moderator = $this->createModerator();
        $user = $this->createRegularUser();
        $suggestion = $this->createSuggestion($user);
        $id = $suggestion->getId()->toRfc4122();

        $client->loginUser($moderator);
        $csrfToken = $this->getCsrfToken($client, 'moderate_' . $id);

        $client->request('POST', "/moderation/suggestion/{$id}/refuse", [
            '_csrf_token' => $csrfToken,
            'reason' => '',
        ], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testRefuseWithoutRoleModeratoryReturns403(): void
    {
        $client = static::createClient();
        $regularUser = $this->createRegularUser();
        $suggestionUser = $this->createRegularUser();
        $suggestion = $this->createSuggestion($suggestionUser);
        $id = $suggestion->getId()->toRfc4122();

        $client->loginUser($regularUser);

        $client->request('POST', "/moderation/suggestion/{$id}/refuse", [
            '_csrf_token' => 'any-token',
            'reason' => 'Raison',
        ], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRefuseWithInvalidCsrfReturns403(): void
    {
        $client = static::createClient();
        $moderator = $this->createModerator();
        $user = $this->createRegularUser();
        $suggestion = $this->createSuggestion($user);
        $id = $suggestion->getId()->toRfc4122();

        $client->loginUser($moderator);

        $client->request('POST', "/moderation/suggestion/{$id}/refuse", [
            '_csrf_token' => 'invalid-token',
            'reason' => 'Raison',
        ], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertResponseStatusCodeSame(403);
    }
}
