<?php

namespace App\Http\Controllers\Api\News;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
   
    public function index()
    {
        return response()->json(
			Article::with(['categories'])->paginate(20)->toArray()
        );
    }

    public function store(Request $request)
    {
		$articleData = $request->only("title", "sub_title",  "keywords", "image",  "excerpt",  "slug",  "published",  "published_at",  "content",  "saved", "categories");
		if($articleData['published']){
			$articleData['published_at'] = date('Y-m-d H:i:s');
		}
        return response()->json(Article::create($articleData));
    }


    public function show(Article $article)
    {
        return response()->json($article->load(['categories'])->toArray());
    }


    public function update(Request $request, Article $article)
    {
        return response()->json(Article::where('id',$article->id)->update($article));
    }

    public function destroy($id)
    {
        Article::where('id', $id)->delete();
    }
}
