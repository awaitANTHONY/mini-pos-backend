<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PHPHtmlParser\Dom;

class ApiController extends Controller
{
    public function settings(Request $request)
    {
        $validator = \Validator::make($request->all(), [

            'platform' => 'required',
            
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => false, 'message' => $validator->errors()->first()]);
        }

        $not_allowed_keys = [

            'site_title',
            'android_onesignal_app_id',
            'android_onesignal_api_key',
            'android_firebase_server_key',
            'android_firebase_topics',
            'ios_onesignal_app_id',
            'ios_onesignal_api_key',
            'ios_firebase_server_key',
            'ios_firebase_topics',

        ];

        $platform = $request->platform;
        $hidden_platform = ($platform == 'ios' ? 'android' : 'ios');
        
        $settings = Cache::rememberForever("settings", function () use ($request, $platform, $hidden_platform, $not_allowed_keys){
            $settings = Setting::select("*", \DB::raw("REPLACE(name, '{$platform}_', '') as name"))
                                ->where("name", "not like", "%{$hidden_platform}%")
                                ->whereNotIn("name", $not_allowed_keys)
                                ->pluck("value", "name")
                                ->toArray();
            return $settings;
        });

        return response()->json(['status' => true, 'data' => $settings]);
    }

    public function categories(Request $request)
    {
        $base_url = url('/');
        
        $categories = Cache::rememberForever("categories", function (){
            $categories = \App\Models\Category::where('status', 1)
                                ->orderBy('id', 'ASC')
                                ->get();

            return $categories;
        });

        return response()->json(['status' => true, 'data' => $categories]);
    }
}