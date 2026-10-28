<?php

namespace App\Http\Controllers;

use App\Models\Taom;

class SiteController extends Controller
{
    public function showSite()
    {
        $malumot = Taom::query()->latest()->get();
        return view('site', compact('malumot'));
    }

    public function showAdmin()
    {
        $taomlar = Taom::query()->latest()->get();
        $jamiTaom = $taomlar->count();
        $jamiMiqdor = $taomlar->sum(function ($taom) { return (int) $taom->miqdori; });
        $ortachaNarx = $jamiTaom ? (int) round($taomlar->avg(function ($taom) { return (float) $taom->narxi; })) : 0;
        return view('admin', compact('taomlar', 'jamiTaom', 'jamiMiqdor', 'ortachaNarx'));
    }
}
