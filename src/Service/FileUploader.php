<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    public function __construct(
        private string $targetDirectory,
        private SluggerInterface $slugger,
    ) {}

    /**
     * Télécharge un fichier et retourne son nom sécurisé
     */
    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($this->getTargetDirectory(), $fileName);
        return $fileName;
    }

    /**
     * Supprime un fichier du répertoire de destination
     */
    public function remove(string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $filePath = $this->getTargetDirectory() . '/' . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Retourne le répertoire de destination
     */
    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
