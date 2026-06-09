<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\DiffField;
use App\Dto\DiffFieldStatus;
use App\Dto\DiffResult;
use App\Entity\Suggestion;
use App\Service\Normalizer\EntityNormalizerInterface;
use Jfcherng\Diff\DiffHelper;
use Symfony\Component\DependencyInjection\ServiceLocator;

class DiffService
{
    /** @param ServiceLocator<EntityNormalizerInterface> $normalizers */
    public function __construct(
        private readonly ServiceLocator $normalizers,
    ) {}

    public function computeForSuggestion(Suggestion $suggestion, ?object $sourceEntity): DiffResult
    {
        /** @var EntityNormalizerInterface $normalizer */
        $normalizer = $this->normalizers->get($suggestion->getEntityType()->value);
        $currentData = $sourceEntity !== null ? $normalizer->normalize($sourceEntity) : [];
        $proposedData = $suggestion->getFormData();
        $labels = $normalizer->getFieldLabels();
        $types = $normalizer->getFieldTypes();

        return $this->compute($currentData, $proposedData, $labels, $types);
    }

    /**
     * @param array<string, mixed>  $current
     * @param array<string, mixed>  $proposed
     * @param array<string, string> $labels
     * @param array<string, string> $types
     */
    public function compute(array $current, array $proposed, array $labels, array $types): DiffResult
    {
        $fields = [];
        $addedCount = 0;
        $replacedCount = 0;
        $removedCount = 0;

        $allKeys = array_unique(array_merge(array_keys($current), array_keys($proposed)));

        foreach ($allKeys as $key) {
            $label = $labels[$key] ?? $key;
            $type = $types[$key] ?? 'scalar';
            $hasCurrentKey = array_key_exists($key, $current);
            $hasProposedKey = array_key_exists($key, $proposed);
            $currentValue = $current[$key] ?? null;
            $proposedValue = $proposed[$key] ?? null;

            if (!$hasCurrentKey && $hasProposedKey) {
                $fields[] = new DiffField($key, $label, DiffFieldStatus::ADDED, null, $proposedValue, null, $type);
                ++$addedCount;
            } elseif ($hasCurrentKey && !$hasProposedKey) {
                $fields[] = new DiffField($key, $label, DiffFieldStatus::REMOVED, $currentValue, null, null, $type);
                ++$removedCount;
            } elseif ($currentValue === $proposedValue) {
                $fields[] = new DiffField($key, $label, DiffFieldStatus::UNCHANGED, $currentValue, $proposedValue, null, $type);
            } else {
                $annotatedHtml = null;
                if ($type === 'text') {
                    $annotatedHtml = $this->computeWordDiff(
                        (string) ($currentValue ?? ''),
                        (string) ($proposedValue ?? ''),
                    );
                }
                $fields[] = new DiffField($key, $label, DiffFieldStatus::REPLACED, $currentValue, $proposedValue, $annotatedHtml, $type);
                ++$replacedCount;
            }
        }

        return new DiffResult($fields, $addedCount, $replacedCount, $removedCount);
    }

    private function computeWordDiff(string $old, string $new): string
    {
        return DiffHelper::calculate(
            $old,
            $new,
            'Inline',
            ['context' => 3],
            ['detailLevel' => 'word', 'showHeader' => false, 'lineNumbers' => false],
        );
    }
}
