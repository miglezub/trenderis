<?php

namespace App\Http\Controllers;

use App\Models\TextAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TextController extends Controller
{
    public function index(Request $request) {
        $user = $request->user();
        if($user) {
            $texts = $user->texts()->get()->toArray();
            foreach($texts as $key => $text) {
                if(strlen($text['title']) == 0) {
                    if(strlen($text['original_text']) > 150) {
                        $texts[$key]['title'] = nl2br(substr($text['original_text'], 0, strpos($text['original_text'], " " , 150))) . " ...";
                    } else {
                        $texts[$key]['title'] = nl2br($text['original_text']);
                    }
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
            $textsQuery =  DB::table('texts')
                        ->join('text_analysis', 'texts.id', '=', 'text_analysis.text_id')
                        ->select('texts.*')
                        ->where('texts.user_id', '=', $user->id);
            if($request->date1) {
                $textsQuery->whereDate('texts.created_at', '>=', $request->date1);
            }
            if($request->date2) {
                $textsQuery->whereDate('texts.created_at', '<=', $request->date2);
            }
            if($request->key) {
                $textsQuery->where('api_key_id', '=', $request->key);
            }
            if(!empty($request->keyword)) {
                $keyword = strip_tags($request->keyword);
                $keyword = mb_strtolower($keyword);
                $keyword = str_replace(array('.', ',', "\n", "\t", "\r", "!", "?", ":", ";", "(", ")", "[", "]", "\"", "“", "„", " – "), ' ', $keyword);
                $textsQuery->where('results', 'LIKE', "%\"" . $keyword . "\"%");
            }
            $texts = $textsQuery->get()->toArray();
            foreach($texts as $key => $text) {
                if(strlen($text->title) == 0) {
                    if(strlen($text->original_text) > 150) {
                        $texts[$key]['title'] = nl2br(substr($text['original_text'], 0, strpos($text->original_text, " " , 150))) . " ...";
                    } else {
                        $texts[$key]['title'] = nl2br($text->original_text);
                    }
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
        $user = \App\Models\User::find(1);
        set_time_limit(300);
        $ch = curl_init();
        $page = Cache::get('botis_page');

        curl_setopt($ch, CURLOPT_URL, config('constants.botis_url') . "&page=" . $page);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($output, true);
        foreach($json as $text) {
            if(!$user->texts()->where('title', '=', html_entity_decode($text['title']))->first()) {
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

        if($page > 1) {
            Cache::put('botis_page', $page - 1);
        }
    }

    public function recalculate()
    {
        set_time_limit(150);
        $texts = \App\Models\Text::all();
        $limit = 10;
        foreach($texts as $text) {
            if($limit < 1) {
                return;
            }
            if(!$text->text_analysis->last() || $text->text_analysis->last()->updated_at < date("Y-m-d H:i:s", strtotime("-1 hours"))) {
                $analysis = $text->text_analysis()->create([
                    'lemmatized_text' => '',
                    'results' => '',
                    'use_idf' => 1,
                    'use_word2vec' => $text->use_word2vec
                ]);
                $analysisController = new TextAnalysisController();
                $analysisController->analyse($analysis->id, $text->user_id);
                $limit--;
            }
        }
    }
}
