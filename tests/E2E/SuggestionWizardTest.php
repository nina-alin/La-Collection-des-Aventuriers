<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * E2E browser tests for the Suggestion Wizard (all steps).
 *
 * Setup:
 *   1. composer require --dev symfony/panther --ignore-platform-req=ext-zip
 *   2. Install ChromeDriver matching your Chrome version.
 *   3. Create .env.test.local with DATABASE_URL pointing to 127.0.0.1:5432
 *      and PANTHER_EXTERNAL_BASE_URI=http://localhost:8000
 *
 * Run (from project root):
 *   PANTHER_CHROME_DRIVER_BINARY=./chromedriver \
 *   php bin/phpunit tests/E2E/SuggestionWizardTest.php --no-coverage
 */
class SuggestionWizardTest extends PantherTestCase
{
    private const EMAIL    = 'e2e_wizard@example.test';
    private const PASSWORD = 'E2eT3stP@ss!';
    private const PSEUDO   = 'e2ewizard';

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $hasher   = self::getContainer()->get(UserPasswordHasherInterface::class);

        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
        }

        $user = new User();
        $user->setEmail(self::EMAIL);
        $user->setPseudo(self::PSEUDO);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));
        $user->setIsEmailVerified(true);
        $this->em->persist($user);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
        if ($user) {
            $this->em->remove($user);
            $this->em->flush();
        }
        parent::tearDown();
    }

    private function createLoggedInClient(): \Symfony\Component\Panther\Client
    {
        $client = self::createPantherClient(['window_size' => [1280, 900]]);
        $client->request('GET', '/connexion');

        $client->executeScript(sprintf(
            "document.querySelector('[name=_username]').value = '%s';
             document.querySelector('[name=_password]').value = '%s';
             document.querySelector('[name=_username]').form.submit();",
            self::EMAIL,
            self::PASSWORD,
        ));
        $client->waitFor('body', 3);

        return $client;
    }

    // ─── Initial render ───────────────────────────────────────────────────────

    public function testStep1RendersModeSelectorAndEntityTypePicker(): void
    {
        $client  = $this->createLoggedInClient();
        $crawler = $client->request('GET', '/suggestions');

        $this->assertCount(2, $crawler->filter('.type-card'));
        $this->assertCount(6, $crawler->filter('.entity-type-card'));
    }

    public function testNextStepButtonInitiallyDisabled(): void
    {
        $client = $this->createLoggedInClient();
        $client->request('GET', '/suggestions');

        $this->assertSelectorExists('[data-live-action-param="nextStep"][disabled]');
    }

    // ─── Mode selection ───────────────────────────────────────────────────────

    /**
     * Clicking a mode card must trigger live action.
     * After AJAX re-render the card gets is-selected class.
     * Timeout here = live action on .type-card not reaching LiveComponent.
     */
    public function testClickingModeSelectorFiresLiveAction(): void
    {
        $client  = $this->createLoggedInClient();
        $crawler = $client->request('GET', '/suggestions');

        $crawler->filter('.type-card')->first()->click();

        $client->waitFor('.type-card.is-selected', 5);

        $this->assertSelectorExists('.type-card.is-selected');
    }

    // ─── Entity type selection ────────────────────────────────────────────────

    /**
     * Clicking an entity type card must trigger live action.
     * Timeout here = live action on .entity-type-card not reaching LiveComponent.
     */
    public function testClickingEntityTypeCardFiresLiveAction(): void
    {
        $client  = $this->createLoggedInClient();
        $crawler = $client->request('GET', '/suggestions');

        $crawler->filter('.type-card')->first()->click();
        $client->waitFor('.type-card.is-selected', 5);

        $crawler->filter('.entity-type-card')->first()->click();
        $client->waitFor('.entity-type-card.is-selected', 5);

        $this->assertSelectorExists('.entity-type-card.is-selected');
    }

    // ─── Core regression ──────────────────────────────────────────────────────

    /**
     * THE critical test: after selecting mode + entity type the Suivant button
     * must become enabled.
     * Timeout = live actions not propagating to LiveComponent.
     */
    public function testSelectingModeAndEntityTypeEnablesNextStepButton(): void
    {
        $client  = $this->createLoggedInClient();
        $crawler = $client->request('GET', '/suggestions');

        $this->assertSelectorExists('[data-live-action-param="nextStep"][disabled]');

        $crawler->filter('.type-card')->first()->click();
        $client->waitFor('.type-card.is-selected', 5);

        $crawler->filter('.entity-type-card')->first()->click();
        $client->waitFor('.entity-type-card.is-selected', 5);

        $client->waitFor('[data-live-action-param="nextStep"]:not([disabled])', 5);

        $this->assertSelectorNotExists('[data-live-action-param="nextStep"][disabled]');
    }

    // ─── Step navigation ──────────────────────────────────────────────────────

    public function testClickingNextStepAdvancesToStep2(): void
    {
        $client  = $this->createLoggedInClient();
        $crawler = $client->request('GET', '/suggestions');

        $crawler->filter('.type-card')->first()->click();
        $client->waitFor('.type-card.is-selected', 5);

        $crawler->filter('.entity-type-card')->first()->click();
        $client->waitFor('[data-live-action-param="nextStep"]:not([disabled])', 5);

        $client->find('[data-live-action-param="nextStep"]')->click();
        $client->waitFor('[aria-current="step"]', 5);

        $this->assertSelectorContains('[aria-current="step"]', 'Détails');
    }

    // ─── Card switching ───────────────────────────────────────────────────────

    public function testSwitchingEntityTypeUpdatesIsSelectedClass(): void
    {
        $client  = $this->createLoggedInClient();
        $crawler = $client->request('GET', '/suggestions');

        $crawler->filter('.type-card')->first()->click();
        $client->waitFor('.type-card.is-selected', 5);

        $crawler->filter('.entity-type-card')->first()->click();
        $client->waitFor('.entity-type-card.is-selected', 5);

        $crawler->filter('.entity-type-card')->eq(1)->click();
        $client->waitFor('.entity-type-card.is-selected', 5);

        $this->assertCount(1, $crawler->filter('.entity-type-card.is-selected'));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Navigate from /suggestions to step 2 with NEW_ENTRY + BOOK selected.
     * Leaves the browser on the step 2 form (waits for #field-title to exist).
     */
    private function navigateToStep2BookNewEntry(\Symfony\Component\Panther\Client $client): void
    {
        $crawler = $client->request('GET', '/suggestions');

        $crawler->filter('.type-card')->first()->click();
        $client->waitFor('.type-card.is-selected', 5);

        $crawler->filter('.entity-type-card')->first()->click();
        $client->waitFor('[data-live-action-param="nextStep"]:not([disabled])', 5);

        $client->find('[data-live-action-param="nextStep"]')->click();
        $client->waitFor('#field-title', 5);
    }

    /**
     * Navigate to step 3 (cover upload) with the given title already filled in.
     */
    private function navigateToStep3(\Symfony\Component\Panther\Client $client, string $title = 'Mon Livre Test'): void
    {
        $this->navigateToStep2BookNewEntry($client);

        $client->executeScript(sprintf(
            "const i = document.querySelector('#field-title');
             i.value = %s;
             i.dispatchEvent(new Event('input', {bubbles: true}));",
            json_encode($title),
        ));

        $client->find('[data-live-action-param="nextStep"]')->click();
        $client->waitFor('.upload-zone', 5);
    }

    /**
     * Navigate to step 4 (preview) with the given title already filled in.
     */
    private function navigateToStep4(\Symfony\Component\Panther\Client $client, string $title = 'Mon Livre Test'): void
    {
        $this->navigateToStep3($client, $title);

        $client->find('[data-live-action-param="nextStep"]')->click();
        $client->waitFor('#step-4-heading', 5);
    }

    // ─── Step 2 ───────────────────────────────────────────────────────────────

    public function testStep2ShowsTitleAndIsbnFieldsForBook(): void
    {
        $client = $this->createLoggedInClient();
        $this->navigateToStep2BookNewEntry($client);

        $this->assertSelectorExists('#field-title');
        $this->assertSelectorExists('#field-isbn');
        $this->assertSelectorNotExists('#field-name');
    }

    public function testStep2PreviousButtonReturnsToStep1(): void
    {
        $client = $this->createLoggedInClient();
        $this->navigateToStep2BookNewEntry($client);

        $client->find('[data-live-step-param="1"]')->click();
        $client->waitFor('[aria-current="step"]', 5);

        $this->assertSelectorContains('[aria-current="step"]', 'Type');
    }

    public function testStep2WithoutTitleStaysOnStep2(): void
    {
        $client = $this->createLoggedInClient();
        $this->navigateToStep2BookNewEntry($client);

        $client->find('[data-live-action-param="nextStep"]')->click();
        $client->waitFor('[aria-current="step"]', 5);

        $this->assertSelectorContains('[aria-current="step"]', 'Détails');
    }

    public function testFillingTitleAndClickingNextAdvancesToStep3(): void
    {
        $client = $this->createLoggedInClient();
        $this->navigateToStep2BookNewEntry($client);

        $client->executeScript(
            "const i = document.querySelector('#field-title');
             i.value = 'L Oeil d Emeraude';
             i.dispatchEvent(new Event('input', {bubbles: true}));"
        );

        $client->find('[data-live-action-param="nextStep"]')->click();
        $client->waitFor('.upload-zone', 5);

        $this->assertSelectorContains('[aria-current="step"]', 'Couverture');
    }

    public function testCorrectionModeShowsSourceAutocompleteInStep2(): void
    {
        $client  = $this->createLoggedInClient();
        $crawler = $client->request('GET', '/suggestions');

        $crawler->filter('.type-card')->eq(1)->click();
        $client->waitFor('.type-card.is-selected', 5);

        $crawler->filter('.entity-type-card')->first()->click();
        $client->waitFor('[data-live-action-param="nextStep"]:not([disabled])', 5);

        $client->find('[data-live-action-param="nextStep"]')->click();
        $client->waitFor('#source-entity', 5);

        $this->assertSelectorExists('#source-entity');
    }

    // ─── Step 3 ───────────────────────────────────────────────────────────────

    public function testStep3ShowsCoverUploadZone(): void
    {
        $client = $this->createLoggedInClient();
        $this->navigateToStep3($client);

        $this->assertSelectorExists('.upload-zone');
        $this->assertSelectorContains('[aria-current="step"]', 'Couverture');
    }

    public function testStep3PreviousButtonReturnsToStep2(): void
    {
        $client = $this->createLoggedInClient();
        $this->navigateToStep3($client);

        $client->find('[data-live-step-param="2"]')->click();
        $client->waitFor('#field-title', 5);

        $this->assertSelectorContains('[aria-current="step"]', 'Détails');
    }

    public function testStep3NextWithoutCoverAdvancesToStep4(): void
    {
        $client = $this->createLoggedInClient();
        $this->navigateToStep3($client);

        $client->find('[data-live-action-param="nextStep"]')->click();
        $client->waitFor('#step-4-heading', 5);

        $this->assertSelectorContains('[aria-current="step"]', 'Aperçu');
    }

    // ─── Step 4 ───────────────────────────────────────────────────────────────

    public function testStep4ShowsPreviewOfEnteredTitle(): void
    {
        $client = $this->createLoggedInClient();
        $this->navigateToStep4($client, 'L Oeil d Emeraude');

        $this->assertSelectorContains('.preview-fields', 'L Oeil d Emeraude');
    }

    public function testStep4PreviousButtonReturnsToStep3(): void
    {
        $client = $this->createLoggedInClient();
        $this->navigateToStep4($client);

        $client->find('[data-live-step-param="3"]')->click();
        $client->waitFor('.upload-zone', 5);

        $this->assertSelectorContains('[aria-current="step"]', 'Couverture');
    }

    public function testStep4SubmitButtonIsEnabled(): void
    {
        $client = $this->createLoggedInClient();
        $this->navigateToStep4($client, 'Mon Livre Test');

        $this->assertSelectorNotExists('[data-live-action-param="submitSuggestion"][disabled]');
    }

    // ─── Full journey ─────────────────────────────────────────────────────────

    /**
     * THE critical end-to-end regression: a complete NEW_ENTRY BOOK submission
     * through all four steps resets the wizard to step 1.
     */
    public function testFullNewEntryBookFlowSubmitsAndResetsToStep1(): void
    {
        $client = $this->createLoggedInClient();
        $this->navigateToStep4($client, 'Livre E2E Complet');

        $client->find('[data-live-action-param="submitSuggestion"]')->click();
        $client->waitFor('[aria-current="step"]', 5);

        $this->assertSelectorContains('[aria-current="step"]', 'Type');
        $this->assertSelectorNotExists('[aria-current="step"][class*="done"]');
    }
}
