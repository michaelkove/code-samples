<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Model as Eloquent;

    class ChatMessageType extends Eloquent {


        protected  $fillable = [
            'id',
            'label',
            'description',
            'created_at',
            'updated_at'
        ];

        public function chat_message()
        {
            return $this->belongsTo('App\Models\ChatMessage','chat_message_type_id');
        }

    }



