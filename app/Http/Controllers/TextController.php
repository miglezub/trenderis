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
}
