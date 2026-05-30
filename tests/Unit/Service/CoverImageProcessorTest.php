<?php

namespace App\Tests\Unit\Service;

use App\Service\CoverImageProcessor;
use function imagecreatetruecolor;
use function imagejpeg;
use function imagedestroy;
use function getimagesize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CoverImageProcessorTest extends TestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }
        $this->tmpDir = sys_get_temp_dir() . '/cover_test_' . uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        if ($this->tmpDir === '') return;
        array_map('unlink', glob($this->tmpDir . '/*.jpg') ?: []);
        @rmdir($this->tmpDir);
    }

    private function makeProcessor(): CoverImageProcessor
    {
        return new CoverImageProcessor($this->tmpDir);
    }

    private function createFakeUploadedFile(string $path, string $mimeType, string $originalName): UploadedFile
    {
        return new UploadedFile($path, $originalName, $mimeType, null, true);
    }

    public function testValidJpegReturnsRelativePath(): void
    {
        $imgPath = $this->tmpDir . '/test.jpg';
        $img     = imagecreatetruecolor(300, 400);
        imagejpeg($img, $imgPath);
        imagedestroy($img);

        $file   = $this->createFakeUploadedFile($imgPath, 'image/jpeg', 'test.jpg');
        $result = $this->makeProcessor()->process($file);

        $this->assertStringStartsWith('uploads/covers/', $result);
        $this->assertStringEndsWith('.jpg', $result);
    }

    public function testFileLargerThan4MbThrows(): void
    {
        $imgPath = $this->tmpDir . '/big.jpg';
        // Create an UploadedFile mock reporting a size > 4MB
        $file = $this->createMock(UploadedFile::class);
        $file->method('getSize')->willReturn(5 * 1024 * 1024);

        $this->expectException(\InvalidArgumentException::class);
        $this->makeProcessor()->process($file);
    }

    public function testUnsupportedMimeThrows(): void
    {
        $pdfPath = $this->tmpDir . '/doc.pdf';
        file_put_contents($pdfPath, '%PDF-1.4 fake content');

        $file = $this->createFakeUploadedFile($pdfPath, 'application/pdf', 'doc.pdf');

        $this->expectException(\InvalidArgumentException::class);
        $this->makeProcessor()->process($file);
    }

    public function testOutputAspectRatioIsThreeToFour(): void
    {
        $imgPath = $this->tmpDir . '/wide.jpg';
        $img     = imagecreatetruecolor(800, 400);
        imagejpeg($img, $imgPath);
        imagedestroy($img);

        $file   = $this->createFakeUploadedFile($imgPath, 'image/jpeg', 'wide.jpg');
        $result = $this->makeProcessor()->process($file);

        $outputPath = $this->tmpDir . '/' . basename($result);
        [$w, $h]    = getimagesize($outputPath);
        $ratio       = $w / $h;

        $this->assertEqualsWithDelta(0.75, $ratio, 0.01);
    }
}
