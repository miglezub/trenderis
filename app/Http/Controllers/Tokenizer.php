<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Tokenizer
{
    protected $text = "";
    protected $lang = 0;

    public function __construct($text, $lang)
    {
        $this->text = $text;
        $this->lang = $lang;
    }
    
    public function tokenize()
    {
        $this->text = strip_tags($this->text);
        $this->text = str_replace(array('.', ',', "\n", "\t", "\r", "!", "?", ":", ";", "-", "(", ")", "[", "]"), ' ', $this->text);
        $this->removeStopWords();
        $this->text = preg_replace("/[\s]+/mu", " ", $this->text);
        return explode(" ", strtolower($this->text));
    }

    public function removeStopWords() {
        $content = file_get_contents(__DIR__ . '/../../stopwords' . $this->lang . '.txt');
        $stopwords = explode(";", $content);
        $this->text = str_replace($stopwords, '', $this->text);
    }
}