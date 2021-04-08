<?php

namespace App\Http\Controllers;
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
            $json['results'] = json_decode($analysis->results);
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
}
