<?php

namespace App\Http\Controllers;

class Lemmatizer
{
    //galininko ą, į vyr ar mot (streiką, galvą, kirvį, avį)
    //dienų
    protected $lang = 0;
    //galva, vyšnia, bitė, duktė, marti, avis, moteris
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
        7 => array ( 0 => 'kteriai', 1 => 'kterimi', 2 => 'kteryje', ), 
        6 => array ( 0 => 'kterį', 1 => 'čioje', ), 
        5 => array ( 0 => 'kters', 1 => 'čios', 2 => 'čiai', 3 => 'eriai', 4 => 'čią', 5 => 'erimi', 6 => 'eryje', ), 
        4 => array ( 0 => 'ktė', 1 => 'eris', 2 => 'erį', 3 => 'čia', 4 => 'ioje', 5 => 'ėje', 6 => 'erie', ), 
        3 => array ( 0 => 'ios', 1 => 'ės', 2 => 'ies', 3 => 'ers', 4 => 'iai', 5 => 'ią', 6 => 'imi', 7 => 'oje', 8 => 'yje', 9 => 'kra', ), 
        2 => array ( 0 => 'ia', 1 => 'ė', 2 => 'ti', 3 => 'is', 4 => 'os', 5 => 'ai', 6 => 'ei', 7 => 'ą', 8 => 'ę', 9 => 'į', 10 => 'ie', ), 
        1 => array ( 0 => 'a', 1 => 'e', ), 
    );
    //vyras, kelias, vėjas, brolis, gaidys, skaičius, sūnus, mėnuo, vanduo (akmuo), ketvirtadienis
    //mėnuo vanduo akmuo
    //užsienio (ias), pasaulio (ias)
    protected $noun_cases_sing_mas_lt = array(
        'v' => array('as', 'lias', 'jas', 'is', 'ys', 'ius', 'us', 'nuo', 'uo', 'ienis'),
        'k' => array('o', 'lio', 'jo', 'io', 'žio', 'iaus', 'aus', 'nesio', 'ens', 'ienio'),
        'n' => array('ui', 'liui', 'jui', 'iui', 'žiui', 'iui','ui', 'nesiui', 'eniui', 'ieniui'), //ui, iui
        'g' => array('ą', 'lią', 'ją', 'į', 'į', 'ių', 'ų', 'nesį', 'enį', 'ienį'), //į
        'in' => array('u', 'liu', 'ju', 'iu', 'žiu', 'iumi', 'umi', 'nesiu', 'eniu', 'ieniu'),
        'vt' => array('e', 'lyje', 'jyje', 'yje', 'dyje', 'iuje', 'uje', 'nesyje', 'enyje', 'ienyje'),
        's' => array('e', 'ly', 'jau', 'i', 'dy', 'iau', 'au', 'nesi', 'enie', 'ieni')
    );
    protected $noun_endings_sing_mas_lt = array ( 
        2 => array ( 0 => 'as', 1 => 'is', 2 => 'ys', 3 => 'us', 4 => 'uo', 5 => 'jo', 6 => 'io', 7 => 'ui', 8 => 'ą', 9 => 'į', 10 => 'ų', 11 => 'ju', 12 => 'iu', 13 => 'ly', 14 => 'dy', 15 => 'au', ), 
        4 => array ( 0 => 'lias', 1 => 'žio', 2 => 'iaus', 3 => 'liui', 4 => 'lią', 5 => 'enį', 6 => 'žiu', 7 => 'iumi', 8 => 'eniu', 9 => 'lyje', 10 => 'jyje', 11 => 'dyje', 12 => 'iuje', 13 => 'nesi', 14 => 'enie', 15 => 'ieni', ), 
        3 => array ( 0 => 'jas', 1 => 'ius', 2 => 'nuo', 3 => 'lio', 4 => 'aus', 5 => 'ens', 6 => 'jui', 7 => 'iui', 8 => 'ją', 9 => 'ių', 10 => 'liu', 11 => 'umi', 12 => 'yje', 13 => 'uje', 14 => 'jau', 15 => 'iau', ), 
        5 => array ( 0 => 'ienis', 1 => 'nesio', 2 => 'ienio', 3 => 'žiui', 4 => 'eniui', 5 => 'nesį', 6 => 'ienį', 7 => 'nesiu', 8 => 'ieniu', 9 => 'enyje', ), 
        1 => array ( 0 => 'o', 1 => 'u', 2 => 'e', 3 => 'i', ), 
        6 => array ( 0 => 'nesiui', 1 => 'ieniui', 2 => 'nesyje', 3 => 'ienyje', ), 
    );
    //galvos, vyšnios, saujos, gervės, aikštės, katės, sūnūs, skaičiai, pavojai, avys, dantys (vandenys)
    protected $noun_cases_plu = array(
        // 'v' => array('os', 'ios', 'jos', 'ės', 'štės', 'tės', 'ūs', 'iai', 'ai', 'vys', 'ys'),
        //bandom verst i vienaskaita iskart
        //dantis vanduo i vienaskaita blogai
        'v' => array('a', 'ia', 'ja', 'ė', 'štė', 'tė', 'us', 'ius', 'us', 'vis', 'is'),
        'k' => array('ų', 'ių', 'jų', 'ių', 'ščių', 'čių', 'ų', 'ių', 'ų', 'vių', 'ų'),
        'n' => array('oms', 'ioms', 'joms', 'ėms','štėms', 'tėms', 'ums', 'iams', 'ams', 'vims', 'ims'),
        'g' => array('as', 'ias', 'jas', 'es', 'štes', 'tes', 'us', 'ius', 'us', 'vis', 'is'),
        'in' => array('omis', 'iomis', 'jomis', 'ėmis', 'štėmis', 'tėmis', 'umis', 'iais', 'ais', 'vimis', 'imis'),
        'vt' => array('ose', 'iose', 'jose', 'ėse', 'štėse', 'tėse', 'uose', 'iuose', 'uose', 'vyse', 'yse'),
    );
    protected $noun_endings_plu = array ( 
        8 => array ( 0 => 'štėmis', ), 
        7 => array ( 0 => 'ščių', 1 => 'štėms', 2 => 'štėse', ), 
        6 => array ( 0 => 'tėmis', ), 
        5 => array ( 0 => 'štė', 1 => 'čių', 2 => 'tėms', 3 => 'štes', 4 => 'iomis', 5 => 'jomis', 6 => 'ėmis', 7 => 'vimis', 8 => 'tėse', 9 => 'iuose', ), 
        4 => array ( 0 => 'vių', 1 => 'ioms', 2 => 'joms', 3 => 'ėms', 4 => 'iams', 5 => 'vims', 6 => 'omis', 7 => 'umis', 8 => 'iais', 9 => 'imis', 10 => 'iose', 11 => 'jose', 12 => 'ėse', 13 => 'uose', 14 => 'vyse', ), 
        3 => array ( 0 => 'tė', 1 => 'ius', 2 => 'vis', 3 => 'ių', 4 => 'jų', 5 => 'oms', 6 => 'ums', 7 => 'ams', 8 => 'ims', 9 => 'ias', 10 => 'jas', 11 => 'tes', 12 => 'ais', 13 => 'ose', 14 => 'yse', ), 
        2 => array ( 0 => 'ia', 1 => 'ja', 2 => 'ė', 3 => 'us', 4 => 'is', 5 => 'ų', 6 => 'as', 7 => 'es', ), 
        1 => array ( 0 => 'a', ), 
    );
    //sesuo išimtis
    protected $noun_exceptions = array(
        'sesuo' => array('sesers', 'seseriai', 'seserį', 'seserimi', 'seseria', 'seseryje', 'seserie'),
    );
    //galininko linksnio galūnės sutampa mot ir vyr giminėse, todėl giminė nustatoma pagal prieš galūnę einančią raidę
    protected $gender_endings = array(
        'ą' => array(
            //vyrą agentūrą???
            //vyrą vėją, streiką, tekstą
            'mas' => array('r', 'j', 'k', 't'),
            //galvą saują
            'fem' => array('v', 'uj')
        ),
        'į' => array(
            //brolį, gaidį, lokį, dantį, vandenį
            'mas' => array('l', 'd', 'k', 't', 'n'),
            //seserį, avį
            'fem' => array('r', 'v')
        ),
        'e' => array(
            //tekste
            'mas' => array('st'),
            //bite
            'fem' => array('it')
        ),
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
        $plu = $this->getLemmatized($word, $this->noun_endings_plu, $this->noun_cases_plu);
        // var_dump($mas);
        // var_dump($fem);
        // var_dump($plu);
        
        if(($fem && $fem['case'] == 'v') || ($mas && $mas['case'] == 'v') || ($plu && $plu['case'] == 'v')) {
            return false;
        }
        $max = $this->maxLength(array($mas, $fem, $plu));
        $lemma = false;
        if($fem && $mas && $fem['length'] == $max && $mas['length'] == $max) {
            if(!$this->getGender($word)) {
                $lemma = $fem['lemma'];
            } else {
                $lemma = $mas['lemma'];
            }
        } else if($fem && $fem['length'] == $max) {
            $lemma = $fem['lemma'];
        } else if($mas && $mas['length'] == $max) {
            $lemma = $mas['lemma'];
        } else if($plu && $plu['length'] == $max) {
            $lemma = $plu['lemma'];
        }
        if(!$lemma || $lemma == $word) {
            return false;
        } else {
            return $lemma;
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
                foreach($noun_cases as $key => $case) {
                    if(($i = array_search($last, $case)) !== false) {
                        return array('length' => $start, 'lemma' => substr($word, 0, $length - $start) . $noun_cases['v'][$i], 'case' => $key);
                    }
                }
                return false;
            }
            $start--;
        }
        return false;
    }

    public function getGender($word) {
        $last = substr($word, -2);
        if(!key_exists($last, $this->gender_endings)) {
            $last = substr($word, -1);
        }
        if(key_exists($last, $this->gender_endings)) {
            $last2 = substr($word, -3);
            $last3 = substr($word, -4);
            foreach($this->gender_endings[$last] as $gender_key => $gender) {
                foreach($gender as $before) {
                    if($last2 == $before . $last || $last3 == $before . $last) {
                        if($gender_key == "mas") {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function formEndings()
    {
        $endings = array();
        foreach($this->noun_cases_sing_mas_lt as $case) {
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

    private function maxLength($arrays) {
        $max = 0;
        foreach($arrays as $array) {
            if($array && key_exists('length', $array) && $array['length'] > $max) {
                $max = $array['length'];
            }
        }
        return $max;
    }
}