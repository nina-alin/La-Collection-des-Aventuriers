<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\BookImage;
use App\Entity\Editor;
use App\Entity\Enum\BookImageTab;
use App\Entity\Enum\BookStatus;
use App\Entity\Illustrator;
use App\Entity\Translator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BookFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // --- People ---
        $jackson = (new Author())->setFirstName('Steve')->setLastName('Jackson');
        $livingstone = (new Author())->setFirstName('Ian')->setLastName('Livingstone');
        $motchane = (new Translator())->setFirstName('Dominique')->setLastName('Motchane');
        $blondel = (new Illustrator())->setFirstName('Russ')->setLastName('Nicholson');
        $gallimard = (new Editor())->setName('Gallimard Jeunesse');

        foreach ([$jackson, $livingstone, $motchane, $blondel, $gallimard] as $entity) {
            $manager->persist($entity);
        }

        // --- Published book: full data ---
        $sorcier = new Book();
        $sorcier
            ->setTitle('Le Sorcier de la Montagne de Feu')
            ->setOriginalTitle('The Warlock of Firetop Mountain')
            ->setStatus(BookStatus::PUBLISHED)
            ->setEditor($gallimard)
            ->setIsbn('2-07-036578-3')
            ->setPages(224)
            ->setParagraphs(400)
            ->setFrenchPublicationYear(1984)
            ->setOriginalPublicationYear(1982)
            ->setEditionInfo('Folio Junior — 1re édition française')
            ->setSaga('Défis Fantastiques')
            ->setVolumeNumber(1)
            ->setLanguages(['fr', 'en'])
            ->setTaverneUrl('https://la-taverne.example.com/sujets/le-sorcier-de-la-montagne-de-feu')
            ->setSummary(
                'Quelque part dans les entrailles de la Montagne de Feu vit le Sorcier, '
                . 'maître des lieux et gardien d\'un trésor fabuleux. Nombreux sont les aventuriers '
                . 'qui ont tenté de s\'emparer de ce trésor ; aucun n\'en est revenu. Peut-être '
                . 'aurez-vous plus de chance ? À vous de jouer !'
            )
            ->addAuthor($jackson)
            ->addAuthor($livingstone)
            ->addIllustrator($blondel)
            ->setTranslator($motchane)
        ;
        $manager->persist($sorcier);

        // Gallery images for published book
        foreach ([
            [BookImageTab::TOME, 'sorcier-tome.jpg'],
            [BookImageTab::DOS,  'sorcier-dos.jpg'],
        ] as [$tab, $path]) {
            $img = (new BookImage())
                ->setBook($sorcier)
                ->setTab($tab)
                ->setImagePath($path);
            $manager->persist($img);
        }

        // --- Published book: minimal data (no summary, no cover) ---
        $citadelle = new Book();
        $citadelle
            ->setTitle('La Citadelle du Chaos')
            ->setStatus(BookStatus::PUBLISHED)
            ->setEditor($gallimard)
            ->addAuthor($jackson)
        ;
        $manager->persist($citadelle);

        // --- Pending book (→ 404 for non-moderators) ---
        $pending = new Book();
        $pending
            ->setTitle('Livre en Attente de Modération')
            ->setStatus(BookStatus::PENDING)
            ->setEditor($gallimard)
            ->addAuthor($livingstone)
        ;
        $manager->persist($pending);

        $manager->flush();

        echo sprintf(
            "Fixtures loaded:\n  /livre/%s  (PUBLISHED, full data)\n  /livre/%s  (PUBLISHED, minimal)\n  /livre/%s  (PENDING → 404)\n",
            $sorcier->getSlug(),
            $citadelle->getSlug(),
            $pending->getSlug(),
        );
    }
}
