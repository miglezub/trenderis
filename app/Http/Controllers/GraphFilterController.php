<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GraphFilterController extends Controller
{
    public function dayResults()
    {
        $start = microtime(true);
        $user = \App\Models\User::find(1);
        $texts = $user->texts()->get()->toArray();
        $results = array();
        foreach($texts as $text) {
            $analysis = \App\Models\TextAnalysis::where('text_id', '=', $text['id'])->orderBy('updated_at')->take(1)->get()->last();
            if($analysis) {
                foreach(json_decode($analysis->results) as $a) {
                    if(!key_exists($a->w, $results)) {
                        $results[$a->w] = array();
                        $results[$a->w]['freq'] = $a->freq;
                        $results[$a->w]['tf'] = $a->tf;
                        if(isset($a->tfidf)) {
                            if(key_exists('tfidf', $results[$a->w])) {
                                $results[$a->w]['tfidf'] += $a->tfidf;
                            } else {
                                $results[$a->w]['tfidf'] = $a->tfidf;
                            }
                        }
                    } else {
                        $results[$a->w]['freq'] += $a->freq;
                        $results[$a->w]['tf'] += $a->tf;
                        if(!key_exists('tfidf', $results[$a->w])) {
                            $results[$a->w]['tfidf'] = 0;
                        }
                        if(isset($a->tfidf)) {
                            $results[$a->w]['tfidf'] += $a->tfidf;
                        } else {
                            $results[$a->w]['tfidf'] += $a->tf;
                        }
                    }
                }
            }
        }
        $res = array();
        foreach($results as $key => $result) {
            $results[$key]['w'] = $key;
            $res[] = $results[$key];
        }
        usort($results, array(TextAnalysisController::class, "cmpFreqFirst"));
        $results = array_splice($results, 0, 20, true);
        $end = microtime(true);
        return response()->json(array('results' => $results, 'time' => $end - $start));
    }
}
