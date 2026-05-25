<?php

declare(strict_types=1);

namespace App\DataFixtures\Factory;

use App\Entity\Editor;

class EditorFactory
{
    public static function new(array $overrides = []): Editor
    {
        $editor = new Editor();
        $editor->setName($overrides['name'] ?? 'Éditeur Test ' . substr(uniqid(), -6));

        return $editor;
    }
}
