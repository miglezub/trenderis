<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Tokenizer
{
    protected $tokenized = "";
    protected $lang = 0;

    public function __construct($text, $lang)
    {
        $this->tokenized = $text;
        $this->lang = $lang;
    }
    
    public function lemmatize()
    {
        if($this->lang == 1) {
            $this->lemmatizeLt();
        } else if($this->lang == 2) {
            $this->lemmatizeEn();
        } else {
            return false;
        }
    }

    public function lemmatizeLt() {
        
    }

    public function lemmatizeEn() {
        
    }
}