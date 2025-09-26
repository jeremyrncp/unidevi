<?php

namespace App\Service;

use App\Entity\Service;
use Symfony\Component\HttpFoundation\File\File;

class FileService
{
    public function save(File $file, string $directory, string $name)
    {
        move_uploaded_file($file->getRealPath(), $directory . "/" . $name . "." . $file->getClientOriginalExtension());

        return $name . "." . $file->getClientOriginalExtension();
    }

    public function naming(): string
    {
        return uniqid();
    }
}
