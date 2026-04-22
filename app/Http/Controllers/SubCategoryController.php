<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    public function GetSub(){
        $data= SubCategory::with('category')->withCount('products')->latest()->get();
        return apiResponse($data,200,'get scuessfully');
    }
    public function AddSub(Request $request){
        $data= $request-> validate([
             'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id'
        ]);
        $sub= SubCategory::create($data);
        return apiResponse($sub,200,'created');
    }
    public function UpdateSub(Request $request, $id){
        $sub= SubCategory::findOrFail($id);
         $data = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id'
        ]);

        $sub->update($data);

        return apiResponse($sub, 200, 'Updated');
    }
    public function DeleteSub($id){
        SubCategory::findOrFail($id)->delete();
        return apiResponse(null,200,'deleted');
    }
}
