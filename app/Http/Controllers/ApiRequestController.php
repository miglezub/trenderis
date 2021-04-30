<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\ApiKey;
use \App\Models\Language;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use \App\Events\AsyncAnalysis;
use App\Models\ApiRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ApiRequestController extends Controller
{
    public function auth(Request $request)
    {
        if(!empty($request->key)) {
            $api_key = ApiKey::all()->where('key', '=', $request->key)->first();
            if(!$api_key || !isset($api_key->user)) {
                return "Nurodytas blogas API raktas";
            }
            $user = $api_key->user;
            Auth::login($user);
            $user = Auth::user();
            $request->api_key = $api_key->id;
            if($user) {
                return true;
            } else {
                return "Nurodytas blogas API raktas";
            }
        } else {
            return "Nenurodytas API raktas";
        }
    }

    public function logout()
    {
        Auth::logout();
    }

    public function text(Request $request)
    {
        if(!is_bool($msg = $this->auth($request))) {
            $this->logout();
            return response()->json(array('error' => ['key' => $msg], 'status' => 400));
        }
        if(isset($request->text_id)) {
            $request->limit = isset($request->limit) && is_numeric($request->limit) ? $request->limit : 20;
            $user = Auth::user();
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            $request->showStatus = true;
            $this->logout();
            return app('App\Http\Controllers\TextController')->show($request->text_id, $request, true);
        } else {
            $this->logout();
            return response()->json(array('error' => ['text_id' => 'Nenurodytas teksto id'], 'status' => 400));
        }
    }

    public function graph(Request $request) {
        if(!is_bool($msg = $this->auth($request))) {
            $this->logout();
            return response()->json(array('error' => ['key' => $msg], 'status' => 400));
        }
        $error = false;
        $json = array();
        if(!isset($request->type)) {
            $json['error']['type'] = "Nenurodytas grafiko tipas";
            $error = true;
        }
        if(isset($request->type) && $request->type == 2 && !isset($request->keyword)) {
            $json['error']['keyword'] = "Nenurodytas grafiko raktazodis";
            $error = true;
        }
        if(!isset($request->date1) && !isset($request->date2)) {
            $json['error']['date'] = "Nenurodytos datos";
            $error = true;
        }
        if(!$error) {
            $user = Auth::user();
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            $request->showStatus = true;
            $this->logout();
            return app('App\Http\Controllers\GraphFilterController')->filter($request);
        } else {
            $json['status'] = 400;
            $this->logout();
            return response()->json($json);
        }
    }
    //teksto formatas
    //id, source, title, description, created_at
    public function addTexts(Request $request)
    {
        if(!is_bool($msg = $this->auth($request))) {
            $this->logout();
            return response()->json(array('error' => ['key' => $msg], 'status' => 400));
        }
        $error = false;
        $json = array();
        if(!isset($request->texts)) {
            $json['error']['texts'] = "Nenurodyti tekstai";
            $error = true;
        }
        if(count($request->texts) >= 10 && !isset($request->callback)) {
            //validatint ar geras urlas
            $json['error']['callback'] = "Nenurodyta callback nuoroda";
            $error = true;
        }
        if(!isset($request->language) ) {
            $json['error']['language'] = "Nenurodyta kalba (1 lt, 2 en)";
            $error = true;
        }
        if(isset($request->language) && !Language::find($request->language)) {
            $json['error']['language'] = "Nurodytas blogas kalbos id (1 lt, 2 en)";
            $error = true;
        }
        if(isset($request->type)) {
            if(in_array($request->type, array("tf", "tfidf"))) {
                $type = $request->type;
            } else {
                $json['error']['type'] = "Nurodytas blogas analizes tipas (tf arba tfidf)";
                $error = true;
            }
        } else {
            $type = "tfidf";
        }
        if(isset($request->synonyms) && ($request->synonyms == 1 || $request->synonyms == 0)) {
            $word2vec = $request->synonyms;
        } else {
            $word2vec = 0;
        }
        if(!$error) {
            $user = Auth::user();
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            if(count($request->texts) < 10) {
                $json = $this->addAndAnalyse($request, $type, $word2vec);
                return response()->json($json);
            } else {
                //sukuriamas naujas api requestas, kiekvienas tekstas pridedamas i db
                //tekstams priskiriamas api request id, kai api requestas ivykdomas id padaromas null
                $api_request = $request->user()->apiRequests()->create([
                    'callback' => $request->callback,
                    ]);
                if($api_request) {
                    $json = $this->addToDB($request, $type, $word2vec, $api_request->id);
                    if($json['status'] == 200) {
                        event(new AsyncAnalysis($api_request));
                    }
                    return response()->json($json);
                } else {
                    $json['status'] = 400;
                    $json['msg'] = "Ivyko sistemos klaida";
                    return response()->json($json);
                }
            }
        } else {
            $json['status'] = 400;
            $this->logout();
            return response()->json($json);
        }
    }

    private function add($text, $user, $language, $type, $word2vec, $api_key, $analyse = true, $api_request_id = null) {
        $newText = $user->texts()->create([
            'title' => key_exists('title', $text) ? html_entity_decode($text['title']) : "",
            'original_text' => html_entity_decode($text['description']),
            'language_id' => $language,
            'use_idf' => $type == "tfidf" ? true : false,
            'use_word2vec' => $word2vec,
            'external_id' => $text['id'],
            'api_key_id' => $api_key,
            'created_at' => key_exists('created_at', $text) ? $text['created_at'] : date('Y-m-d H:i:s'),
            'api_request_id' => $api_request_id
        ]);
        $created_at = key_exists('created_at', $text) ? substr($text['created_at'], 0, strpos($text['created_at'], "T")) : date('Y-m-d');
        $user->graphFilters()
                ->where('date_from', '<=', $created_at)
                ->where('date_to', '>=', $created_at)
                ->delete();
        if($analyse && $newText) {
            $analysis = $newText->text_analysis()->create([
                'lemmatized_text' => '',
                'results' => '',
                'use_idf' => $type == "tfidf" ? true : false,
                'use_word2vec' => $word2vec
            ]);
            $analysisController = new TextAnalysisController();
            $results = $analysisController->analyse($analysis->id, auth()->user()->id);
            return $results;
        } else if(!$analyse && $newText) {
            return true;
        } else {
            return false;
        }
    }

    public function getResults($analysis) {
        $results = array();
        foreach($analysis as $a) {
            foreach($a as $word) {
                if(!is_bool($word) && key_exists("w", $word)) {
                    if(!key_exists($word['w'], $results)) {
                        $results[$word['w']] = array();
                        $results[$word['w']]['w'] = $word['w'];
                        $results[$word['w']]['freq'] = $word['freq'];
                        $results[$word['w']]['tf'] = $word['tf'];
                        if(isset($word['tfidf'])) {
                            if(key_exists('tfidf', $results[$word['w']])) {
                                $results[$word['w']]['tfidf'] += $word['tfidf'];
                            } else {
                                $results[$word['w']]['tfidf'] = $word['tfidf'];
                            }
                        }
                    } else {
                        $results[$word['w']]['freq'] += $word['freq'];
                        $results[$word['w']]['tf'] += $word['tf'];
                        if(!key_exists('tfidf', $results[$word['w']])) {
                            $results[$word['w']]['tfidf'] = 0;
                        }
                        if(isset($a->tfidf)) {
                            $results[$word['w']]['tfidf'] += $word['tfidf'];
                        } else {
                            $results[$word['w']]['tfidf'] += $word['tf'];
                        }
                    }
                }
            }
        }
        usort($results, array(TextAnalysisController::class, "cmp"));
        $results = count($results) > 20 ? array_slice($results, 0, 20, true) : $results;
        return $results;
    }

    private function addAndAnalyse(Request $request, $type, $word2vec)
    {
        $analysis = array();
        $texts = array();
        DB::beginTransaction();
        $error_ids = array();
        $successful_ids = array();
        foreach($request->texts as $text) {
            if(isset($text['id'])) {
                //teksta prideda tik tada jei turi descriptiona ir external id unikalu
                if(isset($text['description']) 
                    && !$request->user()->texts()->where('external_id', '=', $text['id'])->exists()) {
                        $a = $this->add($text, $request->user(), $request->language, $type, $word2vec, $request->api_key);
                        if($a != false) {
                            $analysis[] = $a;
                            $texts[$text['id']] = count($a) > 10 ? array_slice($a, 0, 10, true) : $a;
                            $successful_ids[] = $text['id'];
                        }
                } else {
                    $error_ids[] = $text['id'];
                }
            } else {
                $json['error']['texts'] = "Privaloma nurodyti kiekvieno teksto id.";
                $json['status'] = 400;
                DB::rollBack();
                $this->logout();
                return response()->json($json);
            }
        }
        DB::commit();
        if(count($error_ids) > 0) {
            $json['status'] = 400;
            $json['msg'] = "Tekstai neprideti. Nurodyti id kartojasi arba ivyko serverio klaida.";
            $json['error_ids'] = $error_ids;
        }
        if(count($successful_ids) > 0) {
            $json['texts'] = $texts;
            $json['results'] = $this->getResults($analysis);
            $json['status'] = 200;
            $json['msg'] = "Tekstai prideti.";
            $json['success_ids'] = $successful_ids;
        }
        return $json;
    }

    private function addToDB(Request $request, $type, $word2vec, $api_request_id) {
        DB::beginTransaction();
        $error_ids = array();
        $successful_ids = array();
        foreach($request->texts as $text) {
            if(isset($text['id'])) {
                if(isset($text['description']) 
                    && !$request->user()->texts()->where('external_id', '=', $text['id'])->exists()
                    && $this->add($text, $request->user(), $request->language, $type, $word2vec, $request->api_key, false, $api_request_id)) {
                            $successful_ids[] = $text['id'];
                } else {
                    $error_ids[] = $text['id'];
                }
            } else {
                $json['error']['texts'] = "Privaloma nurodyti kiekvieno teksto id.";
                $json['status'] = 400;
                DB::rollBack();
                $this->logout();
                return response()->json($json);
            }
        }
        DB::commit();
        if(count($error_ids) > 0) {
            $json['status'] = 400;
            $json['msg'] = "Tekstai neprideti. Nurodyti id kartojasi arba ivyko serverio klaida.";
            $json['error_ids'] = $error_ids;
        }
        if(count($successful_ids) > 0) {
            $json['status'] = 200;
            $json['msg'] = "Tekstai prideti ir bus apdorojami asinchroniskai. Analizes rezultatai bus grazinti i nurodyta callback nuoroda.";
            $json['success_ids'] = $successful_ids;
        }
        return $json;
    }

    public function callback(Request $request) {
        $json = json_encode($request->all(), JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_UNICODE);
        Log::debug($json);
        $fp = fopen('C:\xampp\htdocs\trenderis\storage\app\callback_response.json', 'w');
        fwrite($fp, $json);
        fclose($fp);
    }
}
