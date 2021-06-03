<?php

namespace App\Http\Controllers;

use App\Models\TextAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TextAnalysisController extends Controller
{
    static function cmp($a, $b)
    {
        if(!key_exists("tfidf", $a) || empty($a['tfidf'])) {
            $a["tfidf"] = (key_exists("tf", $a)) ? $a["tf"] : 0;
        }
        if(!key_exists("tfidf", $b) || empty($b['tfidf'])) {
            $b["tfidf"] = (key_exists("tf", $b)) ? $b["tf"] : 0;
        }
        if (key_exists("tfidf", $a) && !empty($a['tfidf']) && key_exists("tfidf", $b) && !empty($b['tfidf'])) {
            if($a['tfidf'] == $b['tfidf']) {
                if ($a['tf'] == $b['tf']){
                    return (key($a) < key($b)) ? -1 : 1;
                }
                return ($a['tf'] < $b['tf']) ? 1 : -1;
            } else {
                return (($a["tfidf"]-$b["tfidf"]) < 0) ? 1 : -1;
            }
        } else if (key_exists("tf", $a) && !empty($a['tf'])) {
            if ($a['tf'] == $b['tf']){
                return 0;
            }
            return ($a['tf'] < $b['tf']) ? 1 : -1;
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

    public function analyse($id, $user_id, $analyse_limit = 50, $api_key_id = null, $idf_limit = 2000)
    {
        $start = microtime(true);
        if(!$analyse_limit) {
            $analyse_limit = 50;
        }
        $analysis = TextAnalysis::find($id);
        $text = $analysis->text;
        $tokenizer = new TokenizerCustom($text->original_text, $text->language_id);
        $tokenized = $tokenizer->tokenize();
        $total = count($tokenized);
        $results = array_count_values($tokenized);
        arsort($results);
        $lemmatizer = new Lemmatizer($text->language_id);
        $user = User::find($user_id);
        $total_query = $user->textIds();
        if($api_key_id) {
            $total_query->where('api_key_id', '=', $api_key_id);
        }
        $total_query->where('language_id', '=', $text->language_id)->limit($idf_limit)->orderBy('id', 'DESC');
        $total_ids = $total_query->pluck('id')->toArray();
        $total_documents = count($total_ids);
        if($total_documents / 4 > $idf_limit) {
            $total_documents = $idf_limit;
        } else {
            $total_documents = (int) $total_documents / 4;
        }

        $index = 0;
        $count = count($results) > $analyse_limit ? count($results) / 5 : count($results);
        $count = $count > $analyse_limit ? $analyse_limit : $count;
        $maxtf = 0;
        $max = 0;
        $first = true;
        foreach($results as $key => $result) {
            if($first) {
                $maxtf = $result/$total;
                $first = false;
                $max = $maxtf;
            }
            if(!is_array($results[$key])) {
                $results[$key] = array();
                $results[$key]['w'] = $key;
                $results[$key]['freq'] = $result;
                // $results[$key]['tf'] = $result/$total;
                $results[$key]['tf'] = 0.5 + (1 - 0.5) * ($result/$total) / $maxtf;
            } else {
                $results[$key]['w'] = $key;
                $results[$key]['freq'] += $result;
                // $results[$key]['tf'] += $result/$total;
                $results[$key]['tf'] = 0.5 + (1 - 0.5) * ($results[$key]['freq']/$total) / $maxtf;
            }
            $results[$key]['lemma'] = $lemmatizer->lemmatize($key);
            if($text->use_idf) {
                if($index++ < $count) {
                    $search = '%' . $key . '%';
                    $document_query = $user->textIds();
                    if($api_key_id) {
                        $document_query->where('api_key_id', '=', $api_key_id);
                    }
                    $document_query->whereIn('id', $total_ids)->where('language_id', '=', $text->language_id)->whereRaw('lower(original_text) like (?)',["{$search}"]);
                    $document_count = $document_query->count();
                    // $document_count = 0;
                    // foreach($user->texts()->where('language_id', '=', $text->language_id)->whereRaw('lower(original_text) like (?)',["{$search}"])->get() as $document) {
                    //     $tanalysis = $document->text_analysis->last();
                    //     if($tanalysis && strpos($tanalysis->results, $search2)) {
                    //         $document_count++;
                    //     }
                    // }
                    // $document_count = $user->texts()->where('original_text', 'like', $search)->count();
                    if($document_count > 0) {
                        $results[$key]['idf'] = log($total_documents/$document_count, 10);
                    } else {
                        $results[$key]['idf'] = 1;
                    }
                    if($results[$key]['idf'] < 0) {
                        $results[$key]['idf'] = 0.01;
                    }
                    $results[$key]['tfidf'] = $results[$key]['tf'] * $results[$key]['idf'];
                } else {
                    $results[$key]['idf'] = 1;
                    $results[$key]['tfidf'] = $results[$key]['tf'];
                }
            }
            // if($text->use_word2vec) {
            //     $synonyms = new Synonyms(array($text->id));
            //     $synonyms->trainModel();
            //     $synonyms->getSynonyms();
            // }
            if($results[$key]['freq']/$total > $max) {
                $max = $results[$key]['freq']/$total;
            }
            if(key_exists('lemma', $results[$key]) && $results[$key]['lemma'] && key_exists($results[$key]['lemma'], $results)) {
                if(!is_array($results[$results[$key]['lemma']])) {
                    $results[$results[$key]['lemma']] = array();
                    $results[$results[$key]['lemma']]['freq'] = $results[$key]['freq'];
                    $results[$results[$key]['lemma']]['tf'] = 0.5 + (1 - 0.5) * ($results[$results[$key]['lemma']]['freq']/$total) / $maxtf;
                } else {
                    $results[$results[$key]['lemma']]['freq'] += $results[$key]['freq'];
                    $results[$results[$key]['lemma']]['tf'] = 0.5 + (1 - 0.5) * ($results[$results[$key]['lemma']]['freq']/$total) / $maxtf;
                }
                if($results[$results[$key]['lemma']]['freq']/$total > $max) {
                    $max = $results[$results[$key]['lemma']]['freq']/$total;
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
        if($max > $maxtf) {
            foreach($results as $key => $result) {
                $results[$key]['tf'] = 0.5 + (1 - 0.5) * ($results[$key]['freq']/$total) / $max;
                if($text->use_idf) {
                    if(isset($results[$key]['idf'])) {
                        $results[$key]['tfidf'] = $results[$key]['tf'] * $results[$key]['idf'];
                    } else {
                        $results[$key]['idf'] = 1;
                        $results[$key]['tfidf'] = $results[$key]['tf'];
                    }
                }
            }
        }
        usort($results, array($this, "cmp"));
        $end = microtime(true);
        $analysis->update([
            'results' => json_encode($results, JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_UNICODE ), 
            'top_results' => count($results) > 5 ? array_slice($results, 0, 5, true) : $results, 
            'duration' => $end - $start]);
        if($text->use_word2vec) {
            DB::commit();
            $synonyms = new Synonyms(array($text->id));
            $synonyms->trainModel();
            $synonyms->getSynonyms();
            $results = json_decode(TextAnalysis::find($id)->results, true);
        }
        return array('results' => $results, 'text_id' => isset($text->external_id) ? $text->external_id : $text->id);
    }
}
