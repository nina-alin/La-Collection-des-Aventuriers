<?php

namespace App\DataFixtures;

use App\DataFixtures\Factory\AuthorFactory;
use App\DataFixtures\Factory\BookFactory;
use App\DataFixtures\Factory\CollectionFactory;
use App\DataFixtures\Factory\EditorFactory;
use App\DataFixtures\Factory\UserFactory;
use App\Entity\Enum\BookStatus;
use App\Entity\Enum\GenreCollection;
use App\Entity\Enum\StatutCollection;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // --- Authors ---
        $jackson = AuthorFactory::new(['firstName' => 'Steve', 'lastName' => 'Jackson']);
        $livingstone = AuthorFactory::new(['firstName' => 'Ian', 'lastName' => 'Livingstone']);
        $manager->persist($jackson);
        $manager->persist($livingstone);

        // --- Editors ---
        $gallimard = EditorFactory::new(['name' => 'Gallimard Jeunesse']);
        $folio = EditorFactory::new(['name' => 'Folio Junior']);
        $manager->persist($gallimard);
        $manager->persist($folio);

        // --- Collections ---
        $defis = CollectionFactory::new([
            'nom'              => 'Défis Fantastiques',
            'description'      => 'La série de livres-jeux de référence, publiée par Gallimard Jeunesse.',
            'genre'            => GenreCollection::MEDIEVAL_FANTASTIQUE,
            'statut'           => StatutCollection::TERMINEE,
            'createurs'        => ['Steve Jackson', 'Ian Livingstone'],
            'anneeCreation'    => 1982,
            'editeurHistorique' => 'Puffin Books',
        ]);
        $manager->persist($defis);

        $ldvelh = CollectionFactory::new([
            'nom'         => 'Loup Solitaire',
            'description' => 'Série de livres-jeux heroic-fantasy créée par Joe Dever.',
            'genre'       => GenreCollection::MEDIEVAL_FANTASTIQUE,
            'statut'      => StatutCollection::EN_COURS,
            'createurs'   => ['Joe Dever'],
            'anneeCreation' => 1984,
        ]);
        $manager->persist($ldvelh);

        $manager->flush();

        // --- Books ---
        $sorcier = BookFactory::new([
            'title'                  => 'Le Sorcier de la Montagne de Feu',
            'originalTitle'          => 'The Warlock of Firetop Mountain',
            'status'                 => BookStatus::PUBLISHED,
            'editor'                 => $gallimard,
            'collection'             => $defis,
            'isbn'                   => '2-07-036578-3',
            'pages'                  => 224,
            'paragraphs'             => 400,
            'frenchPublicationYear'  => 1984,
            'originalPublicationYear' => 1982,
            'volumeNumber'           => 1,
            'languages'              => ['fr', 'en'],
            'summary'                => 'Dans les entrailles de la Montagne de Feu vit le Sorcier, gardien d\'un trésor fabuleux.',
        ]);
        $sorcier->addAuthor($jackson)->addAuthor($livingstone);
        $manager->persist($sorcier);

        $citadelle = BookFactory::new([
            'title'      => 'La Citadelle du Chaos',
            'status'     => BookStatus::PUBLISHED,
            'editor'     => $gallimard,
            'collection' => $defis,
            'volumeNumber' => 2,
        ]);
        $citadelle->addAuthor($jackson);
        $manager->persist($citadelle);

        $pending = BookFactory::new([
            'title'  => 'Livre en Attente de Modération',
            'status' => BookStatus::PENDING,
            'editor' => $folio,
        ]);
        $pending->addAuthor($livingstone);
        $manager->persist($pending);

        // --- Users ---
        $admin = UserFactory::new([
            'email'       => 'admin@example.com',
            'pseudo'      => 'admin',
            'roles'       => ['ROLE_ADMIN'],
            'displayName' => 'Admin',
        ]);
        $admin->setPassword($this->hasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        $moderator = UserFactory::new([
            'email'       => 'moderator@example.com',
            'pseudo'      => 'moderator',
            'roles'       => ['ROLE_MODERATOR'],
            'displayName' => 'Modérateur',
        ]);
        $moderator->setPassword($this->hasher->hashPassword($moderator, 'password'));
        $manager->persist($moderator);

        $user = UserFactory::new([
            'email'       => 'user@example.com',
            'pseudo'      => 'utilisateur',
            'displayName' => 'Utilisateur',
        ]);
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $manager->persist($user);

        $manager->flush();
    }
}
