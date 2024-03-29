<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index(Request $request) {
        $user = $request->user();
        if($user) {
            $apiKeys = $user->apiKeys()->get()->toArray();
            foreach($apiKeys as $key => $value) {
                $apiKey = $user->apiKeys()->find($value['id']);
                $apiKeys[$key]['textCount'] = $apiKey->texts()->count();
            }
            return $apiKeys;
        } else {
            return redirect('/login');
        }
    }

    public function show($id, Request $request) {
        $user = $request->user();
        if($user) {
            $apiKey = $user->apiKeys()->find($id);
            return response()->json($apiKey);
        } else {
            return redirect('/login');
        }
    }

    public function update($id, Request $request)
    {
        $user = $request->user();
        if($user) {
            $query = $user->apiKeys()->where('name', '=', $request['name'])->where('id', '!=', $id)->first();
            if($query == null) {
                $apiKey = $user->apiKeys()->find($id);
                $apiKey->update($request->all());
                return response()->json(['success' => 'API raktas atnaujintas']);
            } else {
                return response()->json(['error' => 'API rakto pavadinimas turi būti unikalus']);
            }
        } else {
            return redirect('/login');
        }
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();
        if($user) {
            $apiKey = $user->apiKeys()->find($id);
            foreach($apiKey->texts as $text) {
                $text->api_key_id = NULL;
                $text->save();
            }
            foreach($apiKey->filters as $filter) {
                $filter->delete();
            }
            $apiKey->delete();
            return response()->json(['success' => 'API raktas ištrintas']);
        } else {
            return redirect('/login');
        }
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if($user) {
            $search = "Sistema%";
            $index = 1;
            $keys = $user->apiKeys()->where('name', 'like', $search)->get()->toArray();
            foreach($keys as $key) {
                $i = intval(substr($key['name'], 8));
                if($i > $index) {
                    $index = $i;
                }
            }
            $hash = hash('md5', Str::random(60));
            while(ApiKey::where("key", "=", $hash)->count() > 0) {
                $hash = hash('md5', Str::random(60));
            }
            $newKey = $user->apiKeys()->create([
                'key' => $hash,
                'name' => 'Sistema ' . ++$index,
            ]);
            return response()->json(['success' => 'API raktas sugeneruotas sėkmingai', 'id' => $newKey->id ]);
        } else {
            return redirect('/login');
        }
    }
}
