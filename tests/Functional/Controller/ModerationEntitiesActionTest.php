<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Book;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ModerationEntitiesActionTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\BookImage bi')->execute();
        $em->createQuery('DELETE FROM App\Entity\Book b')->execute();
        $em->createQuery('DELETE FROM App\Entity\Editor e')->execute();
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
        $user->setPseudo('mod-' . uniqid());
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

    private function createEditor(): Editor
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $editor = (new Editor())->setName('Test Editor ' . uniqid());
        $em->persist($editor);
        $em->flush();

        return $editor;
    }

    private function createBook(Editor $editor): Book
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $book = new Book();
        $book->setTitle('Test Book ' . uniqid());
        $book->setStatus(BookStatus::PUBLISHED);
        $book->setEditor($editor);
        $em->persist($book);
        $em->flush();

        return $book;
    }

    public function testEntitiesListAsModerator200(): void
    {
        $client = static::createClient();
        $moderator = $this->createModerator();
        $client->loginUser($moderator);

        $client->request('GET', '/moderation/entities');
        $this->assertResponseIsSuccessful();
    }

    public function testEntitiesListUnauthenticatedRedirects(): void
    {
        $client = static::createClient();
        $client->request('GET', '/moderation/entities');
        $this->assertResponseRedirects();
    }

    public function testDeleteEditorAsModerator(): void
    {
        $client = static::createClient();
        $moderator = $this->createModerator();
        $editor = $this->createEditor();
        $editorId = (string) $editor->getId();

        $client->loginUser($moderator);

        $client->request('DELETE', "/moderation/entities/EDITOR/{$editorId}", [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'HTTP_X-CSRF-Token' => $this->getCsrfToken($client, 'delete_entity_' . $editorId),
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $this->assertNull($em->find(Editor::class, $editorId));
    }

    public function testDeleteWithInvalidCsrfReturns403(): void
    {
        $client = static::createClient();
        $moderator = $this->createModerator();
        $editor = $this->createEditor();
        $editorId = (string) $editor->getId();

        $client->loginUser($moderator);

        $client->request('DELETE', "/moderation/entities/EDITOR/{$editorId}", [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'HTTP_X-CSRF-Token' => 'invalid-token',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteWithRoleUserReturns403(): void
    {
        $client = static::createClient();
        $regularUser = $this->createRegularUser();
        $editor = $this->createEditor();
        $editorId = (string) $editor->getId();

        $client->loginUser($regularUser);

        $client->request('DELETE', "/moderation/entities/EDITOR/{$editorId}", [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteNotFoundReturns404(): void
    {
        $client = static::createClient();
        $moderator = $this->createModerator();
        $client->loginUser($moderator);

        $fakeId = '999999';

        $client->request('DELETE', "/moderation/entities/EDITOR/{$fakeId}", [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'HTTP_X-CSRF-Token' => $this->getCsrfToken($client, 'delete_entity_' . $fakeId),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDepublishReturns422(): void
    {
        $client = static::createClient();
        $moderator = $this->createModerator();
        $editor = $this->createEditor();
        $editorId = (string) $editor->getId();

        $client->loginUser($moderator);

        $client->request('PATCH', "/moderation/entities/EDITOR/{$editorId}/depublish", [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'HTTP_X-CSRF-Token' => $this->getCsrfToken($client, 'delete_entity_' . $editorId),
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    private function getCsrfToken(object $client, string $tokenId): string
    {
        $client->request('GET', '/test-tokens/csrf/' . $tokenId);
        return json_decode($client->getResponse()->getContent(), true)['token'];
    }
}
