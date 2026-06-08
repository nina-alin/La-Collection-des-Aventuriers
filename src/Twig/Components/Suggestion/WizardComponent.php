<?php

namespace App\Twig\Components\Suggestion;

use App\Entity\Enum\SuggestionEntityType;
use App\Entity\Enum\SuggestionMode;
use App\Entity\User;
use App\Form\Suggestion\StepDetailsType;
use App\Form\Suggestion\StepTypeType;
use App\Service\CoverImageProcessor;
use App\Service\SuggestionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
class WizardComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public int $step = 1;

    #[LiveProp(writable: true)]
    public ?string $mode = null;

    #[LiveProp(writable: true)]
    public ?string $entityType = null;

    #[LiveProp(writable: true)]
    public ?string $sourceEntityId = null;

    #[LiveProp(writable: true)]
    public array $originalData = [];

    #[LiveProp(writable: true)]
    public array $formData = [];

    #[LiveProp(writable: true)]
    public ?string $coverImageTempPath = null;

    #[LiveProp(writable: true)]
    public array $errors = [];

    #[LiveProp(writable: true)]
    public bool $isSubmitting = false;

    public function __construct(
        private readonly SuggestionService $suggestionService,
        private readonly CoverImageProcessor $coverImageProcessor,
    ) {
    }

    #[ExposeInTemplate(name: 'pendingCount')]
    public function getPendingCount(): int
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if ($user === null) {
            return 0;
        }

        return $this->suggestionService->getPendingCount($user);
    }

    #[LiveAction]
    public function selectMode(#[LiveArg] string $val): void
    {
        $this->mode = $val;
    }

    #[LiveAction]
    public function selectEntityType(#[LiveArg] string $val): void
    {
        $this->entityType = $val;
    }

    #[LiveAction]
    public function goToStep(#[LiveArg] int $step): void
    {
        if ($step >= 1 && $step <= 4) {
            $this->step = $step;
        }
    }

    #[LiveAction]
    public function nextStep(): void
    {
        $this->errors = [];

        if ($this->step === 1) {
            $form = $this->createForm(StepTypeType::class, [
                'mode'       => $this->mode,
                'entityType' => $this->entityType,
            ]);
            $form->submit(['mode' => $this->mode, 'entityType' => $this->entityType]);

            if (!$form->isValid()) {
                $this->errors['step1'] = 'Veuillez sélectionner un mode et un type d\'entité.';
                return;
            }

            if (empty($this->formData)) {
                $this->formData = $this->buildEmptyFormData();
            }
        } elseif ($this->step === 2) {
            $form = $this->createForm(StepDetailsType::class, $this->formData, [
                'entity_type' => $this->entityType,
            ]);
            $form->submit($this->formData);

            if (!$form->isValid()) {
                foreach ($form->getErrors(true) as $error) {
                    $origin = $error->getOrigin();
                    $field  = $origin !== null ? $origin->getName() : 'details';
                    $this->errors[$field] = $error->getMessage();
                }
                return;
            }
        }

        if ($this->step < 4) {
            $this->step++;
        }
    }

    private const CONTRIBUTOR_TYPES = [
        SuggestionEntityType::AUTHOR->value,
        SuggestionEntityType::ILLUSTRATOR->value,
        SuggestionEntityType::TRADUCTOR->value,
    ];

    private function buildEmptyFormData(): array
    {
        if ($this->entityType === SuggestionEntityType::BOOK->value) {
            return array_fill_keys(
                ['title', 'subtitle', 'author', 'illustrator', 'traductor', 'editor', 'collection', 'isbn', 'publicationFr', 'originalEdition', 'paragraphs', 'backCoverText'],
                ''
            );
        }

        if (in_array($this->entityType, self::CONTRIBUTOR_TYPES, true)) {
            return array_fill_keys(
                ['firstName', 'lastName', 'pseudo', 'biography', 'nationality', 'birthDate', 'deathDate'],
                ''
            );
        }

        return ['name' => ''];
    }

    #[LiveAction]
    public function uploadCover(UploadedFile $file): void
    {
        try {
            $this->coverImageTempPath = $this->coverImageProcessor->process($file);
            unset($this->errors['cover']);
        } catch (\InvalidArgumentException $e) {
            $this->errors['cover'] = $e->getMessage();
        } catch (\RuntimeException $e) {
            $this->errors['cover'] = 'Erreur lors du traitement de l\'image. Réessayez.';
        }
    }

    #[LiveAction]
    public function submitSuggestion(): void
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if ($user === null) {
            return;
        }

        $form = $this->createForm(StepDetailsType::class, $this->formData, [
            'entity_type' => $this->entityType,
        ]);
        $form->submit($this->formData);

        if (!$form->isValid()) {
            $this->errors['submit'] = 'Les données du formulaire sont incomplètes ou invalides.';
            $this->step = 2;
            return;
        }

        $this->isSubmitting = true;
        $this->errors = [];

        try {
            $entityType = SuggestionEntityType::from($this->entityType);
            $mode       = SuggestionMode::from($this->mode);

            $this->suggestionService->submit(
                $user,
                $this->formData,
                $entityType,
                $mode,
                $this->sourceEntityId,
                $this->coverImageTempPath,
            );

            $this->step          = 1;
            $this->mode          = null;
            $this->entityType    = null;
            $this->sourceEntityId    = null;
            $this->originalData  = [];
            $this->formData      = [];
            $this->coverImageTempPath = null;
            $this->errors        = [];
            $this->isSubmitting  = false;

            $this->addFlash('success', 'Votre suggestion a été soumise à la modération.');
            $this->emit('suggestionSubmitted');
        } catch (\RuntimeException $e) {
            $this->errors['submit'] = $e->getMessage();
            $this->isSubmitting = false;
        }
    }

    #[LiveAction]
    public function validateField(#[LiveArg] string $field, #[LiveArg] mixed $value): void
    {
        unset($this->errors[$field]);

        if ($this->entityType === null) {
            return;
        }

        $form = $this->createForm(StepDetailsType::class, null, [
            'entity_type' => $this->entityType,
        ]);

        if (!$form->has($field)) {
            return;
        }

        $form->submit([$field => $value], false);

        foreach ($form->get($field)->getErrors() as $error) {
            $this->errors[$field] = $error->getMessage();
            break;
        }
    }

    #[LiveAction]
    public function selectSource(#[LiveArg] string $id, #[LiveArg] array $data): void
    {
        $this->sourceEntityId = $id;
        $this->originalData   = $data;
        $this->formData       = $data;
    }

    #[LiveAction]
    public function clearCoverOnModeChange(): void
    {
        $this->coverImageTempPath = null;
        unset($this->errors['cover']);
    }

    public function computeDiff(): array
    {
        if (empty($this->originalData)) {
            return [];
        }
        return array_filter(
            $this->formData,
            fn ($value, $key) => array_key_exists($key, $this->originalData)
                && $this->originalData[$key] !== $value,
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function canProceedToStep2(): bool
    {
        return $this->mode !== null && $this->entityType !== null;
    }

    #[ExposeInTemplate]
    public function canSubmit(): bool
    {
        return !$this->isSubmitting && empty($this->errors) && $this->hasRequiredFormData();
    }

    private function hasRequiredFormData(): bool
    {
        if ($this->entityType === null) {
            return false;
        }
        if ($this->entityType === SuggestionEntityType::BOOK->value) {
            return trim((string) ($this->formData['title'] ?? '')) !== '';
        }

        if (in_array($this->entityType, self::CONTRIBUTOR_TYPES, true)) {
            return trim((string) ($this->formData['firstName'] ?? '')) !== ''
                && trim((string) ($this->formData['lastName'] ?? '')) !== '';
        }

        return trim((string) ($this->formData['name'] ?? '')) !== '';
    }
}
