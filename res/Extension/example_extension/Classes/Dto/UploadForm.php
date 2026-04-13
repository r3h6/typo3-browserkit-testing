<?php

namespace R3H6\ExampleExtension\Dto;

final class UploadForm
{
    protected string $hidden = '';

    /**
     * @var array<\TYPO3\CMS\Core\Http\UploadedFile>
     */
    protected array $files = [];

    public function getHidden(): string
    {
        return $this->hidden;
    }

    public function setHidden(string $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(array $files): void
    {
        $this->files = $files;
    }
}
