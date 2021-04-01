<?php

namespace App\Http\Controllers;

use App\Models\TextAnalysis;
use App\Models\User;

class TextAnalysisController extends Controller
{
    public function analyse($id, $user_id)
    {
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
            $results[$key] = array();
            $results[$key]['freq'] = $result;
            $results[$key]['tf'] = $result/$total;
            $results[$key]['lemma'] = $lemmatizer->lemmatize($key);
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
                $results[$key]['tf-idf'] = $results[$key]['tf'] * $results[$key]['idf'];
            }
        }
        foreach($results as $key => $result) {
            if(key_exists('lemma', $results[$key]) && $results[$key]['lemma'] && key_exists($results[$key]['lemma'], $results)) {
                $results[$results[$key]['lemma']]['freq'] += $results[$key]['freq'];
                $results[$results[$key]['lemma']]['tf'] += $results[$key]['tf'];
                if($text->use_idf) {
                    // $results[$results[$key]['lemma']]['idf'] += $results[$key]['idf'];
                    $results[$results[$key]['lemma']]['tf-idf'] = $results[$results[$key]['lemma']]['tf'] * $results[$results[$key]['lemma']]['idf'];
                }
                $results[$results[$key]['lemma']]['incl'][] = $key;
            }
        }
        $analysis->update(['results' => json_encode($results, JSON_INVALID_UTF8_IGNORE)]);
    }
}
