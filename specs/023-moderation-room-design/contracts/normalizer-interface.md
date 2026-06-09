# EntityNormalizerInterface Contract

## Interface

```php
namespace App\Service\Normalizer;

use App\Entity\Enum\SuggestionEntityType;

interface EntityNormalizerInterface
{
    /**
     * Normalize entity to flat array<string, mixed>.
     * Values must be scalar or array<string> (for tag fields).
     * Relations are flattened to IDs or display strings.
     */
    public function normalize(object $entity): array;

    /**
     * @return array<string, string> field key => French label
     */
    public function getFieldLabels(): array;

    public function getSupportedType(): SuggestionEntityType;
}
```

## Service tagging (`config/services.yaml`)

```yaml
services:
    App\Service\Normalizer\BookNormalizer:
        tags:
            - { name: app.entity_normalizer, key: BOOK }

    App\Service\Normalizer\ContributorNormalizer:
        tags:
            - { name: app.entity_normalizer, key: AUTHOR }
            - { name: app.entity_normalizer, key: ILLUSTRATOR }
            - { name: app.entity_normalizer, key: TRADUCTOR }

    App\Service\Normalizer\EditorNormalizer:
        tags:
            - { name: app.entity_normalizer, key: EDITOR }

    App\Service\Normalizer\CollectionNormalizer:
        tags:
            - { name: app.entity_normalizer, key: COLLECTION }

    App\Service\DiffService:
        arguments:
            $normalizers: !tagged_locator { tag: app.entity_normalizer, index_by: key }
```

## Normalizer field maps (reference)

### BookNormalizer
| Key | Label FR | Type |
|-----|----------|------|
| `title` | Titre | text |
| `originalTitle` | Titre original | scalar |
| `isbn` | ISBN | scalar |
| `pages` | Nombre de pages | scalar |
| `paragraphs` | Nombre de paragraphes | scalar |
| `frenchPublicationYear` | Année de publication (FR) | scalar |
| `originalPublicationYear` | Année de publication (original) | scalar |
| `editionInfo` | Informations d'édition | scalar |
| `saga` | Saga | scalar |

### ContributorNormalizer (AUTHOR / ILLUSTRATOR / TRADUCTOR)
| Key | Label FR | Type |
|-----|----------|------|
| `firstName` | Prénom | scalar |
| `lastName` | Nom | scalar |
| `pseudo` | Pseudonyme | scalar |
| `nationality` | Nationalité | scalar |
| `biography` | Biographie | text |
| `birthDate` | Date de naissance | scalar |
| `deathDate` | Date de décès | scalar |

### EditorNormalizer
| Key | Label FR | Type |
|-----|----------|------|
| `name` | Nom | scalar |

### CollectionNormalizer
| Key | Label FR | Type |
|-----|----------|------|
| `nom` | Nom | scalar |
| `slug` | Slug | scalar |
| `genre` | Genre | scalar |
| `statut` | Statut | scalar |
| `description` | Description | text |
