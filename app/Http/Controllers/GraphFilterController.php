<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use \App\Models\ApiKey;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GraphFilterController extends Controller
{
    public function dayResults()
    {
        $start = microtime(true);
        $user = \App\Models\User::find(1);
        $texts = $user->texts()->get()->toArray();
        $end = microtime(true);
        return response()->json(array('results' => $this->getResults($texts), 'time' => $end - $start));
    }

    public function filter(Request $request) {
        $start = microtime(true);
        $user = $request->user();
        if($request->path() == 'portal/filterGraph' && !empty($request->key)) {
            $api_key = ApiKey::all()->where('key', '=', $request->key)->first();
            $user = $api_key->user;
            Auth::login($user);
            $user = Auth::user();
            $request->api_key = $request->key;
        }
        if($user && isset($request-> type)) {
            switch ($request->type) {
                case 1:
                    $minDate = $user->texts->last();
                    if($minDate == null) {
                        $minDate = date("Y-m-d H:i:s");
                    } else {
                        $minDate = $minDate->created_at;
                    }
                    if(!empty($request->date1) && $request->date1 > $minDate) {
                        $request->date1 = $minDate;
                    }
                    $response = $this->filterTendency($request);
                    $title = 'Populiariausi raktažodžiai ';
                    if(!empty($request->date1)) {
                        $title .= '(' . Carbon::parse($request->date1)->format("Y-m-d");
                        if(!empty($request->date2)) {
                            $title .= ' - ' . Carbon::parse($request->date2)->format("Y-m-d");
                        }
                        $title .= ')';
                    }
                    break;
                case 2:
                    $response = $this->filterKeyword($request);
                    $title = 'Raktažodžio "' . $request->keyword . '" istorinis grafikas ';
                    if(!empty($request->date1)) {
                        $title .= '(' . Carbon::parse($request->date1)->format("Y-m-d");
                        if(!empty($request->date2)) {
                            $title .= ' - ' . Carbon::parse($request->date2)->format("Y-m-d");
                        }
                        $title .= ')';
                    }
                    break;
                default:
                    $response = array();
            }
            if($request->path() == 'portal/filterGraph') {
                Auth::logout();
            }
            $end = microtime(true);
            switch ($request->type) {
                case 1:
                    return response()->json(array('total' => $response['total'], 'time' => $end - $start, 'title' => $title, 'results' => $response['results']));
                break;
                case 2:
                    return response()->json(array('time' => $end - $start, 'title' => $title, 'results' => $response));
                default:
                    return; 
            }
        } else {
            if($request->path() == 'portal/filterGraph') {
                $json = array();
                if(empty($request-> type)) {
                    $json['type'] = "Nenurodytas grafiko tipas";
                }
                if(empty($request-> api_key)) {
                    $json['key'] = "Nurodytas blogas API raktas";
                }
                if($request->type != 2 && empty($request->date1) && empty($request->date2)) {
                    $json['date'] = "Nenurodytos datos";
                }
                if($request->type == 2 && empty($request->keyword)) {
                    $json['keyword'] = "Nenurodytas raktažodis";
                }
                return response()->json($json);
            }
            return redirect('/login');
        }
    }

    private function filterTendency(Request $request) {
        $user = $request->user();
        $textsQuery = $user->texts();
        if(!empty($request->date1)) {
            $textsQuery->whereDate('created_at', '>=', $request->date1);
        }
        if(!empty($request->date2) && $request->date2 != $request->date1) {
            $textsQuery->whereDate('created_at', '<=', $request->date2);
        }
        if(!empty($request->api_key)) {
            $textsQuery->where('api_key_id', '=', $request->api_key);
        }
        $texts = $textsQuery->get()->toArray();
        return $this->getResults($texts);
    }

    private function filterKeyword(Request $request) {
        $user = $request->user();
        $textsQuery =  DB::table('texts')
                        ->join('text_analysis', 'texts.id', '=', 'text_analysis.text_id')
                        ->select(DB::raw('DATE(texts.created_at) as created_at'), 'text_analysis.results')
                        ->where('texts.user_id', '=', $user->id);
        if(!empty($request->date1)) {
            $textsQuery->whereDate('texts.created_at', '>=', $request->date1);
        }
        if(!empty($request->date2) && $request->date2 != $request->date1) {
            $textsQuery->whereDate('texts.created_at', '<=', $request->date2);
        }
        if(!empty($request->api_key)) {
            $textsQuery->where('api_key_id', '=', $request->api_key);
        }
        $keyword = "";
        if(!empty($request->keyword)) {
            $keyword = strip_tags($request->keyword);
            $keyword = mb_strtolower($keyword);
            $keyword = str_replace(array('.', ',', "\n", "\t", "\r", "!", "?", ":", ";", "(", ")", "[", "]", "\"", "“", "„", " – "), ' ', $keyword);
            $textsQuery->where('results', 'LIKE', "%\"" . $keyword . "\"%");
        }
        $texts = $textsQuery->orderBy('texts.id', 'ASC')
                ->orderBy('text_analysis.created_at', 'ASC')
                ->groupBy('texts.id')
                ->get();
        $results = array();
        foreach($texts as $text) {
            if(!key_exists($text->created_at, $results)) {
                $results[$text->created_at] = array();
                $results[$text->created_at]['date'] = '';
                $results[$text->created_at]['freq'] = 0;
                $results[$text->created_at]['tfidf'] = 0;
                $results[$text->created_at]['total'] = 0;
            }
            $results[$text->created_at]['date'] = $text->created_at;
            if(!empty($keyword)) {
                $pos = strpos($text->results, $keyword);
                if($pos) {
                    $results[$text->created_at]['total']++;
                    $ending = strpos($text->results, "}", $pos) + 1;
                    $res = substr($text->results, 0, $ending);
                    $start = strrpos($res, "{");
                    $res = json_decode(substr($res, $start, $ending));
                    $results[$text->created_at]['freq'] += $res->freq;
                    $results[$text->created_at]['tfidf'] += $res->tfidf;
                }
            } else {
                $results[$text->created_at]['total']++;
            }
        }
        $res = array();
        foreach($results as $key => $result) {
            $res[] = $results[$key];
        }
        return $res;
    }

    private function getResults($texts) {
        $results = array();
        foreach($texts as $text) {
            $analysis = \App\Models\TextAnalysis::where('text_id', '=', $text['id'])->orderBy('updated_at')->take(1)->get()->last();
            if($analysis && $analysis->results) {
                foreach(json_decode($analysis->results) as $a) {
                    if(!is_bool($a)) {
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
        }
        $res = array();
        foreach($results as $key => $result) {
            $results[$key]['w'] = $key;
            $res[] = $results[$key];
        }
        usort($results, array(TextAnalysisController::class, "cmp"));
        $results = array_splice($results, 0, 20, true);
        return array('results' => $results, 'total' => count($texts));
    }
}
