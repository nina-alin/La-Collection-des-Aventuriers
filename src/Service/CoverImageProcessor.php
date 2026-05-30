<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

class CoverImageProcessor
{
    private const MAX_SIZE_BYTES = 4 * 1024 * 1024;
    private const ALLOWED_MIMES  = ['image/jpeg', 'image/png', 'image/webp'];
    private const TARGET_WIDTH   = 600;
    private const TARGET_HEIGHT  = 800;

    public function __construct(private readonly string $uploadsDir)
    {
    }

    public function process(UploadedFile $file): string
    {
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw new \InvalidArgumentException('Le fichier dépasse la taille maximale de 4 Mo.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file->getPathname());

        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException(sprintf('Format non supporté : %s. Formats acceptés : JPEG, PNG, WEBP.', $mime));
        }

        $source = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($file->getPathname()),
            'image/png'  => imagecreatefrompng($file->getPathname()),
            'image/webp' => imagecreatefromwebp($file->getPathname()),
        };

        if ($source === false) {
            throw new \RuntimeException('Impossible de lire l\'image.');
        }

        $srcWidth  = imagesx($source);
        $srcHeight = imagesy($source);

        [$cropX, $cropY, $cropW, $cropH] = $this->computeCenterCrop($srcWidth, $srcHeight);

        $output = imagecreatetruecolor(self::TARGET_WIDTH, self::TARGET_HEIGHT);
        imagecopyresampled($output, $source, 0, 0, $cropX, $cropY, self::TARGET_WIDTH, self::TARGET_HEIGHT, $cropW, $cropH);

        imagedestroy($source);

        $filename     = Uuid::v7()->toRfc4122() . '.jpg';
        $relativePath = 'uploads/covers/' . $filename;
        $absolutePath = $this->uploadsDir . '/' . $filename;

        if (!imagejpeg($output, $absolutePath, 85)) {
            imagedestroy($output);
            throw new \RuntimeException('Impossible de sauvegarder l\'image.');
        }

        imagedestroy($output);

        return $relativePath;
    }

    private function computeCenterCrop(int $srcWidth, int $srcHeight): array
    {
        $targetRatio = self::TARGET_WIDTH / self::TARGET_HEIGHT;
        $srcRatio    = $srcWidth / $srcHeight;

        if ($srcRatio > $targetRatio) {
            $cropH = $srcHeight;
            $cropW = (int) round($srcHeight * $targetRatio);
            $cropX = (int) round(($srcWidth - $cropW) / 2);
            $cropY = 0;
        } else {
            $cropW = $srcWidth;
            $cropH = (int) round($srcWidth / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($srcHeight - $cropH) / 2);
        }

        return [$cropX, $cropY, $cropW, $cropH];
    }
}
