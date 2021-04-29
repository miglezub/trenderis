<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\ApiKey;
use \App\Models\Language;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        if($request->text_id) {
            $user = Auth::user();
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            $request->showStatus = true;
            $this->logout();
            return app('App\Http\Controllers\TextController')->show($request->text_id, $request);
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
        if(count($request->texts) > 10 && !isset($request->keyword)) {
            $json['error']['callback'] = "Nenurodyta callback nuoroda";
            $error = true;
        }
        if(!isset($request->language) ) {
            $json['error']['language'] = "Nenurodyta kalba (1 lt, 2 en)";
            $error = true;
        }
        if(isset($request->language) && !Language::find($request->language)) {
            //tikrint ar gera kalba
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
                DB::beginTransaction();
                $error_ids = array();
                $successful_ids = array();
                foreach($request->texts as $text) {
                    if(isset($text['id'])) {
                        if(isset($text['description'])) {
                            $newText = $user->texts()->create([
                                'title' => key_exists('title', $text) ? html_entity_decode($text['title']) : "",
                                'original_text' => html_entity_decode($text['description']),
                                'language_id' => $request->language,
                                'use_idf' => $type == "tfidf" ? true : false,
                                'use_word2vec' => $word2vec,
                                'external_id' => $text['id'],
                                'api_key_id' => $request->api_key,
                                'created_at' => key_exists('created_at', $text) ? $text['created_at'] : date('Y-m-d H:i:s')
                            ]);
                            if($newText) {
                                $created_at = key_exists('created_at', $text) ? substr($text['created_at'], 0, strpos($text['created_at'], "T")) : date('Y-m-d');
                                $user->graphFilters()
                                        ->where('date_from', '<=', $created_at)
                                        ->where('date_to', '>=', $created_at)
                                        ->delete();
                                $analysis = $newText->text_analysis()->create([
                                    'lemmatized_text' => '',
                                    'results' => '',
                                    'use_idf' => $type == "tfidf" ? true : false,
                                    'use_word2vec' => $word2vec
                                ]);
                                $analysisController = new TextAnalysisController();
                                $analysisController->analyse($analysis->id, auth()->user()->id);
                                $successful_ids[] = $text['id'];
                            } else {
                                $error_ids[] = $text['id'];
                            }
                        } else {
                            $error_ids[] = $text['id'];
                        }
                    } else {
                        $json['error']['texts'] = "Privaloma nurodyti kiekvieno teksto id.";
                        $error = true;
                        $json['status'] = 400;
                        DB::rollBack();
                        $this->logout();
                        return response()->json($json);
                    }
                }
                DB::commit();
                if(count($error_ids) > 0) {
                    $json['error_ids'] = $error_ids;
                    $json['status'] = 400;
                }
                if(count($successful_ids) > 0) {
                    $json['success_ids'] = $successful_ids;
                    $json['status'] = 200;
                }
                return response()->json($json);
            } else {
                $json['status'] = 202;
                $this->logout();
                $json['msg'] = "Uzklausa apdorojama asinchroniskai. Analizes rezultatai bus grazinti i nurodyta callback nuoroda.";
                return response()->json($json);
            }
        } else {
            $json['status'] = 400;
            $this->logout();
            return response()->json($json);
        }
    }
}
