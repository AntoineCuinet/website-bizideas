<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    public function __construct(
        private string $uploadDirectory,
        private SluggerInterface $slugger
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($this->getUploadDirectory(), $newFilename);

        return $newFilename;
    }

    public function remove(string $filename): void
    {
        $filePath = $this->getUploadDirectory() . '/' . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function getUploadDirectory(): string
    {
        return $this->uploadDirectory;
    }
}
