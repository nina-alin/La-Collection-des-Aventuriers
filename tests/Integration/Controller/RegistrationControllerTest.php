<?php

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    public function testGetRegisterReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/inscription');
        $this->assertResponseIsSuccessful();
    }

    public function testPostValidDataCreatesUserAndRedirects(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[pseudo]' => 'testpseudo',
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
            'registration_form[rgpdConsent]' => true,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/');

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneByEmail('newuser@example.com');
        $this->assertNotNull($user);
    }

    public function testPostDuplicateEmailShowsFieldError(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $existing = new User();
        $existing->setEmail('duplicate@example.com');
        $existing->setPseudo('existingpseudo');
        $existing->setPassword($hasher->hashPassword($existing, 'password123'));
        $em->persist($existing);
        $em->flush();

        $crawler = $client->request('GET', '/inscription');
        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[pseudo]' => 'newpseudo',
            'registration_form[email]' => 'duplicate@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
            'registration_form[rgpdConsent]' => true,
        ]);

        $client->submit($form);

        $this->assertSelectorTextContains('body', 'Cette adresse email est déjà associée à un compte.');
    }

    public function testPostDuplicatePseudoShowsFieldError(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $existing = new User();
        $existing->setEmail('other@example.com');
        $existing->setPseudo('takenPseudo');
        $existing->setPassword($hasher->hashPassword($existing, 'password123'));
        $em->persist($existing);
        $em->flush();

        $crawler = $client->request('GET', '/inscription');
        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[pseudo]' => 'takenPseudo',
            'registration_form[email]' => 'new@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
            'registration_form[rgpdConsent]' => true,
        ]);

        $client->submit($form);

        $this->assertSelectorTextContains('body', "Ce pseudo n'est pas disponible.");
    }

    public function testPostRgpdUncheckedShowsError(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[pseudo]' => 'testpseudo',
            'registration_form[email]' => 'user@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
        ]);

        $client->submit($form);

        $this->assertSelectorTextContains('body', 'Vous devez accepter les conditions pour créer un compte.');
    }

    public function testPostMismatchedPasswordsShowsError(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[pseudo]' => 'testpseudo',
            'registration_form[email]' => 'user@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'differentpassword',
            'registration_form[rgpdConsent]' => true,
        ]);

        $client->submit($form);

        $this->assertSelectorTextContains('body', 'Les mots de passe ne correspondent pas.');
    }
}
