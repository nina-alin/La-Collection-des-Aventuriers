<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Enum\SuggestionEntityType;
use App\Entity\Enum\SuggestionMode;
use App\Entity\Suggestion;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class WizardComponentTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    private EntityManagerInterface $em;
    private User $user;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->user = new User();
        $this->user->setEmail('__wizard_test_' . uniqid() . '@example.com');
        $this->user->setPseudo('wizardtester');
        $this->user->setRoles(['ROLE_USER']);
        $this->user->setPassword($hasher->hashPassword($this->user, 'password'));
        $this->em->persist($this->user);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Suggestion s WHERE s.user = :u')
            ->setParameter('u', $this->user)
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\User u WHERE u.email LIKE :p')
            ->setParameter('p', '__wizard_test_%@example.com')
            ->execute();
        parent::tearDown();
    }

    // ─── Step 1 rendering ─────────────────────────────────────────────────────

    public function testInitialRenderShowsStep1(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $html = $component->render()->toString();

        $this->assertStringContainsString('Étape 1', $html);
        $this->assertStringContainsString('Nouvelle fiche', $html);
        $this->assertStringContainsString('Corriger une fiche', $html);
    }

    public function testStep1ShowsEntityTypeSelector(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $html = $component->render()->toString();

        $this->assertStringContainsString('entity-type-picker', $html);
        $this->assertStringContainsString('Livre', $html);
        $this->assertStringContainsString('Auteur', $html);
        $this->assertStringContainsString('Illustrateur', $html);
        $this->assertStringContainsString('Traducteur', $html);
        $this->assertStringContainsString('Collection', $html);
    }

    public function testStep1SelectedEntityTypeShowsIsSelectedClass(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->set('entityType', 'BOOK');
        $html = $component->render()->toString();

        $this->assertStringContainsString('is-selected', $html);
        $this->assertStringContainsString('entity-type-check', $html);
    }

    // ─── Step 1 → Step 2 navigation ───────────────────────────────────────────

    public function testNextStepWithoutSelectionShowsValidationError(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->call('nextStep');
        $html = $component->render()->toString();

        $this->assertStringContainsString('Veuillez sélectionner un mode', $html);
        $this->assertStringContainsString('Étape 1', $html);
    }

    public function testNextStepWithModeOnlyShowsValidationError(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->call('nextStep');

        $html = $component->render()->toString();

        $this->assertStringContainsString('Veuillez sélectionner un mode', $html);
        $this->assertStringContainsString('Étape 1', $html);
    }

    public function testNextStepWithValidSelectionsAdvancesToStep2(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'BOOK')
            ->call('nextStep');

        $html = $component->render()->toString();

        $this->assertStringContainsString('Étape 2', $html);
        $this->assertStringNotContainsString('Veuillez sélectionner', $html);
    }

    // ─── goToStep direct navigation ───────────────────────────────────────────

    public function testGoToStepNavigatesToTargetStep(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->call('goToStep', ['step' => 3]);
        $html = $component->render()->toString();

        $this->assertStringContainsString('Étape 3', $html);
    }

    public function testGoToStepIgnoresInvalidStep(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->call('goToStep', ['step' => 99]);
        $html = $component->render()->toString();

        $this->assertStringContainsString('Étape 1', $html);
    }

    // ─── Step 2: BOOK form fields ──────────────────────────────────────────────

    public function testStep2ForBookShowsTitleField(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'BOOK')
            ->set('step', 2);

        $html = $component->render()->toString();

        $this->assertStringContainsString('field-title', $html);
        $this->assertStringContainsString('field-isbn', $html);
    }

    public function testStep2ForAuthorShowsNameField(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'AUTHOR')
            ->set('step', 2);

        $html = $component->render()->toString();

        $this->assertStringContainsString('field-name', $html);
        $this->assertStringNotContainsString('field-isbn', $html);
    }

    public function testStep2ForEditorShowsNameField(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'EDITOR')
            ->set('step', 2);

        $html = $component->render()->toString();

        $this->assertStringContainsString('field-name', $html);
    }

    public function testStep2ForCollectionShowsNameField(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'COLLECTION')
            ->set('step', 2);

        $html = $component->render()->toString();

        $this->assertStringContainsString('field-name', $html);
    }

    // ─── Field validation ──────────────────────────────────────────────────────

    public function testValidateFieldParagraphsExceedingMaxSetsError(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('entityType', 'BOOK')
            ->call('validateField', ['field' => 'paragraphs', 'value' => 801]);
        $html = $component->render()->toString();

        $this->assertStringContainsString('800', $html);
    }

    public function testValidateFieldParagraphsWithinMaxClearsError(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('entityType', 'BOOK')
            ->set('errors', ['paragraphs' => 'error'])
            ->call('validateField', ['field' => 'paragraphs', 'value' => 400]);

        $html = $component->render()->toString();

        $this->assertStringNotContainsString('ne peut pas dépasser 800', $html);
    }

    public function testValidateFieldYearOutOfRangeSetsError(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('entityType', 'BOOK')
            ->call('validateField', ['field' => 'publicationFr', 'value' => 1700]);
        $html = $component->render()->toString();

        $this->assertStringContainsString('1800', $html);
    }

    public function testValidateFieldFutureYearTooFarSetsError(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('entityType', 'BOOK')
            ->call('validateField', ['field' => 'publicationFr', 'value' => (int) date('Y') + 10]);
        $html = $component->render()->toString();

        $this->assertStringContainsString('1800', $html);
    }

    public function testValidateFieldEmptyTitleSetsError(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('entityType', 'BOOK')
            ->call('validateField', ['field' => 'title', 'value' => '']);
        $html = $component->render()->toString();

        $this->assertStringContainsString('obligatoire', $html);
    }

    // ─── selectSource ──────────────────────────────────────────────────────────

    public function testSelectSourcePreFillsFormDataAndOriginalData(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $data = ['title' => 'Livre existant', 'author' => 'Auteur Test'];
        $component->call('selectSource', ['id' => '123', 'data' => $data]);

        $componentInstance = $component->component();

        $this->assertSame('123', $componentInstance->sourceEntityId);
        $this->assertSame($data, $componentInstance->originalData);
        $this->assertSame($data, $componentInstance->formData);
    }

    // ─── computeDiff ──────────────────────────────────────────────────────────

    public function testComputeDiffDetectsChangedFields(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('originalData', ['title' => 'Ancien titre', 'author' => 'Auteur'])
            ->set('formData', ['title' => 'Nouveau titre', 'author' => 'Auteur']);

        $instance = $component->component();
        $diff = $instance->computeDiff();

        $this->assertArrayHasKey('title', $diff);
        $this->assertArrayNotHasKey('author', $diff);
    }

    public function testComputeDiffReturnsEmptyWhenNoOriginalData(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $instance = $component->component();
        $diff = $instance->computeDiff();

        $this->assertSame([], $diff);
    }

    // ─── Quota enforcement ────────────────────────────────────────────────────

    public function testStep4ShowsQuotaWarningWhenPendingCountIs20(): void
    {
        // Create 20 pending suggestions for the user
        for ($i = 0; $i < 20; $i++) {
            $s = new Suggestion();
            $s->setUser($this->user);
            $s->setEntityType(SuggestionEntityType::BOOK);
            $s->setMode(SuggestionMode::NEW_ENTRY);
            $s->setFormData(['title' => 'Livre ' . $i]);
            $this->em->persist($s);
        }
        $this->em->flush();

        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->set('step', 4);
        $html = $component->render()->toString();

        $this->assertStringContainsString('Quota atteint', $html);
    }

    public function testSubmitWithEmptyFormDataStaysOnStep2(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'BOOK')
            ->set('step', 4)
            ->call('submitSuggestion');

        $html = $component->render()->toString();

        $this->assertStringContainsString('Étape 2', $html);
        $this->assertStringContainsString('incomplètes', $html);
    }

    // ─── canProceedToStep2 / canSubmit ────────────────────────────────────────

    public function testCanProceedToStep2ReturnsFalseWhenModeOrTypeNull(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $instance = $component->component();

        $this->assertFalse($instance->canProceedToStep2());
    }

    public function testCanProceedToStep2ReturnsTrueWhenBothSet(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'BOOK');

        $instance = $component->component();

        $this->assertTrue($instance->canProceedToStep2());
    }

    public function testCanSubmitReturnsFalseWhenFormDataEmpty(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $instance = $component->component();

        $this->assertFalse($instance->canSubmit());
    }

    public function testCanSubmitReturnsTrueWithValidData(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('entityType', 'BOOK')
            ->set('formData', ['title' => 'Mon Livre']);

        $instance = $component->component();

        $this->assertTrue($instance->canSubmit());
    }

    public function testCanSubmitReturnsTrueForNonBookWithName(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('entityType', 'AUTHOR')
            ->set('formData', ['name' => 'J.R.R. Tolkien']);

        $instance = $component->component();

        $this->assertTrue($instance->canSubmit());
    }

    public function testStep4PreviewShowsTitleForBookType(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'BOOK')
            ->set('step', 4)
            ->set('formData', ['title' => 'L\'Œil d\'Émeraude', 'author' => 'Joe Dever']);

        $html = $component->render()->toString();

        $this->assertStringContainsString('L&#039;Œil d&#039;Émeraude', $html);
        $this->assertStringContainsString('Joe Dever', $html);
    }

    public function testStep4PreviewShowsNameForNonBookType(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'AUTHOR')
            ->set('step', 4)
            ->set('formData', ['name' => 'J.R.R. Tolkien']);

        $html = $component->render()->toString();

        $this->assertStringContainsString('J.R.R. Tolkien', $html);
    }

    public function testStep4SubmitEnabledWhenRequiredFieldFilled(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'BOOK')
            ->set('step', 4)
            ->set('formData', ['title' => 'Mon Livre']);

        $html = $component->render()->toString();

        $this->assertStringNotContainsString('aria-disabled="true"', $html);
    }

    public function testNextStepInitializesFormDataWithBookKeys(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'BOOK')
            ->call('nextStep');

        $instance = $component->component();

        $this->assertArrayHasKey('title', $instance->formData);
        $this->assertArrayHasKey('isbn', $instance->formData);
    }

    public function testNextStepInitializesFormDataWithNameKeyForNonBook(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'AUTHOR')
            ->call('nextStep');

        $instance = $component->component();

        $this->assertArrayHasKey('name', $instance->formData);
        $this->assertArrayNotHasKey('title', $instance->formData);
    }

    // ─── selectMode / selectEntityType live actions ───────────────────────────

    public function testSelectModeSetsModeProp(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->call('selectMode', ['val' => 'NEW_ENTRY']);

        $this->assertSame('NEW_ENTRY', $component->component()->mode);
    }

    public function testSelectModeCorrectionSetsModeProp(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->call('selectMode', ['val' => 'CORRECTION']);

        $this->assertSame('CORRECTION', $component->component()->mode);
    }

    public function testSelectEntityTypeSetsEntityTypeProp(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->call('selectEntityType', ['val' => 'BOOK']);

        $this->assertSame('BOOK', $component->component()->entityType);
    }

    public function testSelectEntityTypeAllValuesAccepted(): void
    {
        foreach (['BOOK', 'AUTHOR', 'ILLUSTRATOR', 'TRADUCTOR', 'EDITOR', 'COLLECTION'] as $type) {
            $component = $this->createLiveComponent('Suggestion:WizardComponent')
                ->actingAs($this->user);

            $component->call('selectEntityType', ['val' => $type]);

            $this->assertSame($type, $component->component()->entityType, "entityType should be $type");
        }
    }

    public function testSelectModeRendersIsSelectedClass(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->call('selectMode', ['val' => 'NEW_ENTRY']);
        $html = $component->render()->toString();

        $this->assertStringContainsString('is-selected', $html);
    }

    public function testSelectEntityTypeRendersIsSelectedClass(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->call('selectEntityType', ['val' => 'BOOK']);
        $html = $component->render()->toString();

        $this->assertStringContainsString('is-selected', $html);
        $this->assertStringContainsString('entity-type-check', $html);
    }

    /**
     * Core server-side regression: after both selectMode + selectEntityType
     * the Suivant button must NOT be disabled in the rendered HTML.
     * If this fails, the PHP live actions or prop propagation is broken.
     */
    public function testBothSelectActionsEnableNextStepButton(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->call('selectMode', ['val' => 'NEW_ENTRY']);
        $component->call('selectEntityType', ['val' => 'BOOK']);
        $html = $component->render()->toString();

        // The button uses this exact compound when disabled (from Twig ternary in StepType)
        $this->assertStringNotContainsString('aria-disabled="true" disabled', $html);
    }

    public function testSelectModeDoesNotAdvanceStep(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component->call('selectMode', ['val' => 'NEW_ENTRY']);

        $this->assertSame(1, $component->component()->step);
    }

    // ─── Successful submit ────────────────────────────────────────────────────

    public function testSuccessfulSubmitResetsWizardToStep1(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'BOOK')
            ->set('step', 4)
            ->set('formData', ['title' => 'Mon Livre Test'])
            ->call('submitSuggestion');

        $instance = $component->component();

        $this->assertSame(1, $instance->step);
        $this->assertNull($instance->mode);
        $this->assertNull($instance->entityType);
        $this->assertSame([], $instance->formData);
    }

    public function testSuccessfulSubmitPersistsSuggestionInDatabase(): void
    {
        $component = $this->createLiveComponent('Suggestion:WizardComponent')
            ->actingAs($this->user);

        $component
            ->set('mode', 'NEW_ENTRY')
            ->set('entityType', 'BOOK')
            ->set('step', 4)
            ->set('formData', ['title' => 'Livre à persister'])
            ->call('submitSuggestion');

        $count = $this->em->createQuery(
            'SELECT COUNT(s) FROM App\Entity\Suggestion s WHERE s.user = :u'
        )->setParameter('u', $this->user)->getSingleScalarResult();

        $this->assertSame(1, (int) $count);
    }
}
