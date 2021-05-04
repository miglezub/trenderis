<?php

namespace App\Listeners;

use App\Events\AsyncAnalysis;
use App\Http\Controllers\ApiRequestController;
use App\Models\ApiRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\TextAnalysisController;
use Illuminate\Queue\InteractsWithQueue;

class AnalyseAsyncListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AsyncAnalysis  $event
     * @return void
     */
    public function handle(AsyncAnalysis $event)
    {
        $api_request = ApiRequest::find($event->api_request->id);
        $texts = $event->api_request->texts;
        // Log::debug($event->api_request->id);
        // Log::debug($texts);
        $res = array();
        $text_res = array();
        foreach($texts as $text) {
            // $text->update(['title' => 'Async']);
            $analysis = $text->text_analysis()->create([
                'lemmatized_text' => '',
                'results' => '',
                'use_idf' => $text['use_idf'],
                'use_word2vec' => $text['use_word2vec']
            ]);
            if($analysis) {
                $analysisController = new TextAnalysisController();
                $results = $analysisController->analyse($analysis->id, $event->api_request->user_id);
                if($results) {
                    $text->update(['api_request_id' => null]);
                    $res[] = $results;
                    $text_res[$text['external_id']] = count($results['results']) > 10 ? array_slice($results['results'], 0, 10, true) : $results['results'];
                }
            }
        }
        $apiRequestController = new ApiRequestController();
        $allResults = $apiRequestController->getResults($res);
        $json = array();
        $json['results'] = $allResults['results'];
        $json['total'] = count($texts);
        $json['texts'] = $text_res;
        $encoded_json = json_encode($json, JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_UNICODE);

        $ch = curl_init($event->api_request->callback);                                                                      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_json);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',                                                                                
            'Content-Length: ' . strlen($encoded_json))                                                                       
        );                                                                                                                 
        $result = curl_exec($ch);
        Log::debug($result);
    }
}
