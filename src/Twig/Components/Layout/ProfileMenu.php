<?php

declare(strict_types=1);

namespace App\Twig\Components\Layout;

use App\Dto\ProfileMenuDto;
use App\Entity\User;
use App\Service\ProfileMenuService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class ProfileMenu
{
    public ProfileMenuDto $menuData;
    public string $instanceId = 'desktop';

    public function __construct(
        private readonly ProfileMenuService $menuService,
        private readonly Security $security,
    ) {}

    public function mount(): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $this->menuData = $this->menuService->getMenuData($user);
    }
}
