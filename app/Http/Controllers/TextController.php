<?php

namespace App\Http\Controllers;

use App\Models\Text;
use App\Models\TextAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TextController extends Controller
{
    public function index(Request $request) {
        $page = isset($request->page) ? $request->page : 1;
        $limit = isset($request->limit) ? $request->limit : 10;

        $user = $request->user();
        if($user) {
            $texts = $user->texts()->orderBy('created_at', 'DESC')->offset(($page-1) * $limit)->limit($limit)->get()->toArray();
            foreach($texts as $key => $text) {
                if(strlen($text['title']) == 0) {
                    if(strlen($text['original_text']) > 150) {
                        $texts[$key]['title'] = nl2br(substr($text->original_text, 0, strpos($text['original_text'], " " , 150))) . " ...";
                    } else {
                        $texts[$key]['title'] = nl2br($text['original_text']);
                    }
                }
                $analysis = $user->texts->find($text['id'])->text_analysis->last();
                $texts[$key]['top_results'] = array();
                if($analysis) {
                    if($analysis->top_results) {
                        foreach(json_decode($analysis->top_results) as $result) {
                            $texts[$key]['top_results'][] = $result->w;
                        }
                    } else {
                        $res = json_decode($analysis->results);
                        if($res) {
                            $top = array_splice($res, 0, 5, true);
                            foreach($top as $result) {
                                $texts[$key]['top_results'][] = $result->w;
                            }
                            $analysis->update(['top_results' => json_encode($top) ]);
                        }
                    }
                }
            }
            return array('results' => $texts, 'total' => $user->texts()->count());
        } else {
            return redirect('/login');
        }
    }
    
    public function filter(Request $request)
    {
        $page = isset($request->page) ? $request->page : 1;
        $limit = isset($request->limit) ? $request->limit : 10;

        $user = $request->user();
        if($user) {
            $textsQuery =  DB::table('texts')
                        // ->join('text_analysis', 'texts.id', '=', 'text_analysis.text_id')
                        ->select('texts.*')
                        ->where('texts.user_id', '=', $user->id);
            $textsTotalQuery =  DB::table('texts')
                        // ->join('text_analysis', 'texts.id', '=', 'text_analysis.text_id')
                        ->select('texts.*')
                        ->where('texts.user_id', '=', $user->id);    
            if($request->date1) {
                $textsQuery->whereDate('texts.created_at', '>=', $request->date1);
                $textsTotalQuery->whereDate('texts.created_at', '>=', $request->date1);
            }
            if($request->date2) {
                $textsQuery->whereDate('texts.created_at', '<=', $request->date2);
                $textsTotalQuery->whereDate('texts.created_at', '<=', $request->date2);
            }
            if($request->key) {
                $textsQuery->where('api_key_id', '=', $request->key);
                $textsTotalQuery->where('api_key_id', '=', $request->key);
            }
            if(!empty($request->keyword)) {
                // $keyword = strip_tags($request->keyword);
                // $keyword = mb_strtolower($keyword);
                // $keyword = str_replace(array('.', ',', "\n", "\t", "\r", "!", "?", ":", ";", "(", ")", "[", "]", "\"", "“", "„", " – "), ' ', $keyword);
                // $keyword2 = json_encode($keyword);
                // $keyword2 = str_replace("\"", '', $keyword);
                // $textsQuery->where('results', 'LIKE', "%\"" . $keyword . "\"%");
                // $textsQuery->orWhere('results', 'LIKE', "%\"" . $keyword2 . "\"%");
                // $textsTotalQuery->where('results', 'LIKE', "%\"" . $keyword . "\"%");
                // $textsTotalQuery->orWhere('results', 'LIKE', "%\"" . $keyword2 . "\"%");
                $textsQuery->where('original_text', 'LIKE', "%" . $request->keyword . "%");
                $textsQuery->orWhere('title', 'LIKE', "%" . $request->keyword . "%");
                $textsTotalQuery->where('original_text', 'LIKE', "%" . $request->keyword . "%");
                $textsTotalQuery->orWhere('title', 'LIKE', "%" . $request->keyword . "%");
            }
            $texts = $textsQuery->orderBy('created_at', 'DESC')->groupBy('texts.id')->offset(($page-1) * $limit)->limit($limit)->get()->toArray();
            $total = count($textsTotalQuery->groupBy('texts.id')->get());
            foreach($texts as $key => $text) {
                if(strlen($text->title) == 0) {
                    if(strlen($text->original_text) > 150) {
                        $texts[$key]->title = nl2br(substr($text->original_text, 0, strpos($text->original_text, " " , 150))) . " ...";
                    } else {
                        $texts[$key]->title = nl2br($text->original_text);
                    }
                }
                $analysis = $user->texts->find($text->id)->text_analysis->last();
                $texts[$key]->top_results = array();
                if($analysis) {
                    if($analysis->top_results) {
                        foreach(json_decode($analysis->top_results) as $result) {
                            if(isset($result->w)) {
                                $texts[$key]->top_results[] = $result->w;
                            }
                        }
                    } else {
                        $res = json_decode($analysis->results);
                        if($res) {
                            $top = array_splice($res, 0, 5, true);
                            foreach($top as $result) {
                                $texts[$key]->top_results[] = $result->w;
                            }
                            $analysis->update(['top_results' => json_encode($top) ]);
                        }
                    }
                }
            }
            return array('results' => $texts, 'total' => $total);
        } else {
            return redirect('/login');
        }
    }

    public function show($id, Request $request, $external = false) {
        $user = $request->user();
        if($user) {
            $json = array();
            $text = $external ? $user->texts()->where('external_id', '=', $id)->first() : $user->texts()->find($id);
            if($text) {
                $text['original_text'] = nl2br($text['original_text']);
                $analysis = $text->text_analysis->last();
                unset($text->text_analysis);
                $json['text'] = $text;
                if($analysis) {
                    if($request->limit) {
                        $results = json_decode($analysis->results);
                        if($request->limit < count($results)) {
                            $json['results'] = array_splice($results, 0, $request->limit , true);
                        } else {
                            $json['results'] = $results;
                        }
                    } else {
                        $json['results'] = json_decode($analysis->results);
                    }
                }
                if($request->showStatus) {
                    $json['status'] = 200;
                }
            } else {
                $json['error']['text'] = "Tekstas nerastas";
                if($request->showStatus) {
                    $json['status'] = 400;
                }
            }
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
            $user->graphFilters()
                ->where('date_from', '<=', $text['created_at']->format('Y-m-d'))
                ->where('date_to', '>=', $text['created_at']->format('Y-m-d'))
                ->delete();
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
                'title' => 'nullable',
                'original_text' => 'required',
                'language_id' => 'required',
                'use_idf' => 'nullable|boolean',
                'use_word2vec' => 'nullable|boolean',
            ]);
    
            $newText = $request->user()->texts()->create([
                'title' => html_entity_decode($data['title']),
                'original_text' => html_entity_decode($data['original_text']),
                'language_id' => $data['language_id'],
                'use_idf' => $data['use_idf'],
                'use_word2vec' => $data['use_word2vec'],
            ]);
    
            if($newText) {
                $request->user()->graphFilters()
                        ->where('date_from', '<=', date('Y-m-d H:i:s'))
                        ->where('date_to', '>=', date('Y-m-d H:i:s'))
                        ->delete();
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
        set_time_limit(150);
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
        // Cache::put('botis_page', 30);
        $user = \App\Models\User::find(1);
        set_time_limit(1500);
        $ch = curl_init();
        $page = Cache::get('botis_page');var_dump($page);

        curl_setopt($ch, CURLOPT_URL, config('constants.botis_url') . "&page=" . $page);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($output, true);
        foreach($json as $text) {
            var_dump($text['id']);
            if(!$user->texts()->where('title', '=', html_entity_decode($text['title']))->first()) {
                $newText = $user->texts()->create([
                    'original_text' => html_entity_decode($text['description']),
                    'title' => html_entity_decode($text['title']),
                    'language_id' => 1,
                    'use_idf' => 1,
                    'use_word2vec' => 0,
                    'created_at' => $text['created_at'],
                    'external_id' => $text['id'],
                    'api_key_id' => 2
                ]);
        
                if($newText) {
                    $user->graphFilters()
                        ->where('date_from', '<=', substr($text['created_at'], 0, strpos($text['created_at'], "T")))
                        ->where('date_to', '>=', substr($text['created_at'], 0, strpos($text['created_at'], "T")))
                        ->delete();
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

        if($page > 1) {
            Cache::put('botis_page', $page - 1);
            // sleep(10);
            return redirect()->route('import');
        }
    }

    public function recalculate()
    {
        set_time_limit(150);
        $texts = \App\Models\Text::all()->orderBy('created_at', 'DESC');
        $limit = 30;
        foreach($texts as $text) {
            if($limit < 1) {
                return;
            }
            // if(!$text->text_analysis->last() || $text->text_analysis->last()->updated_at < date("Y-m-d H:i:s", strtotime("-1 hours"))) {
                $analysis = $text->text_analysis()->create([
                    'lemmatized_text' => '',
                    'results' => '',
                    'use_idf' => 1,
                    'use_word2vec' => $text->use_word2vec
                ]);
                $analysisController = new TextAnalysisController();
                $analysisController->analyse($analysis->id, $text->user_id);
                $limit--;
            // }
        }
    }

    public function find_synonyms() {
        $synonyms = new Synonyms(array(7148));
        $synonyms->getSynonyms();
    }
}
