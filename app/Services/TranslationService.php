<?php

namespace App\Services;

use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslationService
{
    protected GoogleTranslate $translator;

    public function __construct()
    {
        $this->translator = new GoogleTranslate();
        $this->translator->setSource('en');
        $this->translator->setTarget('ar');
    }

    public function translateResponse($data)
    {
        
        if (is_null($data) || is_numeric($data) || is_bool($data)) {
            return $data;
        }

        
        if (is_string($data)) {

            $text = trim($data);

            if ($text === '') {
                return $text;
            }

            try {
                return $this->translator->translate($text);
            } catch (\Throwable $e) {
                return $text;
            }
        }

        
        if (is_array($data)) {

            foreach ($data as $key => $value) {
                $data[$key] = $this->translateResponse($value);
            }

            return $data;
        }

    
        if (is_object($data)) {

            foreach ($data as $key => $value) {
                $data->$key = $this->translateResponse($value);
            }

            return $data;
        }

        return $data;
    }
}