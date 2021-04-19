<?php

namespace App\Http\Controllers;

use App\Models\TextAnalysis;
use App\Models\User;

class TextAnalysisController extends Controller
{
    static function cmp($a, $b)
    {
        if (key_exists("tfidf", $a) && !empty($a['tfidf'])) {
            if($a['tfidf'] == $b['tfidf']) {
                if ($a['freq'] == $b['freq']){
                    return (key($a) < key($b)) ? -1 : 1;
                }
                return ($a['freq'] < $b['freq']) ? 1 : -1;
            } else {
                return (($a["tfidf"]-$b["tfidf"]) < 0) ? 1 : -1;
            }
        } else if (key_exists("freq", $a) && !empty($a['freq'])) {
            if ($a['freq'] == $b['freq']){
                return 0;
            }
            return ($a['freq'] < $b['freq']) ? 1 : -1;
        } else {
            return 0;
        }
    }

    static function cmpFreqFirst($a, $b)
    {
        if (key_exists("freq", $a) && !empty($a['freq'])) {
            if ($a['freq'] == $b['freq']){
                return 0;
            }
            return ($a['freq'] < $b['freq']) ? 1 : -1;
        } else if (key_exists("tfidf", $a) && !empty($a['tfidf'])) {
            if($a['tfidf'] == $b['tfidf']) {
                if ($a['freq'] == $b['freq']){
                    return (key($a) < key($b)) ? -1 : 1;
                }
                return ($a['freq'] < $b['freq']) ? 1 : -1;
            } else {
                return (($a["tfidf"]-$b["tfidf"]) < 0) ? 1 : -1;
            }
        } else {
            return 0;
        }
    }

    public function analyse($id, $user_id)
    {
        $start = microtime(true);
        $analysis = TextAnalysis::find($id);
        $text = $analysis->text;
        $tokenizer = new TokenizerCustom($text->original_text, $text->language_id);
        $tokenized = $tokenizer->tokenize();
        $total = count($tokenized);
        $results = array_count_values($tokenized);
        arsort($results);
        $lemmatizer = new Lemmatizer($text->language_id);
        $user = User::find($user_id);
        $total_documents = $user->texts()->count();
        foreach($results as $key => $result) {
            if(!is_array($results[$key])) {
                $results[$key] = array();
                $results[$key]['freq'] = $result;
                $results[$key]['tf'] = $result/$total;
            } else {
                $results[$key]['freq'] += $result;
                $results[$key]['tf'] += $result/$total;
            }
            $results[$key]['lemma'] = $lemmatizer->lemmatize($key);
            $results[$key]['w'] = $key;
            if($text->use_idf) {
                $search = '%' . $key . '%';
                $search2 = '"' . $key . '"';
                // $document_count = $user->texts()->whereRaw('lower(original_text) like (?)',["{$search}"])->count();
                $document_count = 0;
                foreach($user->texts()->whereRaw('lower(original_text) like (?)',["{$search}"])->get() as $document) {
                    $tanalysis = $document->text_analysis->last();
                    if($tanalysis && strpos($tanalysis->results, $search2)) {
                        $document_count++;
                    }
                }
                // $document_count = $user->texts()->where('original_text', 'like', $search)->count();
                if($document_count > 0) {
                    $results[$key]['idf'] = log($total_documents/$document_count, 10);
                } else {
                    $results[$key]['idf'] = 1;
                }
                $results[$key]['tfidf'] = $results[$key]['tf'] * $results[$key]['idf'];
            }
            if(key_exists('lemma', $results[$key]) && $results[$key]['lemma'] && key_exists($results[$key]['lemma'], $results)) {
                if(!is_array($results[$results[$key]['lemma']])) {
                    $results[$results[$key]['lemma']] = array();
                    $results[$results[$key]['lemma']]['freq'] = $results[$key]['freq'];
                    $results[$results[$key]['lemma']]['tf'] = $results[$key]['tf'];
                } else {
                    $results[$results[$key]['lemma']]['freq'] += $results[$key]['freq'];
                    $results[$results[$key]['lemma']]['tf'] += $results[$key]['tf'];
                }
                if($text->use_idf) {
                    // $results[$results[$key]['lemma']]['idf'] += $results[$key]['idf'];
                    if(!key_exists('idf', $results[$results[$key]['lemma']])) {
                        $results[$results[$key]['lemma']]['idf'] = 1;
                    }
                    $results[$results[$key]['lemma']]['tfidf'] = $results[$results[$key]['lemma']]['tf'] * $results[$results[$key]['lemma']]['idf'];
                }
                $results[$results[$key]['lemma']]['incl'][] = $key;
                unset($results[$key]);
            }
        }
        usort($results, array($this, "cmp"));
        $top_results = array_splice($results, 0, 5, true);
        $end = microtime(true);
        $analysis->update(['results' => json_encode($results, JSON_INVALID_UTF8_IGNORE), 'top_results' => $top_results, 'duration' => $end - $start]);
    }
}
