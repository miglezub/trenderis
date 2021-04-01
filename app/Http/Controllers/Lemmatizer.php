<?php

namespace App\Http\Controllers;

class Lemmatizer
{
    protected $lang = 0;
    //moteris
    //galva, vyšnia, bitė, duktė, marti, avis
    //-uo galūnė sesuo žodis vienintelis moteriškas, kiti vyriški (vanduo)
    //geopolitine (ima vyriska as), tas pats bus su ą ir į galūnėm (IA linskniuotė)
    protected $noun_cases_sing_fem_lt = array(
        'v' => array('a', 'ia', 'ė', 'ktė', 'ti', 'is', 'eris'),
        'k' => array('os', 'ios', 'ės', 'kters', 'čios', 'ies', 'ers'),
        'n' => array('ai', 'iai', 'ei', 'kteriai','čiai', 'iai', 'eriai'),
        'g' => array('ą', 'ią', 'ę', 'kterį', 'čią', 'į', 'erį'),
        'in' => array('a', 'ia', 'e', 'kterimi', 'čia', 'imi', 'erimi'),
        'vt' => array('oje', 'ioje', 'ėje', 'kteryje', 'čioje', 'yje', 'eryje'),
        's' => array('a', 'ia', 'e', 'kra', 'čia', 'ie', 'erie')
    );
    protected $noun_endings_sing_fem_lt = array (
        7 => array ('kteriai', 'kterimi', 'kteryje'),
        5 => array ('kters', 'eriai', 'erimi', 'eryje', 'kterį', 'čioje'),
        4 => array ('eris', 'ioje', 'erie', 'čios', 'čiai'),
        3 => array ('ios', 'ies', 'ers', 'iai', 'imi', 'oje', 'yje', 'kra', 'ktė', 'erį', 'ėje', 'čia', 'čią'),
        2 => array ('ia', 'ti', 'is', 'os', 'ai', 'ei', 'ie', 'ės', 'ią'),
        1 => array ( 'a', 'e', 'ė', 'ą', 'ę', 'į'),
    );
    //vyras, kelias, vėjas, brolis, gaidys, skaičius, sūnus, mėnuo, vanduo (akmuo)
    //mėnuo vanduo akmuo
    //užsienio (ias), pasaulio (ias)
    protected $noun_cases_sing_mas_lt = array(
        'v' => array('as', 'lias', 'jas', 'is', 'ys', 'ius', 'us', 'nuo', 'uo'),
        'k' => array('o', 'lio', 'jo', 'io', 'žio', 'iaus', 'aus', 'nesio', 'ens'), //io (politinis politinio)
        'n' => array('ui', 'liui', 'jui', 'iui', 'žiui', 'iui','ui', 'nesiui', 'eniui'), //ui, iui
        'g' => array('ą', 'lią', 'ją', 'į', 'į', 'ių', 'ų', 'nesį', 'enį'), //į
        'in' => array('u', 'liu', 'ju', 'iu', 'iu', 'iumi', 'umi', 'nesiu', 'eniu'), //, iu
        'vt' => array('e', 'lyje', 'jyje', 'yje', 'yje', 'iuje', 'uje', 'nesyje', 'enyje'), //yje
        's' => array('e', 'ly', 'jau', 'i', 'y', 'iau', 'au', 'nesi', 'enie') //y
    );
    protected $noun_endings_sing_mas_lt = array (
        6 => array ('nesiui', 'nesyje'),
        5 => array ('nesio', 'žiui', 'eniui', 'nesiu', 'enyje'),
        4 => array ('iaus', 'iumi', 'eniu', 'jyje', 'iuje', 'nesi', 'enie', 'nesį', 'lias', 'liui', 'lyje'),
        3 => array ('jas', 'ius', 'nuo', 'aus', 'ens', 'iui', 'jui', 'umi', 'yje', 'uje', 'jau', 'iau', 'enį', 'žio', 'lio', 'lią', 'liu'),
        2 => array ('as', 'is', 'ys', 'us', 'uo', 'io', 'jo', 'ui', 'ju', 'iu', 'au', 'ją', 'ių', 'ly'),
        1 => array ('o', 'u', 'y', 'i', 'ą', 'į', 'ų'), // e ištrintas nes dubliuojasi su moteriška
    );
    protected $noun_before_ending_sing_mas_lt = array(
        'v' => array(1 => 'l')
    );
    //sesuo išimtis
    protected $noun_exceptions = array(
        'sesuo' => array('sesers', 'seseriai', 'seserį', 'seserimi', 'seseria', 'seseryje', 'seserie'),
    );

    public function __construct($lang)
    {
        $this->lang = $lang;
    }
    
    public function lemmatize($word)
    {
        if($this->lang == 1) {
            return $this->lemmatizeLt($word);
        } else if($this->lang == 2) {
            return $this->lemmatizeEn($word);
        } else {
            return false;
        }
    }

    public function lemmatizeLt($word) {
        $mas = $this->getLemmatized($word, $this->noun_endings_sing_mas_lt, $this->noun_cases_sing_mas_lt);
        $fem = $this->getLemmatized($word, $this->noun_endings_sing_fem_lt, $this->noun_cases_sing_fem_lt);
        if($mas && $fem) {
            if($mas['length'] >= $fem['length']) {
                return $mas['lemma'];
                return array('gen' => 'mas', 'lemma' => $mas['lemma']);
            } else {
                return $fem['lemma'];
                return array('gen' => 'fem', 'lemma' => $fem['lemma']);
            }
        } else if($mas) {
            return $mas['lemma'];
            return array('gen' => 'mas', 'lemma' => $mas['lemma']);
        } else if($fem) {
            return $fem['lemma'];
            return array('gen' => 'fem', 'lemma' => $fem['lemma']);
        } else {
            return false;
        }
    }

    public function getLemmatized($word, $noun_endings, $noun_cases) {
        $length = strlen($word);
        if($length >= 6) {
            $start = 6;
        } else {
            $start = $length - 1;
        }
        while($start > 0) {
            $last = substr($word, -$start);
            if(key_exists($start, $noun_endings) && in_array($last, $noun_endings[$start])) {
                foreach($noun_cases as $case) {
                    if(($i = array_search($last, $case)) !== false) {
                        return array('length' => $start, 'lemma' => substr($word, 0, $length - $start) . $noun_cases['v'][$i]);
                    }
                }
                return false;
            }
            $start--;
        }
        return false;
    }

    public function formEndings()
    {
        $endings = array();
        foreach($this->noun_cases_sing_fem_lt as $case) {
            foreach($case as $ending) {
                $endings[] = $ending;
            }
        }
        $unique_endings = array_unique($endings);
        $results = array();
        foreach($unique_endings as $ending) {
            $results[strlen($ending)][] = $ending;
        }
        var_export($results);
    }

    public function lemmatizeEn() {
        
    }
}