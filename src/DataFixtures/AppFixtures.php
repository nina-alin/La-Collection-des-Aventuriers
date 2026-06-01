<?php

namespace App\DataFixtures;

use App\DataFixtures\Factory\BookFactory;
use App\DataFixtures\Factory\CollectionFactory;
use App\DataFixtures\Factory\ContributionFactory;
use App\DataFixtures\Factory\ContributorFactory;
use App\DataFixtures\Factory\EditorFactory;
use App\DataFixtures\Factory\UserFactory;
use App\Entity\CollectionPublishingHistory;
use App\Entity\Enum\BookStatus;
use App\Entity\Enum\ContributionRole;
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

        // --- Publishing History for Loup Solitaire ---
        $scriptarium = EditorFactory::new(['name' => 'Scriptarium']);
        $manager->persist($scriptarium);
        $manager->flush();

        $history1 = new CollectionPublishingHistory();
        $history1->setCollection($ldvelh);
        $history1->setEditor($gallimard);
        $history1->setStartYear(1984);
        $history1->setEndYear(1992);
        $history1->setEditionName('Première édition FR');
        $history1->setDetails('Tomes 1 à 14 · traduction Camille Fabien');
        $manager->persist($history1);

        $history2 = new CollectionPublishingHistory();
        $history2->setCollection($ldvelh);
        $history2->setEditor($folio);
        $history2->setStartYear(1993);
        $history2->setEndYear(1998);
        $history2->setEditionName('Reprise');
        $history2->setDetails('Tomes 15 à 28 · couvertures refondues');
        $manager->persist($history2);

        $history3 = new CollectionPublishingHistory();
        $history3->setCollection($ldvelh);
        $history3->setEditor($scriptarium);
        $history3->setStartYear(2017);
        $manager->persist($history3);

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
            'coverImage'             => 'sorcier.jpg',
        ]);
        $manager->persist($sorcier);

        $citadelle = BookFactory::new([
            'title'      => 'La Citadelle du Chaos',
            'status'     => BookStatus::PUBLISHED,
            'editor'     => $gallimard,
            'collection' => $defis,
            'volumeNumber' => 2,
            'coverImage'   => 'citadelle.jpg',
        ]);
        $manager->persist($citadelle);

        // SC-006: book without cover image
        $pending = BookFactory::new([
            'title'  => 'Livre en Attente de Modération',
            'status' => BookStatus::PENDING,
            'editor' => $folio,
        ]);
        $manager->persist($pending);

        $manager->flush();

        // --- SC-006 Contributors ---

        // (1) Author-only contributor with portraitImage
        $jackson = ContributorFactory::new([
            'firstName'    => 'Steve',
            'lastName'     => 'Jackson',
            'biography'    => 'Auteur britannique de livres-jeux, co-créateur de la série Défis Fantastiques.',
            'nationality'  => 'GB',
            'portraitImage' => 'jackson.jpg',
        ]);
        $manager->persist($jackson);

        // (2) Illustrator-only contributor with portraitImage
        $russ = ContributorFactory::new([
            'firstName'    => 'Russ',
            'lastName'     => 'Nicholson',
            'biography'    => 'Illustrateur britannique, connu pour ses illustrations de Défis Fantastiques.',
            'nationality'  => 'GB',
            'portraitImage' => 'nicholson.jpg',
        ]);
        $manager->persist($russ);

        // (3) Multi-role contributor (Author + Illustrator + Traductor on different books)
        $livingstone = ContributorFactory::new([
            'firstName'    => 'Ian',
            'lastName'     => 'Livingstone',
            'biography'    => 'Auteur et illustrateur britannique, co-fondateur de Games Workshop.',
            'nationality'  => 'GB',
            'portraitImage' => 'livingstone.jpg',
        ]);
        $manager->persist($livingstone);

        // (4) Contributor without portraitImage
        $anonyme = ContributorFactory::withoutPortrait([
            'firstName' => 'Traducteur',
            'lastName'  => 'Anonyme',
        ]);
        $manager->persist($anonyme);

        $manager->flush();

        // --- Contributions ---
        $contrib1 = ContributionFactory::new([
            'contributor' => $jackson,
            'book'        => $sorcier,
            'role'        => ContributionRole::Author,
        ]);
        $manager->persist($contrib1);

        $contrib2 = ContributionFactory::new([
            'contributor' => $livingstone,
            'book'        => $sorcier,
            'role'        => ContributionRole::Author,
        ]);
        $manager->persist($contrib2);

        $contrib3 = ContributionFactory::new([
            'contributor' => $jackson,
            'book'        => $citadelle,
            'role'        => ContributionRole::Author,
        ]);
        $manager->persist($contrib3);

        $contrib4 = ContributionFactory::new([
            'contributor' => $russ,
            'book'        => $sorcier,
            'role'        => ContributionRole::Illustrator,
        ]);
        $manager->persist($contrib4);

        // Multi-role: livingstone as Illustrator on citadelle
        $contrib5 = ContributionFactory::new([
            'contributor' => $livingstone,
            'book'        => $citadelle,
            'role'        => ContributionRole::Illustrator,
        ]);
        $manager->persist($contrib5);

        // Multi-role: livingstone as Traductor on pending
        $contrib6 = ContributionFactory::new([
            'contributor' => $livingstone,
            'book'        => $pending,
            'role'        => ContributionRole::Traductor,
        ]);
        $manager->persist($contrib6);

        // Anonyme as Traductor on sorcier
        $contrib7 = ContributionFactory::new([
            'contributor' => $anonyme,
            'book'        => $sorcier,
            'role'        => ContributionRole::Traductor,
        ]);
        $manager->persist($contrib7);

        $manager->flush();

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
