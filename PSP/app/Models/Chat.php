<?php
	namespace App\Models;
	use Illuminate\Database\Eloquent\Model as Eloquent;

	class Chat extends Eloquent {

		protected $table = 'chats';
		protected  $fillable = [
			'id',
			'pool_id',
			'motd',
			'active',
		];

		public $with = [
		    'messages'
        ];

		public function pool()
		{
			return $this->belongsTo('App\Models\Pool');
		}

		public function messages()
		{
			return $this->hasMany('App\Models\ChatMessage','chat_id');
		}

	}


