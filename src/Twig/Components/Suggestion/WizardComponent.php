<?php

namespace App\Twig\Components\Suggestion;

use App\Entity\Enum\SuggestionEntityType;
use App\Entity\Enum\SuggestionMode;
use App\Entity\User;
use App\Service\CoverImageProcessor;
use App\Service\SuggestionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

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

    public int $pendingCount = 0;

    public function __construct(
        private readonly SuggestionService $suggestionService,
        private readonly CoverImageProcessor $coverImageProcessor,
    ) {
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if ($user !== null) {
            $this->pendingCount = $this->suggestionService->getPendingCount($user);
        }
    }

    #[LiveAction]
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= 4) {
            $this->step = $step;
        }
    }

    #[LiveAction]
    public function nextStep(): void
    {
        $this->errors = [];

        if ($this->step === 1 && ($this->mode === null || $this->entityType === null)) {
            $this->errors['step1'] = 'Veuillez sélectionner un mode et un type d\'entité.';
            return;
        }

        if ($this->step < 4) {
            $this->step++;
        }
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

        if (empty($this->formData)) {
            $this->errors['form'] = 'Les données du formulaire sont incomplètes.';
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

            $this->pendingCount  = $this->suggestionService->getPendingCount($user);
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
    public function validateField(string $field, mixed $value): void
    {
        unset($this->errors[$field]);

        match ($field) {
            'paragraphs' => $this->validateParagraphs((int) $value),
            'publicationFr', 'originalEdition' => $this->validateYear($field, (int) $value),
            'title', 'subtitle' => $this->validateTitle($field, (string) $value),
            default => null,
        };
    }

    private function validateParagraphs(int $value): void
    {
        if ($value > 800) {
            $this->errors['paragraphs'] = 'Le nombre de paragraphes ne peut pas dépasser 800.';
        }
    }

    private function validateYear(string $field, int $value): void
    {
        $currentYear = (int) date('Y');
        if ($value < 1800 || $value > $currentYear + 2) {
            $this->errors[$field] = sprintf(
                'L\'année doit être comprise entre 1800 et %d.',
                $currentYear + 2
            );
        }
    }

    private function validateTitle(string $field, string $value): void
    {
        if (trim($value) === '') {
            $this->errors[$field] = 'Ce champ est obligatoire.';
        }
    }

    #[LiveAction]
    public function selectSource(string $id, array $data): void
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

    public function canSubmit(): bool
    {
        return !empty($this->formData) && empty($this->errors) && !$this->isSubmitting;
    }
}
