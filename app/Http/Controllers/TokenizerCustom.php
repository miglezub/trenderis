<?php

namespace App\Http\Controllers;

class TokenizerCustom
{
    private $text = "";
    private $lang = 0;

    public function __construct($text, $lang)
    {
        $this->text = $text;
        $this->lang = $lang;
    }
    
    public function tokenize()
    {
        $this->text = strip_tags($this->text);
        $this->text = mb_strtolower($this->text);
        $this->text = str_replace(array('.', ',', "\n", "\t", "\r", "!", "?", ":", ";", "(", ")", "[", "]", "\"", "“", "„", " – ", "#", "—", "…", "”", "”", "-"), ' ', $this->text);
        $this->removeStopWords();
        $this->text = preg_replace("/[\s]+/mu", " ", $this->text);
        $tokenized = explode(" ", $this->text);
        return array_filter(
            $tokenized,
            function ($value) {
                return strlen($value) > 1;
            }
        );
    }

    private function removeStopWords() {
        $content = file_get_contents(__DIR__ . '/../../stopwords' . $this->lang . '.txt');
        $stopwords = explode(";", $content);
        foreach($stopwords as $key => $word) {
            $stopwords[$key] = " " . $word . " ";
        }
        $this->text = str_replace($stopwords, ' ', $this->text);
    }
}