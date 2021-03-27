<?php

namespace App\Http\Controllers;
use App\Models\Text;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TextController extends Controller
{
    public function index() {
        $user = Auth::user();
        $texts = $user->texts()->get()->toArray();
        foreach($texts as $key => $text) {
            if(strlen($text['original_text']) > 200) {
                $texts[$key]['original_text'] = nl2br(substr($text['original_text'], 0, strpos($text['original_text'], " " , 200))) . " ...";
            } else {
                $texts[$key]['original_text'] = nl2br($text['original_text']);
            }
        }
        return array_reverse($texts);
    }

    public function show($id) {
        $user = Auth::user();
        $text = $user->texts()->find($id);
        return response()->json($text);
    }

    public function update($id, Request $request)
    {
        $user = Auth::user();
        $text = $user->texts()->find($id);
        $text->update($request->all());
        return response()->json('Text updated!');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $text = $user->texts()->find($id);
        $text->delete();
        return response()->json('Text deleted!');
    }

    public function store()
    {
        $data=request()->validate([
            'original_text' => 'required',
            'language_id' => 'required',
            'use_idf' => 'nullable|boolean',
            'use_word2vec' => 'nullable|boolean',
        ]);

        $newText = auth()->user()->texts()->create([
            'original_text' => html_entity_decode($data['original_text']),
            'language_id' => $data['language_id'],
            'use_idf' => $data['use_idf'],
            'use_word2vec' => $data['use_word2vec'],
        ]);

        if($newText) {
            return response()->json('Success');
        } else {
            return response()->json('Error');
        }
    }
}
