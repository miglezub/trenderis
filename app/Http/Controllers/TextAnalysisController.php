<?php

namespace App\Http\Controllers;

use App\Models\TextAnalysis;
use App\Models\User;

use Illuminate\Http\Request;
use Symfony\Component\CssSelector\Parser\Token;

class TextAnalysisController extends Controller
{
    static function analyse($id, $user_id)
    {
        $analysis = TextAnalysis::find($id);
        $text = $analysis->text;
        $tokenizer = new Tokenizer($text->original_text, $text->language_id);
        $tokenized = $tokenizer->tokenize();
        $total = count($tokenized);
        $results = array_count_values($tokenized);
        arsort($results);
        $user = User::find($user_id);
        $total_documents = $user->texts()->count();
        foreach($results as $key => $result) {
            if(strlen($key) > 0) {
                $results[$key] = array();
                $results[$key]['freq'] = $result;
                $results[$key]['tf'] = $result/$total;
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
            } else {
                unset($results[$key]);
            }
        }
        $analysis->update(['results' => serialize($results)]);
    }
}