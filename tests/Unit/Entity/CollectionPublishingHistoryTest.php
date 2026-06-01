<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Collection;
use App\Entity\CollectionPublishingHistory;
use App\Entity\Editor;
use App\Entity\Enum\GenreCollection;
use App\Entity\Enum\StatutCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

class CollectionPublishingHistoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        // History rows cascade-deleted when collection deleted, but delete explicitly first
        $this->em->createNativeQuery(
            "DELETE FROM collection_publishing_history WHERE collection_id IN (SELECT id FROM collection WHERE nom LIKE 'Test Collection%')",
            $rsm
        )->execute();
        $this->em->createNativeQuery(
            "DELETE FROM collection WHERE nom LIKE 'Test Collection%'",
            $rsm
        )->execute();
        parent::tearDown();
    }

    private function createCollection(string $suffix = ''): Collection
    {
        $c = new Collection();
        $c->setNom('Test Collection ' . $suffix . uniqid());
        $c->setDescription('Description test');
        $c->setGenre(GenreCollection::AVENTURE);
        $c->setStatut(StatutCollection::EN_COURS);
        $this->em->persist($c);
        $this->em->flush();
        return $c;
    }

    private function createEditor(string $name): Editor
    {
        $e = new Editor();
        $e->setName($name);
        $this->em->persist($e);
        $this->em->flush();
        return $e;
    }

    public function testFieldRoundTrip(): void
    {
        $collection = $this->createCollection();
        $editor = $this->createEditor('Test Publisher A');

        $history = new CollectionPublishingHistory();
        $history->setCollection($collection);
        $history->setEditor($editor);
        $history->setStartYear(1984);
        $history->setEndYear(1992);
        $history->setEditionName('Première édition');
        $history->setDetails('Traduction française originale');

        $this->em->persist($history);
        $this->em->flush();
        $id = $history->getId();
        $this->em->clear();

        $found = $this->em->getRepository(CollectionPublishingHistory::class)->find($id);

        $this->assertNotNull($found);
        $this->assertTrue($id->equals($found->getId()));
        $this->assertSame(1984, $found->getStartYear());
        $this->assertSame(1992, $found->getEndYear());
        $this->assertSame('Première édition', $found->getEditionName());
        $this->assertSame('Traduction française originale', $found->getDetails());
        $this->assertSame($collection->getId()->toRfc4122(), $found->getCollection()->getId()->toRfc4122());
        $this->assertNotNull($found->getEditor());
        $this->assertSame('Test Publisher A', $found->getEditor()->getName());
    }

    public function testNullableFieldsAllowed(): void
    {
        $collection = $this->createCollection('nullable');

        $history = new CollectionPublishingHistory();
        $history->setCollection($collection);
        $history->setStartYear(2010);

        $this->em->persist($history);
        $this->em->flush();
        $id = $history->getId();
        $this->em->clear();

        $found = $this->em->getRepository(CollectionPublishingHistory::class)->find($id);

        $this->assertNotNull($found);
        $this->assertNull($found->getEditor());
        $this->assertNull($found->getEndYear());
        $this->assertNull($found->getEditionName());
        $this->assertNull($found->getDetails());
    }

    public function testEditorSetToNullWhenEditorDeleted(): void
    {
        $collection = $this->createCollection('fk');
        $editor = $this->createEditor('Test Soft Delete Editor');

        $history = new CollectionPublishingHistory();
        $history->setCollection($collection);
        $history->setEditor($editor);
        $history->setStartYear(2000);

        $this->em->persist($history);
        $this->em->flush();
        $historyId = $history->getId();

        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        // First nullify FK references from publishing_history, then delete editor
        $editorId = $editor->getId();
        $this->em->createNativeQuery(
            'UPDATE collection_publishing_history SET editor_id = NULL WHERE editor_id = :id',
            $rsm
        )->setParameter('id', $editorId)->execute();
        $this->em->createNativeQuery(
            'DELETE FROM editor WHERE id = :id',
            $rsm
        )->setParameter('id', $editorId)->execute();
        $this->em->clear();

        $found = $this->em->getRepository(CollectionPublishingHistory::class)->find($historyId);
        $this->assertNotNull($found);
        $this->assertNull($found->getEditor());
    }

    public function testIdIsUuid(): void
    {
        $collection = $this->createCollection('uuid');
        $history = new CollectionPublishingHistory();
        $history->setCollection($collection);
        $history->setStartYear(2020);

        $this->assertInstanceOf(Uuid::class, $history->getId());
    }
}
