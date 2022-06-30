<?php

namespace App\Models;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    //
	protected $table = 'articles';
	
	protected $fillable = [
			'title',
			'sub_title',
			'keywords',
			'image',
			'excerpt',
			'slug',
			'published',
			'published_at',
			'content',
			'user_id',
	];
	
	/**
	 * Boot the model.
	 */
	protected static function boot()
	{
		parent::boot();
		
		static::created(function ($article) {
			$article->slug = $article->createSlug($article->title);
			$article->save();
		});
	}
	
	public function user(){
		return $this->belongsTo(User::class, 'created_by_user_id', 'id');
	}
	
	public function categories(){
		return $this->belongsToMany(Category::class)->withPivot('is_primary');
	}
	
	public function category(){
		return $this->categories()->wherePivot('is_primary',true);
	}
	

	private function createSlug($title){
		if (static::whereSlug($slug = Str::slug($title))->exists()) {
			$max = static::whereTitle($title)->latest('id')->skip(1)->value('slug');
			
			if (is_numeric($max[-1])) {
				return preg_replace_callback('/(\d+)$/', function ($mathces) {
					return $mathces[1] + 1;
				}, $max);
			}
			
			return "{$slug}-2";
		}
		
		return $slug;
	}
}
