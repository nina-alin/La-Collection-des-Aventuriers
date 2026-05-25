<?php

declare(strict_types=1);

namespace App\DataFixtures\Factory;

use App\Entity\Book;
use App\Entity\Collection as CollectionEntity;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;

class BookFactory
{
    public static function new(array $overrides = []): Book
    {
        $book = new Book();
        $book->setTitle($overrides['title'] ?? 'Livre Test ' . substr(uniqid(), -6));
        $book->setStatus($overrides['status'] ?? BookStatus::PUBLISHED);

        if (isset($overrides['editor'])) {
            $book->setEditor($overrides['editor']);
        }
        if (isset($overrides['collection'])) {
            $book->setCollection($overrides['collection']);
        }
        if (array_key_exists('originalTitle', $overrides)) {
            $book->setOriginalTitle($overrides['originalTitle']);
        }
        if (array_key_exists('isbn', $overrides)) {
            $book->setIsbn($overrides['isbn']);
        }
        if (array_key_exists('pages', $overrides)) {
            $book->setPages($overrides['pages']);
        }
        if (array_key_exists('paragraphs', $overrides)) {
            $book->setParagraphs($overrides['paragraphs']);
        }
        if (array_key_exists('frenchPublicationYear', $overrides)) {
            $book->setFrenchPublicationYear($overrides['frenchPublicationYear']);
        }
        if (array_key_exists('originalPublicationYear', $overrides)) {
            $book->setOriginalPublicationYear($overrides['originalPublicationYear']);
        }
        if (array_key_exists('volumeNumber', $overrides)) {
            $book->setVolumeNumber($overrides['volumeNumber']);
        }
        if (array_key_exists('summary', $overrides)) {
            $book->setSummary($overrides['summary']);
        }
        if (array_key_exists('languages', $overrides)) {
            $book->setLanguages($overrides['languages']);
        }

        return $book;
    }
}
