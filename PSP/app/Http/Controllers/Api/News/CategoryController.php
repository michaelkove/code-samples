<?php

namespace App\Http\Controllers\Api\News;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
	
    public function index()
    {
        return response()->json(Category::get());
    }
	

   
    public function store(Request $request)
    {
		$categoryData = [
			'label' => $request->label
		];
       return response()->json(
		   Category::create($categoryData)
       );
    }

    
    public function show(Category $category)
    {
        return response()->json($category->toArray());
    }
	
 
    public function update(Request $request, $id)
    {
		$categoryData = ['label' => $request->label];
        return response()->json(Category::where('id',$id)->update($categoryData));
    }

  
    public function destroy($id)
    {
	    return response()->json(Category::where('id',$id)->delete());
    }
}
