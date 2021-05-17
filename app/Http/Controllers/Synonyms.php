<?php

namespace App\Http\Controllers;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Synonyms
{
    protected $text_ids = array();

    public function __construct($text_ids)
    {
        $this->text_ids = $text_ids;
    }

    public function trainModel()
    {
        // echo shell_exec("python ../find_similar.py korona 2>&1");
        $ids = implode(",", $this->text_ids);
        echo shell_exec("python ./train_word2vec.py " . $ids . " 2>&1");
    }

    public function getSynonyms() {
        $ids = implode(",", $this->text_ids);
        echo shell_exec("python ./synonym_list.py " . $ids . " 2>&1");
    }
}
