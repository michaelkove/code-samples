<?php
	namespace App\Models;
	use Illuminate\Database\Eloquent\Model as Eloquent;

	class ChatMessage extends Eloquent {


        public $table = "chat_messages";
		protected  $fillable = [
			'chat_id',
			'user_id',
			'message',
			'is_commissioner',
			'visible',
			'reported',
			'system',
			'link',
			'sent_at',
            'chat_message_type_id',
            'display_name',
		];

		public $with = ['user', 'type'];

		public function chat()
		{
			return $this->belongsTo('App\Models\Chat','chat_id');
		}

		public function user()
		{
			return $this->belongsTo('App\Models\User','user_id');
		}

		public function type()
        {
            return $this->belongsTo('App\Models\ChatMessageType', 'chat_message_type_id');
        }

//        public function setMyUserIdAttribute($userId){
//		    $this->my_user_id = $userId;
//        }
//
//        public function getMyMessageAttribute(){
//		    return $this->user_id == $this->my_user_id;
//        }
	}


