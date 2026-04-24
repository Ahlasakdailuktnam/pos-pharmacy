<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function getUnits(){
        return apiResponse(Unit::latest()->get(),200,'success');
    }
    public function AddUnits(Request $req){
        $data= $req->validate([
            'name'=> 'required',
            'symbol'=> 'nullable'
        ]);
        $unit=Unit::create($data);
        return apiResponse($unit,200,'sccuess');
    }
}
