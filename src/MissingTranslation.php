<?php

namespace Noclue\Translation;

class MissingTranslation
{
    private $fileName;
    private $translation;
    private $langCode;

    public function __construct(string $fileName, string $translation, string $langCode)
    {
        $this->fileName = $fileName;
        $this->translation = $translation;
        $this->langCode = $langCode;
    }

    public static function buildForWholeFile(string $fileName, string $langCode)
    {
        return new MissingTranslation($fileName, 'whole file', $langCode);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getTranslation(): string
    {
        return $this->translation;
    }

    public function getLangCode(): string
    {
        return $this->langCode;
    }
}
