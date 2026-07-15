<?php

namespace App\Http\Controllers;

use App\Http\Models\Community;
use App\Http\Models\Locality;
use App\Http\Models\Province;

class GEOController extends Controller
{
    //funcion para sacar las comunidades
    public static function getCommunities(){

        $communities = Community::all();

        return response()->json(['communities' => $communities], 200);
    }

    //funcion para sacar las provincias de una comunidad
    public static function getProvinces($community){

        $provinces = Province::where('acom_name', $community)->get();

        return response()->json(['provinces' => $provinces], 200);
    }

    //funcion para sacar las localidades de una provincia
    public static function getLocalities($province){

        $localities = Locality::where('prov_name', $province)->get();

        return response()->json(['localities' => $localities], 200);
    }
}
