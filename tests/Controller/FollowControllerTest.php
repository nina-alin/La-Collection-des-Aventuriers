<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Collection;
use App\Entity\Contributor;
use App\Entity\User;
use App\Entity\UserCollectionSubscription;
use App\Entity\UserFollowedContributor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class FollowControllerTest extends WebTestCase
{
    private function loginUser(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, User $user): void
    {
        $session = static::getContainer()->get('session.factory')->createSession();
        $firewallName = 'main';
        $token = new UsernamePasswordToken($user, $firewallName, $user->getRoles());
        $session->set('_security_' . $firewallName, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }

    // ============================
    // POST /follow/contributor/{id}
    // ============================

    public function testFollowContributorUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $contributor = $em->getRepository(Contributor::class)->findOneBy([]);
        if ($contributor === null) {
            $this->markTestSkipped('No contributor in DB.');
        }

        $client->request('POST', '/follow/contributor/' . $contributor->getId(), [
            '_token' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testFollowContributorNotFoundReturns404(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy([]);
        if ($user === null) {
            $this->markTestSkipped('No user in DB.');
        }

        $this->loginUser($client, $user);

        $fakeId = '00000000-0000-0000-0000-000000000000';
        $client->request('POST', '/follow/contributor/' . $fakeId, [
            '_token' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testFollowContributorInvalidCsrfReturns403(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy([]);
        $contributor = $em->getRepository(Contributor::class)->findOneBy([]);

        if ($user === null || $contributor === null) {
            $this->markTestSkipped('No user or contributor in DB.');
        }

        $this->loginUser($client, $user);

        $client->request('POST', '/follow/contributor/' . $contributor->getId(), [
            '_token' => 'bad_token',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testFollowContributorAuthenticated(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy([]);
        $contributor = $em->getRepository(Contributor::class)->findOneBy([]);

        if ($user === null || $contributor === null) {
            $this->markTestSkipped('No user or contributor in DB.');
        }

        // Remove any existing follow
        $existing = $em->getRepository(UserFollowedContributor::class)->findOneBy([
            'user'        => $user,
            'contributor' => $contributor,
        ]);
        if ($existing !== null) {
            $em->remove($existing);
            $em->flush();
        }

        $this->loginUser($client, $user);

        $csrfToken = static::getContainer()->get('security.csrf.token_manager')
            ->getToken('follow_contributor_' . $contributor->getId())
            ->getValue();

        $client->request('POST', '/follow/contributor/' . $contributor->getId(), [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['followed']);
        $this->assertArrayHasKey('token', $data);

        $em->clear();
        $follow = $em->getRepository(UserFollowedContributor::class)->findOneBy([
            'user'        => $em->find(User::class, $user->getId()),
            'contributor' => $em->find(Contributor::class, $contributor->getId()),
        ]);
        $this->assertNotNull($follow);
    }

    public function testUnfollowContributorAuthenticated(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy([]);
        $contributor = $em->getRepository(Contributor::class)->findOneBy([]);

        if ($user === null || $contributor === null) {
            $this->markTestSkipped('No user or contributor in DB.');
        }

        // Ensure follow exists
        $existing = $em->getRepository(UserFollowedContributor::class)->findOneBy([
            'user'        => $user,
            'contributor' => $contributor,
        ]);
        if ($existing === null) {
            $follow = new UserFollowedContributor($user, $contributor);
            $em->persist($follow);
            $em->flush();
        }

        $this->loginUser($client, $user);

        $csrfToken = static::getContainer()->get('security.csrf.token_manager')
            ->getToken('follow_contributor_' . $contributor->getId())
            ->getValue();

        $client->request('POST', '/follow/contributor/' . $contributor->getId(), [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['followed']);

        $em->clear();
        $follow = $em->getRepository(UserFollowedContributor::class)->findOneBy([
            'user'        => $em->find(User::class, $user->getId()),
            'contributor' => $em->find(Contributor::class, $contributor->getId()),
        ]);
        $this->assertNull($follow);
    }

    // ============================
    // POST /follow/collection/{id}
    // ============================

    public function testFollowCollectionUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $collection = $em->getRepository(Collection::class)->findOneBy([]);
        if ($collection === null) {
            $this->markTestSkipped('No collection in DB.');
        }

        $client->request('POST', '/follow/collection/' . $collection->getId(), [
            '_token' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testFollowCollectionNotFoundReturns404(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy([]);
        if ($user === null) {
            $this->markTestSkipped('No user in DB.');
        }

        $this->loginUser($client, $user);

        $fakeId = '00000000-0000-0000-0000-000000000000';
        $client->request('POST', '/follow/collection/' . $fakeId, [
            '_token' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testFollowCollectionInvalidCsrfReturns403(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy([]);
        $collection = $em->getRepository(Collection::class)->findOneBy([]);

        if ($user === null || $collection === null) {
            $this->markTestSkipped('No user or collection in DB.');
        }

        $this->loginUser($client, $user);

        $client->request('POST', '/follow/collection/' . $collection->getId(), [
            '_token' => 'bad_token',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testFollowCollectionAuthenticated(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy([]);
        $collection = $em->getRepository(Collection::class)->findOneBy([]);

        if ($user === null || $collection === null) {
            $this->markTestSkipped('No user or collection in DB.');
        }

        $existing = $em->getRepository(UserCollectionSubscription::class)->findOneBy([
            'user'       => $user,
            'collection' => $collection,
        ]);
        if ($existing !== null) {
            $em->remove($existing);
            $em->flush();
        }

        $this->loginUser($client, $user);

        $csrfToken = static::getContainer()->get('security.csrf.token_manager')
            ->getToken('follow_collection_' . $collection->getId())
            ->getValue();

        $client->request('POST', '/follow/collection/' . $collection->getId(), [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['followed']);
        $this->assertArrayHasKey('token', $data);

        $em->clear();
        $sub = $em->getRepository(UserCollectionSubscription::class)->findOneBy([
            'user'       => $em->find(User::class, $user->getId()),
            'collection' => $em->find(Collection::class, $collection->getId()),
        ]);
        $this->assertNotNull($sub);
    }

    public function testUnfollowCollectionAuthenticated(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy([]);
        $collection = $em->getRepository(Collection::class)->findOneBy([]);

        if ($user === null || $collection === null) {
            $this->markTestSkipped('No user or collection in DB.');
        }

        $existing = $em->getRepository(UserCollectionSubscription::class)->findOneBy([
            'user'       => $user,
            'collection' => $collection,
        ]);
        if ($existing === null) {
            $sub = new UserCollectionSubscription($user, $collection);
            $em->persist($sub);
            $em->flush();
        }

        $this->loginUser($client, $user);

        $csrfToken = static::getContainer()->get('security.csrf.token_manager')
            ->getToken('follow_collection_' . $collection->getId())
            ->getValue();

        $client->request('POST', '/follow/collection/' . $collection->getId(), [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['followed']);

        $em->clear();
        $sub = $em->getRepository(UserCollectionSubscription::class)->findOneBy([
            'user'       => $em->find(User::class, $user->getId()),
            'collection' => $em->find(Collection::class, $collection->getId()),
        ]);
        $this->assertNull($sub);
    }
}
