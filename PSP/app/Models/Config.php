<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model as Eloquent;

    class Config extends Eloquent {

        protected $table = 'config';
        protected  $fillable = [
            'id',
            'key',
            'label',
            'type',
            'value',
            'active',
            'special'
        ];


        public function active_config(){
            return $this->where('active', true);
        }



    }


