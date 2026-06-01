<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components;

use App\Dto\ProfileMenuDto;
use App\Entity\User;
use App\Service\ProfileMenuService;
use App\Twig\Components\Layout\ProfileMenu;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class ProfileMenuTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    private function makeComponent(array $roles, ?string $rankName = 'Aventurier'): string
    {
        $highestRole = 'ROLE_USER';
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $highestRole = 'ROLE_ADMIN';
        } elseif (in_array('ROLE_MODERATOR', $roles, true)) {
            $highestRole = 'ROLE_MODERATOR';
        }

        $dto = new ProfileMenuDto(
            pseudo: 'testuser',
            displayName: 'Test User',
            avatarUrl: null,
            highestRole: $highestRole,
            rankName: $rankName,
            validatedCount: 5,
            pendingModerationCount: 3,
        );

        $mockUser = $this->createMock(User::class);
        $mockUser->method('getRoles')->willReturn($roles);

        $token = new UsernamePasswordToken($mockUser, 'main', $roles);
        static::getContainer()->get(TokenStorageInterface::class)->setToken($token);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($mockUser);

        $service = $this->createMock(ProfileMenuService::class);
        $service->method('getMenuData')->willReturn($dto);

        static::getContainer()->set(ProfileMenuService::class, $service);
        static::getContainer()->set(Security::class, $security);

        return $this->renderTwigComponent(ProfileMenu::class)->toString();
    }

    public function testTriggerHasAriaAttributes(): void
    {
        $html = $this->makeComponent(['ROLE_USER']);

        $this->assertStringContainsString('aria-haspopup="menu"', $html);
        $this->assertMatchesRegularExpression('/aria-controls="user-menu-\w+"/', $html);
    }

    public function testStandardUserHasNoBadgeInDom(): void
    {
        $html = $this->makeComponent(['ROLE_USER']);

        $this->assertStringNotContainsString('badge-role-mod', $html);
        $this->assertStringNotContainsString('badge-role-admin', $html);
    }

    public function testModeratorUserShowsModBadge(): void
    {
        $html = $this->makeComponent(['ROLE_USER', 'ROLE_MODERATOR']);

        $this->assertStringContainsString('badge-role-mod', $html);
        $this->assertStringContainsString('MODÉRATEUR', $html);
    }

    public function testAdminUserShowsAdminBadge(): void
    {
        $html = $this->makeComponent(['ROLE_USER', 'ROLE_ADMIN']);

        $this->assertStringContainsString('badge-role-admin', $html);
        $this->assertStringContainsString('ADMINISTRATEUR', $html);
    }

    public function testRankFallbackWhenNull(): void
    {
        $html = $this->makeComponent(['ROLE_USER'], null);

        $this->assertStringContainsString('—', $html);
    }

    public function testModerationSectionAbsentForStandardUser(): void
    {
        $html = $this->makeComponent(['ROLE_USER']);

        $this->assertStringNotContainsString('OUTILS DE MODÉRATION', $html);
    }

    public function testModerationSectionPresentForModerator(): void
    {
        $html = $this->makeComponent(['ROLE_USER', 'ROLE_MODERATOR']);

        $this->assertStringContainsString('OUTILS DE MODÉRATION', $html);
    }
}
