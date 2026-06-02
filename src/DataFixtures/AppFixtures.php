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
use App\Entity\UserBook;
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
        // =====================================================================
        // EDITORS
        // =====================================================================
        $gallimard  = EditorFactory::new(['name' => 'Gallimard Jeunesse']);
        $folio      = EditorFactory::new(['name' => 'Folio Junior']);
        $scriptarium = EditorFactory::new(['name' => 'Scriptarium']);
        $rageot     = EditorFactory::new(['name' => 'Rageot']);
        $flammarion = EditorFactory::new(['name' => 'Flammarion Jeunesse']);
        $hachette   = EditorFactory::new(['name' => 'Hachette']);
        $nathan     = EditorFactory::new(['name' => 'Nathan']);
        $lpj        = EditorFactory::new(['name' => 'Livre de Poche Jeunesse']);

        foreach ([$gallimard, $folio, $scriptarium, $rageot, $flammarion, $hachette, $nathan, $lpj] as $e) {
            $manager->persist($e);
        }

        // =====================================================================
        // COLLECTIONS
        // =====================================================================
        $defis = CollectionFactory::new([
            'nom'               => 'Défis Fantastiques',
            'description'       => 'La série de livres-jeux de référence, publiée par Gallimard Jeunesse. 59 volumes de pure heroic-fantasy.',
            'genre'             => GenreCollection::MEDIEVAL_FANTASTIQUE,
            'statut'            => StatutCollection::TERMINEE,
            'createurs'         => ['Steve Jackson', 'Ian Livingstone'],
            'anneeCreation'     => 1982,
            'editeurHistorique' => 'Puffin Books',
        ]);

        $loupSolitaire = CollectionFactory::new([
            'nom'               => 'Loup Solitaire',
            'description'       => "Série d'heroic-fantasy créée par Joe Dever. Vous incarnez Loup Solitaire, dernier des Seigneurs Kaï de Sommerlund.",
            'genre'             => GenreCollection::MEDIEVAL_FANTASTIQUE,
            'statut'            => StatutCollection::EN_COURS,
            'createurs'         => ['Joe Dever'],
            'anneeCreation'     => 1984,
            'editeurHistorique' => 'Beaver Books',
        ]);

        $sorcellerie = CollectionFactory::new([
            'nom'               => 'Sorcellerie!',
            'description'       => 'Épopée en quatre volumes par Steve Jackson, avec un système de magie sophistiqué permettant de lancer de vrais sorts.',
            'genre'             => GenreCollection::MEDIEVAL_FANTASTIQUE,
            'statut'            => StatutCollection::TERMINEE,
            'createurs'         => ['Steve Jackson'],
            'anneeCreation'     => 1983,
            'editeurHistorique' => 'Puffin Books',
        ]);

        $grailquest = CollectionFactory::new([
            'nom'           => 'Grailquest',
            'description'   => 'Série humoristique de J.H. Brennan. Vous jouez un jeune garçon envoyé par Merlin dans des aventures médiévales loufoques.',
            'genre'         => GenreCollection::MEDIEVAL_FANTASTIQUE,
            'statut'        => StatutCollection::TERMINEE,
            'createurs'     => ['J.H. Brennan'],
            'anneeCreation' => 1984,
            'editeurHistorique' => 'Armada',
        ]);

        $bloodSword = CollectionFactory::new([
            'nom'           => 'Blood Sword',
            'description'   => 'Série de livres-jeux de Dave Morris et Jamie Thomson, jouable en solo ou jusqu\'à quatre joueurs simultanément.',
            'genre'         => GenreCollection::MEDIEVAL_FANTASTIQUE,
            'statut'        => StatutCollection::TERMINEE,
            'createurs'     => ['Dave Morris', 'Jamie Thomson'],
            'anneeCreation' => 1987,
            'editeurHistorique' => 'Knight Books',
        ]);

        $voieTigre = CollectionFactory::new([
            'nom'           => 'La Voie du Tigre',
            'description'   => "Série de fantasy asiatique par Mark Smith et Jamie Thomson. Vous incarnez un ninja maître des arts martiaux.",
            'genre'         => GenreCollection::MEDIEVAL_FANTASTIQUE,
            'statut'        => StatutCollection::TERMINEE,
            'createurs'     => ['Mark Smith', 'Jamie Thomson'],
            'anneeCreation' => 1985,
            'editeurHistorique' => 'Puffin Books',
        ]);

        $elanNoir = CollectionFactory::new([
            'nom'           => 'Élan Noir',
            'description'   => "Série de fantasy par Dave Morris. Vous êtes un guerrier elfe en quête des cristaux de vie volés par le Seigneur des Ténèbres.",
            'genre'         => GenreCollection::MEDIEVAL_FANTASTIQUE,
            'statut'        => StatutCollection::TERMINEE,
            'createurs'     => ['Dave Morris'],
            'anneeCreation' => 1989,
            'editeurHistorique' => 'Beaver Books',
        ]);

        $chairDePoule = CollectionFactory::new([
            'nom'           => 'Chair de Poule',
            'description'   => "La célèbre série d'horreur pour jeunes lecteurs de R.L. Stine. Plus de 60 titres traduits en français.",
            'genre'         => GenreCollection::HORREUR,
            'statut'        => StatutCollection::REEDITEE,
            'createurs'     => ['R.L. Stine'],
            'anneeCreation' => 1992,
            'editeurHistorique' => 'Scholastic',
        ]);

        $maitreTenebres = CollectionFactory::new([
            'nom'           => 'Les Maîtres des Ténèbres',
            'description'   => "Série d'horreur gothique par Jonathan Green. Explorez des manoirs hantés, des cryptes sinistres et des abbayes maudites.",
            'genre'         => GenreCollection::HORREUR,
            'statut'        => StatutCollection::TERMINEE,
            'createurs'     => ['Jonathan Green'],
            'anneeCreation' => 1995,
            'editeurHistorique' => 'Puffin Books',
        ]);

        $starChallenge = CollectionFactory::new([
            'nom'           => 'Star Challenge',
            'description'   => 'Série de science-fiction interstellaire. Explorez les confins de la galaxie, combattez des envahisseurs et sauvez des civilisations.',
            'genre'         => GenreCollection::SCIENCE_FICTION,
            'statut'        => StatutCollection::TERMINEE,
            'createurs'     => ['Gary Chalk'],
            'anneeCreation' => 1985,
            'editeurHistorique' => 'Random House',
        ]);

        $duelMasters = CollectionFactory::new([
            'nom'           => 'Duel Masters',
            'description'   => 'Série de science-fiction intergalactique par Paul Mason. Batailles spatiales épiques et explorations de planètes inconnues.',
            'genre'         => GenreCollection::SCIENCE_FICTION,
            'statut'        => StatutCollection::TERMINEE,
            'createurs'     => ['Paul Mason'],
            'anneeCreation' => 1987,
        ]);

        $destAventure = CollectionFactory::new([
            'nom'           => 'Destination Aventure',
            'description'   => "Collection d'aventures modernes pour jeunes lecteurs. Espionnage, exploration et mystères contemporains.",
            'genre'         => GenreCollection::AVENTURE,
            'statut'        => StatutCollection::TERMINEE,
            'createurs'     => ['Christophe Lambert'],
            'anneeCreation' => 1988,
        ]);

        $espionnage = CollectionFactory::new([
            'nom'           => 'Agents Secrets',
            'description'   => "Série d'espionnage haletante. Vous êtes un agent secret infiltré dans les réseaux les plus dangereux du monde.",
            'genre'         => GenreCollection::ESPIONNAGE,
            'statut'        => StatutCollection::TERMINEE,
            'createurs'     => ['Marc Gascoigne'],
            'anneeCreation' => 1986,
        ]);

        $collections = [
            $defis, $loupSolitaire, $sorcellerie, $grailquest, $bloodSword,
            $voieTigre, $elanNoir, $chairDePoule, $maitreTenebres,
            $starChallenge, $duelMasters, $destAventure, $espionnage,
        ];
        foreach ($collections as $c) {
            $manager->persist($c);
        }

        $manager->flush();

        // =====================================================================
        // PUBLISHING HISTORIES
        // =====================================================================
        $h = new CollectionPublishingHistory();
        $h->setCollection($defis);
        $h->setEditor($gallimard);
        $h->setStartYear(1984);
        $h->setEndYear(2001);
        $h->setEditionName('Édition Gallimard Jeunesse');
        $h->setDetails('59 volumes · traductions multiples');
        $manager->persist($h);

        $h = new CollectionPublishingHistory();
        $h->setCollection($loupSolitaire);
        $h->setEditor($gallimard);
        $h->setStartYear(1984);
        $h->setEndYear(1992);
        $h->setEditionName('Première édition FR');
        $h->setDetails('Tomes 1 à 14 · traduction Camille Fabien');
        $manager->persist($h);

        $h = new CollectionPublishingHistory();
        $h->setCollection($loupSolitaire);
        $h->setEditor($folio);
        $h->setStartYear(1993);
        $h->setEndYear(1998);
        $h->setEditionName('Reprise Folio');
        $h->setDetails('Tomes 15 à 28 · couvertures refondues');
        $manager->persist($h);

        $h = new CollectionPublishingHistory();
        $h->setCollection($loupSolitaire);
        $h->setEditor($scriptarium);
        $h->setStartYear(2017);
        $h->setEditionName('Édition Scriptarium');
        $h->setDetails('Réédition avec nouvelles illustrations');
        $manager->persist($h);

        $h = new CollectionPublishingHistory();
        $h->setCollection($sorcellerie);
        $h->setEditor($gallimard);
        $h->setStartYear(1985);
        $h->setEndYear(1989);
        $h->setEditionName('Édition originale FR');
        $manager->persist($h);

        $h = new CollectionPublishingHistory();
        $h->setCollection($chairDePoule);
        $h->setEditor($folio);
        $h->setStartYear(1993);
        $h->setEndYear(2005);
        $h->setEditionName('Édition Folio Junior');
        $manager->persist($h);

        $h = new CollectionPublishingHistory();
        $h->setCollection($chairDePoule);
        $h->setEditor($hachette);
        $h->setStartYear(2012);
        $h->setEditionName('Réédition Hachette');
        $h->setDetails('Nouvelles couvertures modernisées');
        $manager->persist($h);

        $manager->flush();

        // =====================================================================
        // CONTRIBUTORS
        // =====================================================================
        $jackson = ContributorFactory::new([
            'firstName'     => 'Steve',
            'lastName'      => 'Jackson',
            'biography'     => 'Auteur britannique de livres-jeux, co-créateur de la série Défis Fantastiques et auteur de la tétralogie Sorcellerie!. Co-fondateur de Games Workshop.',
            'nationality'   => 'GB',
            'portraitImage' => 'jackson.jpg',
        ]);

        $livingstone = ContributorFactory::new([
            'firstName'     => 'Ian',
            'lastName'      => 'Livingstone',
            'biography'     => 'Auteur et entrepreneur britannique, co-fondateur de Games Workshop et co-créateur des Défis Fantastiques. Auteur de La Ville des Voleurs, Le Labyrinthe de la Mort et de nombreux autres titres.',
            'nationality'   => 'GB',
            'portraitImage' => 'livingstone.jpg',
        ]);

        $nicholson = ContributorFactory::new([
            'firstName'     => 'Russ',
            'lastName'      => 'Nicholson',
            'biography'     => "Illustrateur britannique emblématique, connu pour son style médiéval détaillé dans les Défis Fantastiques. Ses créatures sont reconnaissables entre mille.",
            'nationality'   => 'GB',
            'portraitImage' => 'nicholson.jpg',
        ]);

        $dever = ContributorFactory::new([
            'firstName'     => 'Joe',
            'lastName'      => 'Dever',
            'biography'     => "Auteur britannique, créateur de la série Loup Solitaire avec 28 volumes. Champion du monde de jeu de rôle en 1982, il a consacré sa vie à l'univers de Magnamund.",
            'nationality'   => 'GB',
            'portraitImage' => 'dever.jpg',
        ]);

        $chalk = ContributorFactory::new([
            'firstName'   => 'Gary',
            'lastName'    => 'Chalk',
            'biography'   => "Illustrateur et auteur britannique. Illustrateur des premiers volumes de Loup Solitaire et auteur de la série Star Challenge.",
            'nationality' => 'GB',
        ]);

        $morris = ContributorFactory::new([
            'firstName'   => 'Dave',
            'lastName'    => 'Morris',
            'biography'   => "Auteur britannique prolifique, co-créateur de Blood Sword et de la série Élan Noir. Pionnier du livre-jeu multijoueur en Europe.",
            'nationality' => 'GB',
        ]);

        $thomson = ContributorFactory::new([
            'firstName'   => 'Jamie',
            'lastName'    => 'Thomson',
            'biography'   => 'Auteur britannique, co-créateur de Blood Sword et de La Voie du Tigre avec Mark Smith.',
            'nationality' => 'GB',
        ]);

        $smith = ContributorFactory::new([
            'firstName'   => 'Mark',
            'lastName'    => 'Smith',
            'biography'   => "Auteur britannique, co-créateur de La Voie du Tigre. La série se distingue par sa progression de personnage unique entre les volumes.",
            'nationality' => 'GB',
        ]);

        $brennan = ContributorFactory::new([
            'firstName'   => 'J.H.',
            'lastName'    => 'Brennan',
            'biography'   => "Auteur irlandais, créateur de la série Grailquest mélangeant aventure médiévale et humour décalé. Aussi connu sous le nom Herbie Brennan.",
            'nationality' => 'IE',
        ]);

        $green = ContributorFactory::new([
            'firstName'   => 'Jonathan',
            'lastName'    => 'Green',
            'biography'   => "Auteur britannique prolifique de livres-jeux, notamment la série Les Maîtres des Ténèbres et de nombreux volumes des Défis Fantastiques.",
            'nationality' => 'GB',
        ]);

        $stine = ContributorFactory::new([
            'firstName'     => 'R.L.',
            'lastName'      => 'Stine',
            'biography'     => "Auteur américain, créateur de la série Chair de Poule, vendue à plus de 400 millions d'exemplaires dans le monde. Le roi de l'horreur pour enfants.",
            'nationality'   => 'US',
            'portraitImage' => 'stine.jpg',
        ]);

        $lambert = ContributorFactory::new([
            'firstName'   => 'Christophe',
            'lastName'    => 'Lambert',
            'biography'   => "Auteur français de livres-jeux et de romans de fantasy. Figure importante du genre en France avec la série Destination Aventure.",
            'nationality' => 'FR',
        ]);

        $mason = ContributorFactory::new([
            'firstName'   => 'Paul',
            'lastName'    => 'Mason',
            'biography'   => 'Auteur britannique de livres-jeux, collaborateur fréquent sur les Défis Fantastiques et auteur de la série Duel Masters.',
            'nationality' => 'GB',
        ]);

        $miller = ContributorFactory::new([
            'firstName'   => 'Ian',
            'lastName'    => 'Miller',
            'biography'   => "Illustrateur britannique au style sombre, surréaliste et très détaillé. Ses œuvres dans les Défis Fantastiques ont marqué toute une génération.",
            'nationality' => 'GB',
        ]);

        $gascoigne = ContributorFactory::new([
            'firstName'   => 'Marc',
            'lastName'    => 'Gascoigne',
            'biography'   => "Auteur britannique et éditeur chez Puffin Books. Co-auteur de plusieurs Défis Fantastiques et créateur de la série Agents Secrets.",
            'nationality' => 'GB',
        ]);

        $waterfield = ContributorFactory::new([
            'firstName'   => 'Robin',
            'lastName'    => 'Waterfield',
            'biography'   => "Auteur britannique et traducteur, contributeur aux Défis Fantastiques avec des titres aux décors fantastiques variés.",
            'nationality' => 'GB',
        ]);

        $anonyme = ContributorFactory::withoutPortrait([
            'firstName' => 'Traducteur',
            'lastName'  => 'Anonyme',
        ]);

        $contributors = [
            $jackson, $livingstone, $nicholson, $dever, $chalk, $morris, $thomson,
            $smith, $brennan, $green, $stine, $lambert, $mason, $miller,
            $gascoigne, $waterfield, $anonyme,
        ];
        foreach ($contributors as $c) {
            $manager->persist($c);
        }

        $manager->flush();

        // =====================================================================
        // BOOKS — Défis Fantastiques
        // =====================================================================
        $dfSorcier = BookFactory::new([
            'title'                   => 'Le Sorcier de la Montagne de Feu',
            'originalTitle'           => 'The Warlock of Firetop Mountain',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'isbn'                    => '2-07-036578-3',
            'pages'                   => 224,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1984,
            'originalPublicationYear' => 1982,
            'volumeNumber'            => 1,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Dans les entrailles de la Montagne de Feu vit le Sorcier, gardien d'un trésor fabuleux. Affrontez les créatures de son donjon pour vous en emparer.",
            'coverImage'              => 'sorcier.jpg',
        ]);

        $dfCitadelle = BookFactory::new([
            'title'                   => 'La Citadelle du Chaos',
            'originalTitle'           => 'The Citadel of Chaos',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'isbn'                    => '2-07-036579-1',
            'pages'                   => 240,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1984,
            'originalPublicationYear' => 1983,
            'volumeNumber'            => 2,
            'languages'               => ['fr', 'en'],
            'summary'                 => "La Citadelle du Chaos est la forteresse du terrible Balthus Dire. Pénétrez-y avec vos sorts et vos armes pour vaincre ce sorcier maléfique.",
            'coverImage'              => 'citadelle.jpg',
        ]);

        $dfForet = BookFactory::new([
            'title'                   => 'La Forêt des Ténèbres',
            'originalTitle'           => 'Forest of Doom',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 222,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1984,
            'originalPublicationYear' => 1983,
            'volumeNumber'            => 3,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Les nains de Stonebridge ont besoin de vous ! Leur légendaire marteau de guerre a été volé. Retrouvez-le dans les profondeurs de la Forêt de Darkwood.",
        ]);

        $dfEtoile = BookFactory::new([
            'title'                   => 'Étoile de la Mort',
            'originalTitle'           => 'Starship Traveller',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 224,
            'paragraphs'              => 423,
            'frenchPublicationYear'   => 1984,
            'originalPublicationYear' => 1983,
            'volumeNumber'            => 4,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Vous êtes commandant du vaisseau spatial Traveller. Un trou noir vous a projeté dans une galaxie inconnue. Trouvez un chemin vers votre galaxie d'origine.",
        ]);

        $dfVille = BookFactory::new([
            'title'                   => 'La Ville des Voleurs',
            'originalTitle'           => 'City of Thieves',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 224,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1983,
            'volumeNumber'            => 5,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Port Blacksand, la ville des voleurs, est tenue par le terrible Seigneur Azzur. Infiltrez cette cité dangereuse pour retrouver Nicodemus et sauver Silverton.",
        ]);

        $dfLabyrinthe = BookFactory::new([
            'title'                   => 'Le Labyrinthe de la Mort',
            'originalTitle'           => 'Deathtrap Dungeon',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 240,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 6,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Baron Sukumvit a créé le donjon le plus meurtrier qui soit. Chaque année, des aventuriers y entrent pour décrocher la fortune promise. Aucun n'en est sorti vivant.",
        ]);

        $dfIle = BookFactory::new([
            'title'                   => "L'Île du Roi Lézard",
            'originalTitle'           => 'Island of the Lizard King',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 220,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 7,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Des esclaves construisent une pyramide sur l'île Fang pour un mystérieux Roi Lézard. Votre ami Mungo est parmi eux. Libérez-les !",
        ]);

        $dfMarais = BookFactory::new([
            'title'                   => 'Le Marais aux Scorpions',
            'originalTitle'           => 'Scorpion Swamp',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 208,
            'paragraphs'              => 360,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 8,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Le Marais aux Scorpions est un enfer de boue et de venin. Trois sorciers vous confient des missions opposées dans ces terres hostiles.",
        ]);

        $dfCavernes = BookFactory::new([
            'title'                   => 'Cavernes de la Neige Sanglante',
            'originalTitle'           => 'Caverns of the Snow Witch',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 224,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 9,
            'languages'               => ['fr', 'en'],
            'summary'                 => "La Sorcière des Neiges menace de plonger le monde dans un éternel hiver. Pénétrez dans ses cavernes glacées pour la vaincre avant qu'il ne soit trop tard.",
        ]);

        $dfFear = BookFactory::new([
            'title'                   => 'Magehunter',
            'originalTitle'           => 'Appointment with F.E.A.R.',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 208,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 10,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Vous êtes le Silver Crusader, un super-héros doté de pouvoirs extraordinaires. Déjouez les plans criminels de F.E.A.R. dans la ville de Titan City.",
        ]);

        $dfTalisman = BookFactory::new([
            'title'                   => 'Le Talisman de la Mort',
            'originalTitle'           => 'Talisman of Death',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 208,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 11,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Le Talisman de la Mort est une arme redoutable convoitée par les agents des Enfers. Fuyez à travers les terres de Orb en protégeant ce sinistre objet.",
        ]);

        $dfSeides = BookFactory::new([
            'title'                   => 'Séides du Scorpion',
            'originalTitle'           => 'Space Assassin',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 224,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 12,
            'languages'               => ['fr', 'en'],
            'summary'                 => "À bord d'un vaisseau spatial infesté de monstres mutants, vous devez retrouver et éliminer le savant fou Cyrus avant qu'il ne détruise la galaxie.",
        ]);

        $dfChevaliers = BookFactory::new([
            'title'                   => 'Les Chevaliers du Destin',
            'originalTitle'           => 'Freeway Fighter',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 208,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 13,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Dans un monde post-apocalyptique, vous pilotez un bolide armé jusqu'aux dents sur des autoroutes anarchiques pour transporter du carburant vital.",
        ]);

        $dfTemple = BookFactory::new([
            'title'                   => 'Le Temple de la Terreur',
            'originalTitle'           => 'Temple of Terror',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 224,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 14,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Traversez le désert de Khul pour retrouver cinq artefacts magiques avant que le sorcier Malbordus ne les rassemble et ne conquière le monde.",
        ]);

        $dfChassPrimes = BookFactory::new([
            'title'                   => 'Le Chasseur de Primes',
            'originalTitle'           => 'Seas of Blood',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 224,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1987,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 15,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Vous êtes un pirate en quête de gloire sur les mers de Khul. Pillez, combattez et naviguez vers la cité mythique d'Assur.",
        ]);

        $dfFleche = BookFactory::new([
            'title'                   => 'La Flèche Ardente',
            'originalTitle'           => 'Rebel Planet',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 208,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1987,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 16,
            'languages'               => ['fr', 'en'],
            'summary'                 => "La Terre a été conquise par les Arcadiens. En mission secrète sur leur planète natale, vous devez trouver le code d'autodestruction de leur flotte.",
        ]);

        $dfSeides2 = BookFactory::new([
            'title'                   => 'L\'Épée du Dragon',
            'originalTitle'           => 'Appointment with F.E.A.R.',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $defis,
            'pages'                   => 224,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1988,
            'originalPublicationYear' => 1986,
            'volumeNumber'            => 17,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Vous quêtez la légendaire Épée du Dragon. Des donjons en ruine, des dragons à vaincre, et un artefact à rapporter vivant.",
        ]);

        $dfBooks = [
            $dfSorcier, $dfCitadelle, $dfForet, $dfEtoile, $dfVille,
            $dfLabyrinthe, $dfIle, $dfMarais, $dfCavernes, $dfFear,
            $dfTalisman, $dfSeides, $dfChevaliers, $dfTemple, $dfChassPrimes,
            $dfFleche, $dfSeides2,
        ];
        foreach ($dfBooks as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — Loup Solitaire
        // =====================================================================
        $lsFaucons = BookFactory::new([
            'title'                   => 'Les Faucons du Soleil Couchant',
            'originalTitle'           => 'Flight from the Dark',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $loupSolitaire,
            'pages'                   => 240,
            'paragraphs'              => 350,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 1,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Votre monastère vient d'être attaqué et vos frères massacrés. Vous êtes le seul Seigneur Kaï survivant. Fuyez vers la capitale avant que les armées de Zagarna ne prennent Sommerlund.",
        ]);

        $lsMarais = BookFactory::new([
            'title'                   => 'Les Seigneurs des Marais',
            'originalTitle'           => 'Fire on the Water',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $loupSolitaire,
            'pages'                   => 240,
            'paragraphs'              => 350,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 2,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Le roi de Sommerlund est au bord de la mort. Seul le cristal Lorestone peut le sauver. Traversez les marais et les mers pour le retrouver sur l'île de Durenor.",
        ]);

        $lsMort = BookFactory::new([
            'title'                   => 'La Mort sur les Eaux',
            'originalTitle'           => 'The Caverns of Kalte',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $loupSolitaire,
            'pages'                   => 248,
            'paragraphs'              => 350,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 3,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Le sorcier Vonotar le Traître s'est réfugié dans les terres gelées de Kalte. Pourchassez-le à travers des cavernes de glace peuplées de monstres arctiques.",
        ]);

        $lsArmees = BookFactory::new([
            'title'                   => "L'Armée des Ténèbres",
            'originalTitle'           => 'The Chasm of Doom',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $loupSolitaire,
            'pages'                   => 240,
            'paragraphs'              => 350,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 4,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Une armée de morts-vivants menace la cité de Ruanon. Retrouvez leur nécromancien maître dans les profondeurs du gouffre de Maaken.",
        ]);

        $lsPlaines = BookFactory::new([
            'title'                   => 'Les Plaines du Cauchemar',
            'originalTitle'           => 'Shadow on the Sand',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $loupSolitaire,
            'pages'                   => 256,
            'paragraphs'              => 360,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 5,
            'languages'               => ['fr', 'en'],
            'summary'                 => "En mission diplomatique à Vassagonia, vous êtes accusé d'assassinat. Fuyez à travers les déserts brûlants en cherchant à prouver votre innocence.",
        ]);

        $lsNid = BookFactory::new([
            'title'                   => 'Le Nid de la Honte',
            'originalTitle'           => 'The Kingdoms of Terror',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $loupSolitaire,
            'pages'                   => 256,
            'paragraphs'              => 350,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 6,
            'languages'               => ['fr', 'en'],
            'summary'                 => "La guilde des assassins a mis votre tête à prix. Infiltrez les cinq royaumes de l'Ouest pour démasquer et éliminer leur grand maître.",
        ]);

        $lsPelerins = BookFactory::new([
            'title'                   => 'Les Pèlerins du Désert',
            'originalTitle'           => 'Castle Death',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $loupSolitaire,
            'pages'                   => 248,
            'paragraphs'              => 350,
            'frenchPublicationYear'   => 1987,
            'originalPublicationYear' => 1986,
            'volumeNumber'            => 7,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Le Château de la Mort cache un Lorestone. Guidez une caravane de pèlerins à travers le désert de Ruel et affrontez les défenses du château maudit.",
        ]);

        $lsChateau = BookFactory::new([
            'title'                   => 'Le Château des Morts',
            'originalTitle'           => 'The Jungle of Horrors',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $loupSolitaire,
            'pages'                   => 256,
            'paragraphs'              => 350,
            'frenchPublicationYear'   => 1987,
            'originalPublicationYear' => 1986,
            'volumeNumber'            => 8,
            'languages'               => ['fr', 'en'],
            'summary'                 => "La jungle de Ohrur est un enfer vert peuplé de créatures mortelles et de plantes carnivores. Un Lorestone s'y cache — si vous survivez.",
        ]);

        $lsGardes = BookFactory::new([
            'title'                   => 'Les Gardes du Kazan',
            'originalTitle'           => 'The Cauldron of Fear',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $loupSolitaire,
            'pages'                   => 256,
            'paragraphs'              => 360,
            'frenchPublicationYear'   => 1988,
            'originalPublicationYear' => 1987,
            'volumeNumber'            => 9,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Vous devez retrouver le quatrième Lorestone dans la cité marchande de Tahou, tenue par les gardes corrompus du Kazan.",
        ]);

        $lsSables = BookFactory::new([
            'title'                   => 'Les Sables de la Mort',
            'originalTitle'           => 'The Dungeons of Torgar',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $loupSolitaire,
            'pages'                   => 272,
            'paragraphs'              => 380,
            'frenchPublicationYear'   => 1988,
            'originalPublicationYear' => 1987,
            'volumeNumber'            => 10,
            'languages'               => ['fr', 'en'],
            'summary'                 => "La forteresse de Torgar abrite le cinquième Lorestone dans ses donjons souterrains. Infiltrez cette place forte ennemie en pleine guerre.",
        ]);

        $lsBooks = [
            $lsFaucons, $lsMarais, $lsMort, $lsArmees, $lsPlaines,
            $lsNid, $lsPelerins, $lsChateau, $lsGardes, $lsSables,
        ];
        foreach ($lsBooks as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — Sorcellerie!
        // =====================================================================
        $soCity = BookFactory::new([
            'title'                   => 'Les Collines de Shamutanti',
            'originalTitle'           => 'The Shamutanti Hills',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $sorcellerie,
            'pages'                   => 256,
            'paragraphs'              => 460,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1983,
            'volumeNumber'            => 1,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Commencez votre épopée dans les collines de Shamutanti. Avec le Sortilège Opératoire en main, choisissez votre chemin vers Khare la cité-piège.",
        ]);

        $soKhare = BookFactory::new([
            'title'                   => 'Kharé, Cité-Piège',
            'originalTitle'           => 'Kharé - Cityport of Traps',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $sorcellerie,
            'pages'                   => 272,
            'paragraphs'              => 480,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 2,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Kharé, la ville aux mille pièges. Pour en sortir, vous devez réunir les quatre lignes d'un sortilège secret connu seulement de ses nobles corrompus.",
        ]);

        $soSerpents = BookFactory::new([
            'title'                   => 'Les Sept Serpents',
            'originalTitle'           => 'The Seven Serpents',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $sorcellerie,
            'pages'                   => 240,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 3,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Sept serpents messagers filent vers la Couronne des Rois pour prévenir l'Archimagicien de votre approche. Interceptez-les tous avant qu'il ne soit trop tard.",
        ]);

        $soCouronne = BookFactory::new([
            'title'                   => 'La Couronne des Rois',
            'originalTitle'           => 'The Crown of Kings',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $sorcellerie,
            'pages'                   => 448,
            'paragraphs'              => 800,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 4,
            'languages'               => ['fr', 'en'],
            'summary'                 => "La conclusion épique de Sorcellerie! La Couronne des Rois est gardée dans la citadelle de Mampang par l'Archimagicien lui-même. Votre longue quête touche à sa fin.",
        ]);

        foreach ([$soCity, $soKhare, $soSerpents, $soCouronne] as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — Grailquest
        // =====================================================================
        $gqChateau = BookFactory::new([
            'title'                   => 'Le Château des Ténèbres',
            'originalTitle'           => 'The Castle of Darkness',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $grailquest,
            'pages'                   => 192,
            'paragraphs'              => 280,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 1,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Merlin vous envoie dans un château médiéval envahi par les forces du mal pour retrouver le Graal. Une aventure médiévale pleine d'humour et de pièges.",
        ]);

        $gqDragons = BookFactory::new([
            'title'                   => 'La Tanière des Dragons',
            'originalTitle'           => 'The Den of Dragons',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $grailquest,
            'pages'                   => 192,
            'paragraphs'              => 280,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1984,
            'volumeNumber'            => 2,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Des dragons ont envahi le royaume de Camelot. Votre mission : retrouver leur chef dans leur repaire volcanique et mettre fin à leur terreur.",
        ]);

        $gqPortail = BookFactory::new([
            'title'                   => 'Le Portail du Destin',
            'originalTitle'           => 'The Gateway of Doom',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $grailquest,
            'pages'                   => 200,
            'paragraphs'              => 295,
            'frenchPublicationYear'   => 1985,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 3,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Un portail démoniaque s'est ouvert dans les terres de Camelot. Ses créatures infernales ravagent le royaume. Seul vous pouvez le refermer.",
        ]);

        $gqVoyage = BookFactory::new([
            'title'                   => 'Voyage dans la Terreur',
            'originalTitle'           => 'Voyage of Terror',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $grailquest,
            'pages'                   => 200,
            'paragraphs'              => 295,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 4,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Merlin vous envoie sur un navire maudit errant dans des mers mystérieuses. L'équipage s'est transformé en créatures cauchemardesques.",
        ]);

        foreach ([$gqChateau, $gqDragons, $gqPortail, $gqVoyage] as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — Blood Sword
        // =====================================================================
        $bsGuerriers = BookFactory::new([
            'title'                   => 'Les Guerriers du Roi',
            'originalTitle'           => 'The Battlepits of Krarth',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $bloodSword,
            'pages'                   => 256,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1988,
            'originalPublicationYear' => 1987,
            'volumeNumber'            => 1,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Les fosses de combat de Krarth : une arène souterraine mortelle. Jusqu'à quatre joueurs s'affrontent et s'allient pour en récupérer le légendaire trésor.",
        ]);

        $bsRoyaume = BookFactory::new([
            'title'                   => 'Le Royaume Maudit',
            'originalTitle'           => 'The Kingdom of Wyrd',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $bloodSword,
            'pages'                   => 256,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1988,
            'originalPublicationYear' => 1987,
            'volumeNumber'            => 2,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Un royaume entier frappé par une malédiction ancienne. Quatre héros de classe différente doivent unir leurs forces pour briser le sort maléfique.",
        ]);

        $bsSeigneur = BookFactory::new([
            'title'                   => 'Le Seigneur des Enfers',
            'originalTitle'           => 'The Demon\'s Claw',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $bloodSword,
            'pages'                   => 272,
            'paragraphs'              => 420,
            'frenchPublicationYear'   => 1989,
            'originalPublicationYear' => 1988,
            'volumeNumber'            => 3,
            'languages'               => ['fr', 'en'],
            'summary'                 => "La griffe du démon menace le monde des mortels. Quatre champions doivent descendre aux enfers pour en ramener l'Épée de Sang enchantée.",
        ]);

        $bsGlaive = BookFactory::new([
            'title'                   => 'Le Glaive des Ténèbres',
            'originalTitle'           => 'Bloodsword',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $bloodSword,
            'pages'                   => 272,
            'paragraphs'              => 420,
            'frenchPublicationYear'   => 1990,
            'originalPublicationYear' => 1989,
            'volumeNumber'            => 4,
            'languages'               => ['fr', 'en'],
            'summary'                 => "L'Épée de Sang est forgée. Mais pour vaincre le Seigneur des Enfers, vous devez encore traverser son domaine et affronter ses légions démoniaques.",
        ]);

        foreach ([$bsGuerriers, $bsRoyaume, $bsSeigneur, $bsGlaive] as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — La Voie du Tigre
        // =====================================================================
        $vtEnnemi = BookFactory::new([
            'title'                   => "L'Ennemi des Dieux",
            'originalTitle'           => 'Avenger!',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $voieTigre,
            'pages'                   => 208,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 1,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Élève du monastère de Kwon, vous incarnez un ninja accompli. Votre maître a été tué par Shadazar. Votre vengeance ne peut être qu'impitoyable.",
        ]);

        $vtArene = BookFactory::new([
            'title'                   => "L'Arène des Dieux",
            'originalTitle'           => 'Assassin!',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $voieTigre,
            'pages'                   => 224,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1986,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 2,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Shadazar vaincu, un nouveau mal émerge. La cité de Harith est tombée sous l'emprise d'un seigneur de guerre. Votre mission d'assassin commence.",
        ]);

        $vtPortes = BookFactory::new([
            'title'                   => 'Au-Delà des Portes',
            'originalTitle'           => 'Usurper!',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $gallimard,
            'collection'              => $voieTigre,
            'pages'                   => 224,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1987,
            'originalPublicationYear' => 1986,
            'volumeNumber'            => 3,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Un usurpateur siège sur le trône de Irsmuncast. Vos arts martiaux et votre intelligence seront mis à rude épreuve pour renverser ce tyran.",
        ]);

        foreach ([$vtEnnemi, $vtArene, $vtPortes] as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — Chair de Poule
        // =====================================================================
        $cpCamp = BookFactory::new([
            'title'                   => 'Bienvenue à Camp Cauchemar',
            'originalTitle'           => 'Welcome to Camp Nightmare',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $folio,
            'collection'              => $chairDePoule,
            'pages'                   => 144,
            'paragraphs'              => 22,
            'frenchPublicationYear'   => 1993,
            'originalPublicationYear' => 1993,
            'volumeNumber'            => 1,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Votre séjour au camp d'été tourne au cauchemar. Vos camarades disparaissent un à un et les moniteurs cachent un terrible secret.",
        ]);

        $cpNeige = BookFactory::new([
            'title'                   => 'Le Bonhomme de Neige qui Marche dans la Nuit',
            'originalTitle'           => 'Beware, the Snowman',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $folio,
            'collection'              => $chairDePoule,
            'pages'                   => 144,
            'paragraphs'              => 20,
            'frenchPublicationYear'   => 1993,
            'originalPublicationYear' => 1993,
            'volumeNumber'            => 2,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Le bonhomme de neige que vous avez construit se met à bouger la nuit. Et il ne semble vraiment pas avoir de bonnes intentions envers vous.",
        ]);

        $cpMasque = BookFactory::new([
            'title'                   => 'Le Masque Hanté',
            'originalTitle'           => 'The Haunted Mask',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $folio,
            'collection'              => $chairDePoule,
            'pages'                   => 144,
            'paragraphs'              => 20,
            'frenchPublicationYear'   => 1994,
            'originalPublicationYear' => 1993,
            'volumeNumber'            => 3,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Vous avez trouvé un masque terrifiant dans un magasin mystérieux. Impossible de l'enlever — et il commence à changer votre personnalité de manière inquiétante.",
        ]);

        $cpMomie = BookFactory::new([
            'title'                   => 'La Momie Attaque',
            'originalTitle'           => 'Return of the Mummy',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $folio,
            'collection'              => $chairDePoule,
            'pages'                   => 160,
            'paragraphs'              => 22,
            'frenchPublicationYear'   => 1994,
            'originalPublicationYear' => 1994,
            'volumeNumber'            => 4,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Lors d'une fouille archéologique en Égypte, vous réveillez une malédiction vieille de 4000 ans. La momie du pharaon est bien vivante et assoiffée de vengeance.",
        ]);

        $cpRuelle = BookFactory::new([
            'title'                   => 'La Ruelle de la Peur',
            'originalTitle'           => 'One Day at Horrorland',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $folio,
            'collection'              => $chairDePoule,
            'pages'                   => 144,
            'paragraphs'              => 20,
            'frenchPublicationYear'   => 1994,
            'originalPublicationYear' => 1994,
            'volumeNumber'            => 5,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Un parc d'attractions nommé Horrorland. Les attractions ont l'air vraies... un peu trop. Et il n'y a pas de sortie visible.",
        ]);

        $cpEcole = BookFactory::new([
            'title'                   => "L'École Fantôme",
            'originalTitle'           => 'Ghost School',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $folio,
            'collection'              => $chairDePoule,
            'pages'                   => 144,
            'paragraphs'              => 20,
            'frenchPublicationYear'   => 1995,
            'originalPublicationYear' => 1994,
            'volumeNumber'            => 6,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Votre nouvelle école est hantée par des fantômes d'élèves disparus. Chaque nuit, ils reviennent et cherchent à vous entraîner avec eux.",
        ]);

        foreach ([$cpCamp, $cpNeige, $cpMasque, $cpMomie, $cpRuelle, $cpEcole] as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — Les Maîtres des Ténèbres
        // =====================================================================
        $mtCrypte = BookFactory::new([
            'title'                   => 'La Crypte des Damnés',
            'originalTitle'           => 'Howl of the Werewolf',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $rageot,
            'collection'              => $maitreTenebres,
            'pages'                   => 240,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1996,
            'originalPublicationYear' => 1995,
            'volumeNumber'            => 1,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Une crypte oubliée sous un vieux manoir breton. Des créatures maudites vous attendent dans ses couloirs sombres tapissés de toiles d'araignée.",
        ]);

        $mtAbbaye = BookFactory::new([
            'title'                   => "L'Abbaye des Cauchemars",
            'originalTitle'           => 'Night of the Necromancer',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $rageot,
            'collection'              => $maitreTenebres,
            'pages'                   => 240,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1996,
            'originalPublicationYear' => 1995,
            'volumeNumber'            => 2,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Une abbaye en ruine est le repaire d'un nécromancien qui commande une armée de morts-vivants. Le village voisin vit dans la terreur.",
        ]);

        $mtCimetiere = BookFactory::new([
            'title'                   => "Le Cimetière des Âmes Perdues",
            'originalTitle'           => 'Vault of the Vampire',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $rageot,
            'collection'              => $maitreTenebres,
            'pages'                   => 240,
            'paragraphs'              => 400,
            'frenchPublicationYear'   => 1997,
            'originalPublicationYear' => 1996,
            'volumeNumber'            => 3,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Un vampire millénaire règne sur un cimetière qui dévore les âmes des vivants. Vous devez trouver son cercueil et y planter un pieu avant l'aube.",
        ]);

        foreach ([$mtCrypte, $mtAbbaye, $mtCimetiere] as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — Star Challenge
        // =====================================================================
        $scMission = BookFactory::new([
            'title'                   => 'Mission Cosmique',
            'originalTitle'           => 'Cosmic Mission',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $flammarion,
            'collection'              => $starChallenge,
            'pages'                   => 192,
            'paragraphs'              => 280,
            'frenchPublicationYear'   => 1987,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 1,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Capitaine d'un vaisseau de reconnaissance, vous partez explorer une galaxie inconnue. Contact avec une civilisation extraterrestre — hostile ou pacifique ?",
        ]);

        $scPlanete = BookFactory::new([
            'title'                   => 'La Planète des Robots',
            'originalTitle'           => 'Robot Planet',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $flammarion,
            'collection'              => $starChallenge,
            'pages'                   => 192,
            'paragraphs'              => 280,
            'frenchPublicationYear'   => 1987,
            'originalPublicationYear' => 1985,
            'volumeNumber'            => 2,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Une planète entière contrôlée par des robots devenus autonomes et hostiles. Reprogrammez leur intelligence artificielle centrale pour sauver les colons humains.",
        ]);

        $scInvasion = BookFactory::new([
            'title'                   => "L'Invasion Fantôme",
            'originalTitle'           => 'Ghost Invasion',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $flammarion,
            'collection'              => $starChallenge,
            'pages'                   => 200,
            'paragraphs'              => 290,
            'frenchPublicationYear'   => 1988,
            'originalPublicationYear' => 1986,
            'volumeNumber'            => 3,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Des vaisseaux fantômes invisibles aux radars envahissent le secteur. Leur origine est inconnue et leurs armes redoutables. Défendez la station spatiale.",
        ]);

        foreach ([$scMission, $scPlanete, $scInvasion] as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — Duel Masters
        // =====================================================================
        $dmBataille = BookFactory::new([
            'title'                   => 'La Bataille de l\'Espace',
            'originalTitle'           => 'Space Battle',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $nathan,
            'collection'              => $duelMasters,
            'pages'                   => 192,
            'paragraphs'              => 300,
            'frenchPublicationYear'   => 1988,
            'originalPublicationYear' => 1987,
            'volumeNumber'            => 1,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Amiral d'une flotte spatiale, vous devez repousser une invasion extraterrestre massive. Chaque décision stratégique peut faire basculer la guerre.",
        ]);

        $dmFrontiere = BookFactory::new([
            'title'                   => 'La Frontière des Étoiles',
            'originalTitle'           => 'Star Frontier',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $nathan,
            'collection'              => $duelMasters,
            'pages'                   => 200,
            'paragraphs'              => 310,
            'frenchPublicationYear'   => 1989,
            'originalPublicationYear' => 1988,
            'volumeNumber'            => 2,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Aux confins de la galaxie explorée, une colonie humaine est coupée du reste de l'humanité. Traversez la Zone de Silence pour les rejoindre.",
        ]);

        foreach ([$dmBataille, $dmFrontiere] as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — Destination Aventure
        // =====================================================================
        $daSecret = BookFactory::new([
            'title'                   => 'Le Secret des Tombes',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $lpj,
            'collection'              => $destAventure,
            'pages'                   => 176,
            'paragraphs'              => 250,
            'frenchPublicationYear'   => 1989,
            'originalPublicationYear' => 1989,
            'volumeNumber'            => 1,
            'languages'               => ['fr'],
            'summary'                 => "Une mission archéologique en Égypte vire au thriller. Des trafiquants d'antiquités veulent mettre la main sur le trésor que vous venez de découvrir.",
        ]);

        $daAgent = BookFactory::new([
            'title'                   => 'Agent Double',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $lpj,
            'collection'              => $destAventure,
            'pages'                   => 180,
            'paragraphs'              => 260,
            'frenchPublicationYear'   => 1990,
            'originalPublicationYear' => 1990,
            'volumeNumber'            => 2,
            'languages'               => ['fr'],
            'summary'                 => "Une organisation criminelle internationale vous a infiltré comme agent double. Qui sont les bons ? Qui sont les méchants ? Vous devez déjouer un complot mondial.",
        ]);

        foreach ([$daSecret, $daAgent] as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — Agents Secrets
        // =====================================================================
        $asOperation = BookFactory::new([
            'title'                   => "Opération Scorpion",
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $hachette,
            'collection'              => $espionnage,
            'pages'                   => 208,
            'paragraphs'              => 320,
            'frenchPublicationYear'   => 1987,
            'originalPublicationYear' => 1986,
            'volumeNumber'            => 1,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Agent du MI6, vous êtes infiltré dans un réseau terroriste international. L'opération Scorpion : neutraliser leur chef avant qu'il ne déclenche la troisième guerre mondiale.",
        ]);

        $asReseau = BookFactory::new([
            'title'                   => 'Le Réseau Fantôme',
            'status'                  => BookStatus::PUBLISHED,
            'editor'                  => $hachette,
            'collection'              => $espionnage,
            'pages'                   => 208,
            'paragraphs'              => 320,
            'frenchPublicationYear'   => 1987,
            'originalPublicationYear' => 1986,
            'volumeNumber'            => 2,
            'languages'               => ['fr', 'en'],
            'summary'                 => "Un réseau d'espions fantômes opère en Europe. Leurs identités sont inconnues, leurs méthodes impitoyables. Vous avez 48 heures pour les démanteler.",
        ]);

        foreach ([$asOperation, $asReseau] as $b) {
            $manager->persist($b);
        }

        // =====================================================================
        // BOOKS — Standalone
        // =====================================================================
        $pending = BookFactory::new([
            'title'  => 'Livre en Attente de Modération',
            'status' => BookStatus::PENDING,
            'editor' => $folio,
        ]);
        $manager->persist($pending);

        $manager->flush();

        // =====================================================================
        // CONTRIBUTIONS
        // =====================================================================
        $contribs = [
            // Défis Fantastiques
            [$jackson,    $dfSorcier,     ContributionRole::Author],
            [$livingstone, $dfSorcier,    ContributionRole::Author],
            [$nicholson,  $dfSorcier,     ContributionRole::Illustrator],
            [$anonyme,    $dfSorcier,     ContributionRole::Traductor],
            [$jackson,    $dfCitadelle,   ContributionRole::Author],
            [$livingstone, $dfCitadelle,  ContributionRole::Illustrator],
            [$livingstone, $dfForet,      ContributionRole::Author],
            [$nicholson,  $dfForet,       ContributionRole::Illustrator],
            [$jackson,    $dfEtoile,      ContributionRole::Author],
            [$livingstone, $dfVille,      ContributionRole::Author],
            [$miller,     $dfVille,       ContributionRole::Illustrator],
            [$livingstone, $dfLabyrinthe, ContributionRole::Author],
            [$nicholson,  $dfLabyrinthe,  ContributionRole::Illustrator],
            [$livingstone, $dfIle,        ContributionRole::Author],
            [$mason,      $dfMarais,      ContributionRole::Author],
            [$jackson,    $dfCavernes,    ContributionRole::Author],
            [$miller,     $dfCavernes,    ContributionRole::Illustrator],
            [$jackson,    $dfFear,        ContributionRole::Author],
            [$mason,      $dfTalisman,    ContributionRole::Author],
            [$morris,     $dfTalisman,    ContributionRole::Author],
            [$miller,     $dfTalisman,    ContributionRole::Illustrator],
            [$waterfield, $dfSeides,      ContributionRole::Author],
            [$waterfield, $dfChevaliers,  ContributionRole::Author],
            [$livingstone, $dfTemple,     ContributionRole::Author],
            [$nicholson,  $dfTemple,      ContributionRole::Illustrator],
            [$thomson,    $dfChassPrimes, ContributionRole::Author],
            [$gascoigne,  $dfFleche,      ContributionRole::Author],
            [$mason,      $dfFleche,      ContributionRole::Author],
            [$green,      $dfSeides2,     ContributionRole::Author],
            [$miller,     $dfSeides2,     ContributionRole::Illustrator],
            // Loup Solitaire
            [$dever,  $lsFaucons,  ContributionRole::Author],
            [$chalk,  $lsFaucons,  ContributionRole::Illustrator],
            [$dever,  $lsMarais,   ContributionRole::Author],
            [$chalk,  $lsMarais,   ContributionRole::Illustrator],
            [$dever,  $lsMort,     ContributionRole::Author],
            [$chalk,  $lsMort,     ContributionRole::Illustrator],
            [$dever,  $lsArmees,   ContributionRole::Author],
            [$dever,  $lsPlaines,  ContributionRole::Author],
            [$dever,  $lsNid,      ContributionRole::Author],
            [$dever,  $lsPelerins, ContributionRole::Author],
            [$dever,  $lsChateau,  ContributionRole::Author],
            [$dever,  $lsGardes,   ContributionRole::Author],
            [$dever,  $lsSables,   ContributionRole::Author],
            // Sorcellerie!
            [$jackson, $soCity,     ContributionRole::Author],
            [$jackson, $soKhare,    ContributionRole::Author],
            [$jackson, $soSerpents, ContributionRole::Author],
            [$jackson, $soCouronne, ContributionRole::Author],
            // Grailquest
            [$brennan, $gqChateau, ContributionRole::Author],
            [$brennan, $gqDragons, ContributionRole::Author],
            [$brennan, $gqPortail, ContributionRole::Author],
            [$brennan, $gqVoyage,  ContributionRole::Author],
            // Blood Sword
            [$morris,  $bsGuerriers, ContributionRole::Author],
            [$thomson, $bsGuerriers, ContributionRole::Author],
            [$morris,  $bsRoyaume,   ContributionRole::Author],
            [$thomson, $bsRoyaume,   ContributionRole::Author],
            [$morris,  $bsSeigneur,  ContributionRole::Author],
            [$thomson, $bsSeigneur,  ContributionRole::Author],
            [$morris,  $bsGlaive,    ContributionRole::Author],
            [$thomson, $bsGlaive,    ContributionRole::Author],
            // La Voie du Tigre
            [$smith,   $vtEnnemi, ContributionRole::Author],
            [$thomson, $vtEnnemi, ContributionRole::Author],
            [$smith,   $vtArene,  ContributionRole::Author],
            [$thomson, $vtArene,  ContributionRole::Author],
            [$smith,   $vtPortes, ContributionRole::Author],
            [$thomson, $vtPortes, ContributionRole::Author],
            // Chair de Poule
            [$stine, $cpCamp,   ContributionRole::Author],
            [$stine, $cpNeige,  ContributionRole::Author],
            [$stine, $cpMasque, ContributionRole::Author],
            [$stine, $cpMomie,  ContributionRole::Author],
            [$stine, $cpRuelle, ContributionRole::Author],
            [$stine, $cpEcole,  ContributionRole::Author],
            // Les Maîtres des Ténèbres
            [$green, $mtCrypte,    ContributionRole::Author],
            [$green, $mtAbbaye,    ContributionRole::Author],
            [$green, $mtCimetiere, ContributionRole::Author],
            // Star Challenge
            [$chalk, $scMission, ContributionRole::Author],
            [$chalk, $scPlanete, ContributionRole::Author],
            [$chalk, $scInvasion, ContributionRole::Author],
            // Duel Masters
            [$mason, $dmBataille,  ContributionRole::Author],
            [$mason, $dmFrontiere, ContributionRole::Author],
            // Destination Aventure
            [$lambert, $daSecret, ContributionRole::Author],
            [$lambert, $daAgent,  ContributionRole::Author],
            // Agents Secrets
            [$gascoigne, $asOperation, ContributionRole::Author],
            [$gascoigne, $asReseau,    ContributionRole::Author],
            // Pending
            [$livingstone, $pending, ContributionRole::Traductor],
        ];

        foreach ($contribs as [$contributor, $book, $role]) {
            $manager->persist(ContributionFactory::new([
                'contributor' => $contributor,
                'book'        => $book,
                'role'        => $role,
            ]));
        }

        $manager->flush();

        // =====================================================================
        // USERS
        // =====================================================================
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

        // =====================================================================
        // USER BOOKS
        // =====================================================================
        // [user, book, isOwned, isToRead, isToBuy, isFavorite]
        // LU and PAS_DANS_MA_COLLECTION entries omitted (deleted by migration — clean start)
        $userBooks = [
            // utilisateur — défis fantastiques
            [$user, $dfSorcier,   true,  false, false, true],
            [$user, $dfCitadelle, false, false, true,  false],
            [$user, $dfForet,     true,  false, false, false],
            [$user, $dfVille,     true,  false, false, false],
            [$user, $dfTemple,    false, true,  false, false],
            // utilisateur — loup solitaire
            [$user, $lsFaucons,  true,  false, false, true],
            [$user, $lsMarais,   true,  false, false, false],
            [$user, $lsMort,     true,  false, false, false],
            [$user, $lsArmees,   false, false, true,  false],
            [$user, $lsPlaines,  false, false, true,  false],
            // utilisateur — sorcellerie
            [$user, $soCity,     true,  false, false, true],
            [$user, $soKhare,    true,  false, false, false],
            [$user, $soSerpents, false, false, true,  false],
            [$user, $soCouronne, false, false, true,  false],
            // utilisateur — voie du tigre
            [$user, $vtEnnemi, true,  false, false, true],
            [$user, $vtArene,  false, false, true,  false],
            // utilisateur — chair de poule
            [$user, $cpMomie, false, true, false, false],
            // admin
            [$admin, $lsFaucons,  true, false, false, true],
            [$admin, $lsMarais,   true, false, false, false],
            [$admin, $soCity,     true, false, false, true],
            [$admin, $soCouronne, true, false, false, true],
            [$admin, $vtEnnemi,   true, false, false, false],
            // moderator
            [$moderator, $dfSorcier,   true,  false, false, false],
            [$moderator, $dfCitadelle, true,  false, false, false],
            [$moderator, $dfForet,     false, true,  false, false],
            [$moderator, $lsFaucons,   true,  false, false, false],
            [$moderator, $mtAbbaye,    false, false, true,  false],
        ];

        foreach ($userBooks as [$theUser, $book, $isOwned, $isToRead, $isToBuy, $isFav]) {
            $ub = new UserBook($theUser, $book);
            $ub->setIsOwned($isOwned)->setIsToRead($isToRead)->setIsToBuy($isToBuy)->setIsFavorite($isFav);
            $manager->persist($ub);
        }

        $manager->flush();
    }
}
