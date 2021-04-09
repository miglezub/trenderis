<?php

namespace App\Http\Controllers;

use App\Models\TextAnalysis;
use Illuminate\Http\Request;

class TextController extends Controller
{
    public function index(Request $request) {
        $user = $request->user();
        if($user) {
            $texts = $user->texts()->get()->toArray();
            foreach($texts as $key => $text) {
                if(strlen($text['original_text']) > 200) {
                    $texts[$key]['original_text'] = nl2br(substr($text['original_text'], 0, strpos($text['original_text'], " " , 200))) . " ...";
                } else {
                    $texts[$key]['original_text'] = nl2br($text['original_text']);
                }
            }
            return array_reverse($texts);
        } else {
            return redirect('/login');
        }
    }

    public function filter(Request $request)
    {
        $user = $request->user();
        if($user) {
            $texts = $user->texts();
            $textsQuery = $user->texts();
            if($request->date1) {
                $textsQuery->whereDate('created_at', '>=', $request->date1);
            }
            if($request->date1) {
                $textsQuery->whereDate('created_at', '<=', $request->date2);
            }
            $texts = $textsQuery->get()->toArray();
            foreach($texts as $key => $text) {
                if(strlen($text['original_text']) > 200) {
                    $texts[$key]['original_text'] = nl2br(substr($text['original_text'], 0, strpos($text['original_text'], " " , 200))) . " ...";
                } else {
                    $texts[$key]['original_text'] = nl2br($text['original_text']);
                }
            }
            return array_reverse($texts);
        } else {
            return redirect('/login');
        }
    }

    public function show($id, Request $request) {
        $user = $request->user();
        if($user) {
            $json = array();
            $text = $user->texts()->find($id);
            $text['original_text'] = nl2br($text['original_text']);
            $analysis = $text->text_analysis->last();
            if($analysis) {
                $json['results'] = json_decode($analysis->results);
            }
            $json['text'] = $text;
            return response()->json($json);
        } else {
            return redirect('/login');
        }
    }

    public function update($id, Request $request)
    {
        $user = $request->user();
        if($user) {
            $text = $user->texts()->find($id);
            $text->update($request->all());
            return response()->json(['success' => 'Tekstas atnaujintas']);
        } else {
            return redirect('/login');
        }
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();
        if($user) {
            $text = $user->texts()->find($id);
            foreach($text->text_analysis as $analysis) {
                $analysis->delete();
            }
            $text->delete();
            return response()->json(['success' => 'Tekstas ištrintas']);
        } else {
            return redirect('/login');
        }
    }

    public function store(Request $request)
    {
        if($request->user()) {
            $data = request()->validate([
                'original_text' => 'required',
                'language_id' => 'required',
                'use_idf' => 'nullable|boolean',
                'use_word2vec' => 'nullable|boolean',
            ]);
    
            $newText = $request->user()->texts()->create([
                'original_text' => html_entity_decode($data['original_text']),
                'language_id' => $data['language_id'],
                'use_idf' => $data['use_idf'],
                'use_word2vec' => $data['use_word2vec'],
            ]);
    
            if($newText) {
                $analysis = $newText->text_analysis()->create([
                    'lemmatized_text' => '',
                    'results' => '',
                    'use_idf' => $data['use_idf'],
                    'use_word2vec' => $data['use_word2vec']
                ]);
                $analysisController = new TextAnalysisController();
                $analysisController->analyse($analysis->id, auth()->user()->id);
                return response()->json(['success' => 'Tekstas pridėtas sėkmingai', 'id' => $newText->id ]);
            } else {
                return response()->json(['error' => 'Klaida']);
            }
        } else {
            return redirect('/login');
        }
    }

    public function analyse($id, Request $request)
    {
        $user = $request->user();
        if($user) {
            $text = $user->texts()->find($id);
            if($text) {
                $data=request()->validate([
                    'language_id' => 'required',
                    'use_idf' => 'nullable|boolean',
                ]);
                $text->update($data);
                $analysis = $text->text_analysis()->create([
                    'lemmatized_text' => '',
                    'results' => '',
                    'use_idf' => $data['use_idf'],
                    'use_word2vec' => $text->use_word2vec
                ]);
                $analysisController = new TextAnalysisController();
                $analysisController->analyse($analysis->id, $user->id);
                return response()->json(['success' => 'Analizes rezultatai atnaujinti.']);
            } else {
                return response()->json(['error' => 'Klaida']);
            }
        } else {
            return redirect('/login');
        }
    }

    public function lemmatize($word)
    {
        $lemmatizer = new Lemmatizer(1);
        return response()->json($lemmatizer->lemmatize($word));
    }

    public function wordEndings()
    {
        $lemmatizer = new Lemmatizer(1);
        return response()->json($lemmatizer->formEndings());
    }

    public function import()
    {
        set_time_limit(150);
        $user = \App\Models\User::find(1);
        $ch = curl_init();

        $page = 1;
        curl_setopt($ch, CURLOPT_URL, config('constants.botis_url') . "&page=" . $page);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($output, true);
        foreach($json as $text) {
            $newText = $user->texts()->create([
                'original_text' => html_entity_decode($text['description']),
                'title' => html_entity_decode($text['title']),
                'language_id' => 1,
                'use_idf' => 1,
                'use_word2vec' => 0,
                'created_at' => $text['created_at']
            ]);
    
            if($newText) {
                $analysis = $newText->text_analysis()->create([
                    'lemmatized_text' => '',
                    'results' => '',
                    'use_idf' => 1,
                    'use_word2vec' => 0
                ]);
                $analysisController = new TextAnalysisController();
                $analysisController->analyse($analysis->id, auth()->user()->id);
            }
        }
    }

    public function dayResults()
    {
        $start = microtime(true);
        $user = \App\Models\User::find(1);
        $texts = $user->texts()->get()->toArray();
        $results = array();
        foreach($texts as $text) {
            $analysis = TextAnalysis::where('text_id', '=', $text['id'])->orderBy('updated_at')->take(1)->get()->last();
            if($analysis) {
                foreach(json_decode($analysis->results) as $a) {
                    if(!key_exists($a->w, $results)) {
                        $results[$a->w] = array();
                        $results[$a->w]['freq'] = $a->freq;
                        $results[$a->w]['tf'] = $a->tf;
                        if(isset($a->tfidf)) {
                            if(key_exists('tf-idf', $results[$a->w])) {
                                $results[$a->w]['tf-idf'] += $a->tfidf;
                            } else {
                                $results[$a->w]['tf-idf'] = $a->tfidf;
                            }
                        }
                    } else {
                        $results[$a->w]['freq'] += $a->freq;
                        $results[$a->w]['tf'] += $a->tf;
                        if(!key_exists('tf-idf', $results[$a->w])) {
                            $results[$a->w]['tf-idf'] = 0;
                        }
                        if(isset($a->tfidf)) {
                            $results[$a->w]['tf-idf'] += $a->tfidf;
                        } else {
                            $results[$a->w]['tf-idf'] += $a->tf;
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
        usort($results, array(TextAnalysisController::class, "cmp"));
        $results = array_splice($results, 0, 20, true);
        $end = microtime(true);
        return response()->json(array('results' => $results, 'time' => $end - $start));
    }

    public function recalculate()
    {
        set_time_limit(150);
        $texts = \App\Models\Text::all();
        foreach($texts as $text) {
            $analysis = $text->text_analysis()->create([
                'lemmatized_text' => '',
                'results' => '',
                'use_idf' => 1,
                'use_word2vec' => $text->use_word2vec
            ]);
            $analysisController = new TextAnalysisController();
            $analysisController->analyse($analysis->id, $text->user_id);
        }
    }
}
