<?php

namespace Noclue\Translation;

use Noclue\GitHelper\Helper;

class Checker
{
    private const POLISH_TRANSLATION_FILE = 'general.pl';

    protected $helper;
    protected $commitsToCheckBack;

    public function __construct(Helper $helper, ?int $commitsToCheckBack = 1)
    {
        $this->helper = $helper;
        if (null === $commitsToCheckBack) {
            $this->commitsToCheckBack = 1;
        } else {
            $this->commitsToCheckBack = $commitsToCheckBack;
        }
    }

    public function check() : array
    {
        $files = $this->helper->getFileNamesChangedSince($this->commitsToCheckBack);
        $result = [];
        foreach ($files as $file) {
            $isPolishTranslation = $this->isPolishTranslation($file);
            if (false === $isPolishTranslation) {
                continue;
            }
            $this->validateTranslation($file, $files, $result);
        }

        return $result;
    }

    protected function validateTranslation(string $fileName, array $files, array &$errorsCollection) : void
    {
        //first we need to know if there is any translations added.
        // If not we can skip it. (FE- something removed or changed)
        $addedTranslations = $this->helper->getLineAddedToFileSince($fileName, $this->commitsToCheckBack);
        if (count($addedTranslations) < 1) {
            return;
        }

        //other translation exists? atm - we are searching only for english
        $englishTranslationFileName = str_replace('.pl', '.en', $fileName);
        if (false === in_array($englishTranslationFileName, $files)) {
            $errorsCollection[] = MissingTranslation::buildForWholeFile($fileName, 'en');

            return;
        }

        $addedEnglishTranslation = $this->helper->getLineAddedToFileSince(
            $englishTranslationFileName,
            $this->commitsToCheckBack
        );
        $englishPlaceholders = $this->getPlaceHoldersFromAddedTranslations($addedEnglishTranslation);
        $currentPlaceholders = $this->getCurrentPlaceholders($englishTranslationFileName);
        foreach ($addedTranslations as $addedTranslation) {
            list($placeHolder, $translation) = explode("=", $addedTranslation);
            $placeHolder = trim($placeHolder);
            if (false === in_array($placeHolder, $englishPlaceholders) &&
                false === in_array($placeHolder, $currentPlaceholders)) {
                $errorsCollection[] = new MissingTranslation($fileName, $placeHolder, 'en');
            }
        }
    }

    protected function getCurrentPlaceholders(string $fileName) : array
    {
        $rows = file($fileName);
        $retValue = [];
        foreach ($rows as $row) {
            $row = trim($row);
            if (empty($row) || $row === "") {
                continue;
            }
            $translationPattern = '/.+=.+;$/';
            if (0 == preg_match($translationPattern, $row)) { //false or 0
                continue;
            }

            list($placeHolder, $translation) = explode("=", $row);
            $retValue[] = trim($placeHolder);
        }

        return $retValue;
    }

    protected function getPlaceHoldersFromAddedTranslations(array $addedTranslations) : array
    {
        $retValue = [];

        foreach ($addedTranslations as $addedTranslation) {
            list($placeHolder, $translation) = explode("=", $addedTranslation);
            $retValue[] = trim($placeHolder);
        }

        return $retValue;
    }


    protected function isPolishTranslation(string $fileName) : bool
    {
        $pattern = sprintf("/.+%s$/", Checker::POLISH_TRANSLATION_FILE);
        $result = preg_match($pattern, $fileName);

        if ($result === 1) {
            return true;
        }

        return false;
    }

}
