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
        return response()->json(array('results' => $this->getResults($texts, $user), 'time' => $end - $start));
    }

    public function filter(Request $request) {
        set_time_limit(0);
        $start = microtime(true);
        $user = $request->user();
        if($user && isset($request->type)) {
            switch ($request->type) {
                case 1:
                    if($request->initial == 1) {
                        $minDate = $user->texts->sortByDesc('created_at')->values()->first();
                        if($minDate == null) {
                            $minDate = date("Y-m-d H:i:s");
                        } else {
                            $minDate = $minDate->created_at;
                        }
                        if(!empty($request->date1) && $request->date1 > $minDate) {
                            $request->date1 = $minDate;
                        }
                    }
                    if(empty($request->date2)) {
                        $request->date2 = $request->date1;
                    }
                    if(!empty($request->date1) && is_string($request->date1) && strpos($request->date1, "T")) {
                        $search_date1 = $request->date1 = substr($request->date1, 0, strpos($request->date1, "T"));
                    } else if(!empty($request->date1) && !is_string($request->date1)) {
                        $search_date1 = $request->date1 = $request->date1->format("Y-m-d");
                    } else if (is_string($request->date1) && !strpos($request->date1, "T")){
                        $search_date1 = $request->date1;
                    } else {
                        $search_date1 = $request->date1 = $user->texts->min('created_at');
                    }
                    if(!empty($request->date2) && is_string($request->date2) && strpos($request->date2, "T")) {
                        $search_date2 = $request->date2 = substr($request->date2, 0, strpos($request->date2, "T"));
                    } else if(!empty($request->date2) && !is_string($request->date2)) {
                        $search_date2 = $request->date2 = $request->date2->format("Y-m-d");
                    } else if (is_string($request->date2) && !strpos($request->date2, "T")){
                        $search_date2 = $request->date2;
                    } else {
                        $search_date2 = $request->date2 = $user->texts->max('created_at');
                    }
                    $search = $user->graphFilters()
                        ->where('date_from', '=', $search_date1)
                        ->where('date_to', '=', $search_date2)
                        ->where('api_key_id', '=', $request->api_key)
                        ->orderBy('created_at', 'DESC')
                        ->get();
                        // $query = str_replace(array('?'), array('\'%s\''), $search->toSql());
                        // $query = vsprintf($query, $search->getBindings());
                        // var_dump($query);
                    if($search->count()) {
                        return response()->json(json_decode($search[0]->result));
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
            $end = microtime(true);
            switch ($request->type) {
                case 1:
                    $res = array('total' => $response['total'], 'time' => $end - $start, 'title' => $title, 'results' => $response['results']);
                    $user->graphFilters()->create([
                        'result' => json_encode($res, JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_UNICODE),
                        'date_from' => $request->date1,
                        'date_to' => $request->date2,
                        'api_key_id' => $request->api_key
                    ]);
                    return response()->json($res);
                break;
                case 2:
                    $res = array('time' => $end - $start, 'title' => $title, 'results' => $response);
                    return response()->json($res);
                default:
                    return; 
            }
        } else {
            return redirect('/login');
        }
    }

    private function filterTendency(Request $request) {
        $user = $request->user();
        $textsQuery = $user->texts();
        if(!empty($request->date1)) {
            $textsQuery->whereDate('created_at', '>=', $request->date1);
        }
        if(!empty($request->date2)) {
            $textsQuery->whereDate('created_at', '<=', $request->date2);
        }
        if(!empty($request->api_key)) {
            $textsQuery->where('api_key_id', '=', $request->api_key);
        }
        $texts = $textsQuery->get()->toArray();
        if(count($texts) > 0) {
            return $this->getResults($texts, $request);
        } else {
            return array('total' => 0, 'results' => array());
        }
    }

    private function filterKeyword(Request $request) {
        $user = $request->user();
        $textsQuery =  DB::table('texts')
                        ->select(DB::raw('DATE(texts.created_at) as created_at'), 'text_analysis.results')
                        ->join('text_analysis', 'texts.id', '=', 'text_analysis.text_id')
                        ->where('texts.user_id', '=', $user->id);
        if(!empty($request->date1)) {
            $textsQuery->whereDate('texts.created_at', '>=', $request->date1);
        }
        if(!empty($request->date2)) {
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
            // $keyword2 = json_encode($keyword);
            // $keyword2 = str_replace("\"", '', $keyword);
            // $textsQuery->where('results', 'LIKE', "%\"" . $keyword . "\"%");
            // $textsQuery->orWhere('results', 'LIKE', "%\"" . $keyword2 . "\"%");
            $textsQuery->where('original_text', 'LIKE', "%" . $keyword . "%");
        }
        $texts = $textsQuery->orderBy('texts.id', 'ASC')
                ->orderBy('text_analysis.id', 'desc')
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
                    if(isset($res->tfidf)) {
                        $results[$text->created_at]['tfidf'] += $res->tfidf;
                    }
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

    private function getResults($texts, $request, $limit = 1) {
        $results = array();
        $total_texts = count($texts);
        $total_query = $request->user()->textIds();
        if($request->api_key) {
            $total_query->where('api_key_id', '=', $request->api_key);
        }
        $total_query->where('language_id', '=', $texts[0]['language_id']);
        $total_query->limit(3000)->orderBy('id', 'DESC');
        $total_ids = $total_query->pluck('id')->toArray();
        $total_documents = count($total_ids);
        $total_documents = $total_query->count();
        if($total_documents / 4 > 3000) {
            $total_documents = 3000;
        } else {
            $total_documents = (int) $total_documents / 4;
        }
        foreach($texts as $text) {
            $analysis = \App\Models\TextAnalysis::where('text_id', '=', $text['id'])->orderBy('id', 'DESC')->take(1)->get()->last();
            if($analysis && isset($analysis->results)) {
                $decoded = json_decode($analysis->results);
                if(is_array($decoded)) {
                    foreach($decoded as $a) {
                        if(!is_bool($a) && isset($a->w)) {
                            if(!key_exists($a->w, $results) && ($limit && ((isset($a->idf) && $a->idf >= $limit) || !isset($a->idf)))) {
                                $results[$a->w] = array();
                                $results[$a->w]['w'] = $a->w;
                                $results[$a->w]['freq'] = $a->freq;
                                $results[$a->w]['tf'] = $a->tf;
                                if(isset($a->idf)
                                    && (!key_exists('idf', $results[$a->w]) 
                                    || (key_exists('idf', $results[$a->w]) && $a->idf < $results[$a->w]['idf']))) {
                                        
                                        $results[$a->w]['idf'] = $a->idf;
                                } else {
                                    $results[$a->w]['idf'] = 1;
                                }
                                $results[$a->w]['tfidf'] = $results[$a->w]['tf'] * $results[$a->w]['idf'];
                                if($limit && $results[$a->w]['idf'] < $limit) {
                                    unset($results[$a->w]);
                                }
                            } else if(key_exists($a->w, $results)) {
                                $results[$a->w]['freq'] += $a->freq;
                                $results[$a->w]['tf'] += $a->tf;
                                if(!key_exists('tfidf', $results[$a->w])) {
                                    $results[$a->w]['tfidf'] = 0;
                                }
                                if(isset($a->idf)
                                    && $a->idf != 1
                                    && (!key_exists('idf', $results[$a->w]) 
                                    || (key_exists('idf', $results[$a->w]) && $a->idf < $results[$a->w]['idf']))) {
                                        
                                        $results[$a->w]['idf'] = $a->idf;
                                } else if($results[$a->w]['idf'] == 1 && $results[$a->w]['tfidf'] > ($total_texts / 35)) {
                                    $search = '%' . $a->w . '%';
                                    $document_count = $request->user()->texts()->whereIn('id', $total_ids)->where('language_id', '=', $text['language_id'])->whereRaw('lower(original_text) like (?)',["{$search}"])->count();
                                    if($document_count > 0) {
                                        $a->idf = log($total_documents/$document_count, 10);
                                        $results[$a->w]['idf'] = $a->idf;
                                    }
                                }
                                $results[$a->w]['tfidf'] = $results[$a->w]['tf'] * $results[$a->w]['idf'];
                                if($limit && $results[$a->w]['idf'] < $limit) {
                                    unset($results[$a->w]);
                                }
                            }
                        }
                    }
                }
            }
        }
        usort($results, array(TextAnalysisController::class, "cmp"));
        $results = array_splice($results, 0, 20, true);
        return array('results' => $results, 'total' => count($texts));
    }

    private function getResultsOld($texts) {
        $results = array();
        foreach($texts as $text) {
            $analysis = \App\Models\TextAnalysis::where('text_id', '=', $text['id'])->orderBy('id', 'DESC')->take(1)->get()->last();
            if($analysis && $analysis->results) {
                foreach(json_decode($analysis->results) as $a) {
                    if(!is_bool($a) && isset($a->w)) {
                        if(!key_exists($a->w, $results)) {
                            $results[$a->w] = array();
                            $results[$a->w]['w'] = $a->w;
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
        usort($results, array(TextAnalysisController::class, "cmp"));
        $results = array_splice($results, 0, 20, true);
        return array('results' => $results, 'total' => count($texts));
    }
}
