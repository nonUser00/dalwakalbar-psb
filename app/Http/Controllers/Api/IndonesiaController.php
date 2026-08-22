<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

class IndonesiaController extends Controller
{
    public function provinces(Request $request)
    {
        $query = Province::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function cities(Request $request)
    {
        $request->validate([
            'province_code' => 'required',
        ]);

        $query = City::where('province_code', $request->province_code);

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function districts(Request $request)
    {
        $request->validate([
            'city_code' => 'required',
        ]);

        $query = District::where('city_code', $request->city_code);

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function villages(Request $request)
    {
        $request->validate([
            'district_code' => 'required',
        ]);

        $query = Village::where('district_code', $request->district_code);

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return response()->json($query->orderBy('name')->get());
    }
}
