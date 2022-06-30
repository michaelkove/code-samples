<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model as Eloquent;


    class StaticPage extends Eloquent
    {
        public $table = 'static_pages';
        protected  $fillable = [
            'id',
            "link_title",
            "title",
            "slug",
            'active',
            'content',
            'user_id',
            'footer_menu_order',
            'order'
        ];


//    php artisan make:migration add_footer_menu_order_to_static_pages_table --table=static_pages


        public $timestamps = [

        ];

        public function user()
        {
            return $this->belongsTo('\App\Models\User', 'user_id');
        }

        public function getRouteKeyName() {
            return 'slug';
        }
    }
