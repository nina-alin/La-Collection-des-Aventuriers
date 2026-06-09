# Data Model: Salle de Modération — Intégration du Design

## Aucune nouvelle entité Doctrine / aucune migration

Cette feature n'ajoute aucune colonne, table, ou relation. Tous les nouveaux types sont des DTOs PHP purs.

---

## DTO: `DiffField`

```php
// src/Dto/DiffField.php
namespace App\Dto;

enum DiffFieldStatus: string {
    case ADDED     = 'ADDED';
    case REMOVED   = 'REMOVED';
    case REPLACED  = 'REPLACED';
    case UNCHANGED = 'UNCHANGED';
}

class DiffField
{
    public function __construct(
        public readonly string $key,           // clé technique (ex: 'title', 'biography')
        public readonly string $label,         // label FR (ex: 'Titre', 'Biographie')
        public readonly DiffFieldStatus $status,
        public readonly mixed $currentValue,   // valeur actuelle (null si ADDED)
        public readonly mixed $proposedValue,  // valeur proposée (null si REMOVED)
        public readonly ?string $annotatedHtml = null, // HTML annoté <ins>/<del> pour champs text (REPLACED uniquement)
        public readonly string $type = 'scalar', // 'scalar' | 'text' | 'tags'
    ) {}
}
```

---

## DTO: `DiffResult`

```php
// src/Dto/DiffResult.php
namespace App\Dto;

class DiffResult
{
    /** @param DiffField[] $fields */
    public function __construct(
        public readonly array $fields,
        public readonly int $addedCount,
        public readonly int $replacedCount,
        public readonly int $removedCount,
    ) {}

    public function hasChanges(): bool
    {
        return $this->addedCount + $this->replacedCount + $this->removedCount > 0;
    }
}
```

---

## Interface: `EntityNormalizerInterface`

```php
// src/Service/Normalizer/EntityNormalizerInterface.php
namespace App\Service\Normalizer;

use App\Entity\Enum\SuggestionEntityType;

interface EntityNormalizerInterface
{
    /** @return array<string, mixed> Données normalisées de l'entité */
    public function normalize(object $entity): array;

    /** @return array<string, string> Mapping clé → label FR */
    public function getFieldLabels(): array;

    public function getSupportedType(): SuggestionEntityType;
}
```

---

## Normalizers par type

| Type | Normalizer | Entité source | Champs principaux |
|------|-----------|---------------|-------------------|
| `BOOK` | `BookNormalizer` | `Book` | title, originalTitle, isbn, pages, paragraphs, frenchPublicationYear, originalPublicationYear, editionInfo, saga |
| `AUTHOR` | `ContributorNormalizer` | `Contributor` | firstName, lastName, pseudo, nationality, biography, birthDate, deathDate |
| `ILLUSTRATOR` | `ContributorNormalizer` | `Contributor` | (idem AUTHOR) |
| `TRADUCTOR` | `ContributorNormalizer` | `Contributor` | (idem AUTHOR) |
| `EDITOR` | `EditorNormalizer` | `Editor` | name |
| `COLLECTION` | `CollectionNormalizer` | `Collection` | nom, slug, genre, statut, description |

**Note**: `ContributorNormalizer` est instancié une fois mais enregistré dans le ServiceLocator avec 3 clés (`AUTHOR`, `ILLUSTRATOR`, `TRADUCTOR`). Cela est réalisé en services.yaml via des alias de service ou en déclarant 3 entrées pointant vers le même service ID.

---

## Service: `DiffService`

```php
// src/Service/DiffService.php
namespace App\Service;

use App\Dto\DiffResult;
use App\Entity\Suggestion;
use App\Entity\Enum\SuggestionEntityType;
use App\Service\Normalizer\EntityNormalizerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class DiffService
{
    /** @param ServiceLocator<EntityNormalizerInterface> $normalizers */
    public function __construct(
        private readonly ServiceLocator $normalizers,
    ) {}

    public function computeForSuggestion(Suggestion $suggestion, ?object $sourceEntity): DiffResult
    {
        $normalizer = $this->normalizers->get($suggestion->getEntityType()->value);
        $currentData  = $sourceEntity !== null ? $normalizer->normalize($sourceEntity) : [];
        $proposedData = $suggestion->getFormData();
        $labels       = $normalizer->getFieldLabels();

        return $this->compute($currentData, $proposedData, $labels);
    }

    /** @param array<string, mixed> $current @param array<string, mixed> $proposed @param array<string, string> $labels */
    public function compute(array $current, array $proposed, array $labels): DiffResult { /* ... */ }
}
```

---

## ModerationService — modifications

Signature mise à jour :

```php
public function moderateSuggestion(
    User $moderator,
    Suggestion $suggestion,
    SuggestionStatus $newStatus,
    ?string $refusalReason = null,
): void
```

Quand `$newStatus === REFUSED` et `$refusalReason !== null` :
1. Créer `SuggestionRefusal`, setter `suggestion`, `moderator`, `reason`
2. Persister via `EntityManager`
3. Flush

---

## États visuels diff (mapping status → classes CSS design)

| Status | Colonne gauche | Colonne droite | Badge |
|--------|---------------|----------------|-------|
| ADDED | — (vide) | valeur avec classe `.ins` | vert "AJOUTÉ" |
| REMOVED | valeur avec classe `.del` | — (vide) | rouge "SUPPRIMÉ" |
| REPLACED | ancienne valeur `.del` | nouvelle valeur `.ins` (ou annotatedHtml) | — |
| UNCHANGED | valeur normale | valeur normale | grisé / masquable |

Classes CSS issues de `design/pages/moderation.html` :
- `.split` — wrapper colonnes
- `.split-col.now` — colonne gauche (données actuelles)
- `.split-col.next` — colonne droite (nouvelles données)
- `.ins` — insertion (vert souligné)
- `.del` — suppression (rouge barré)
- `.flux` — grille flux : `grid-template-columns: minmax(0,1fr) 320px` pour ≥ 1100px
