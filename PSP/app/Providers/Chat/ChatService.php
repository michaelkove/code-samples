<?php
	/**
	 * Created by PhpStorm.
	 * User: mkova
	 * Date: 7/22/2018
	 * Time: 1:09 PM
	 */

	namespace App\Providers\Chat;

    use App\Events\MessageSentEvent;
    use App\Events\ChatEvent;
    use App\Models\ChatMessage;
    use Illuminate\Http\Request;
	use App\Models\Pool;
	use App\Models\Schwindy;
	use App\Models\SchwindyPick;
    use Illuminate\Support\Facades\Auth;

    class ChatService {

        private $_chatRepo;
        public function __construct(
            \App\Repositories\ChatRepository $chatRepo
        )
        {
            $this->_chatRepo = $chatRepo;
        }

	    public function send_admin($pool, $message){
	        $chat = $pool->chat;
            $msg = \App\Models\ChatMessage::create([
                'chat_id' => $pool->chat->id,
                'user_id' => null,
                'message' => $message,
                'is_commissioner' => false,
                'visible' => true,
                'reported' => false,
                'system' => true,
                'link' => '#',
                'chat_message_type_id' => 12,
                'sent_at' => date('Y-m-d H:i:s'),
            ]);

            self::_push(null, $chat, $msg);
//            self::_mark_all_unread($chat->pool, $msg);
            return $msg;
            return true;
        }

		public static function send($chat, $userId = null, $message, $commissionerId = false, $messageTypeId = null, $displayName = "System")
		{
			$msg = \App\Models\ChatMessage::create([
				'chat_id' => $chat->id,
				'user_id' => ($userId) ? $userId : null,
				'message' => $message,
				'is_commissioner' => ($commissionerId) ? true : false,
				'visible' => true,
				'reported' => false,
				'system' => ($userId) ? false : true,
				'link' => ' ',
				'chat_message_type_id' => $messageTypeId,
				'sent_at' => date('Y-m-d H:i:s'),
                'display_name' => $displayName
			]);
            $user = Auth::user();
			self::_push($user, $chat, $msg);
//            self::_mark_all_unread($chat->pool, $msg);
			return $msg;

		}

		public static function broadcast($message, $poolId = null)
        {
            $chats = \App\Models\Chat::where('active', true);
            if($poolId) {
                $chats = $chats->where('pool_id', $poolId);
            }
            $chats = $chats->get();

            foreach($chats as $chat)
            {
               self::send($chat, null, $message, false, 11);
            }
            return true;
        }

		public function create($pool, $motd = null) {

            if($pool){
                $motd = ($motd) ?? "Welcome to pool";
                $chatData  =[
                    'pool_id' => $pool->id,
                    'active' => true,
                    'motd' => $motd,
                ];
                return $this->_chatRepo->create($chatData);
            }
            return null;

		}

		public static function load_chat($poolId)
        {
            return \App\Models\Chat::with(['messages','messages.type', 'message.user'])->where('pool_id', $poolId)->first();
        }

        public static function get_by_user_id($id, $userId, $limit = 100){
            $chat = \App\Models\Chat::whereHas('pool.users', function($query) use ($userId){
                $query->where('users.id', $userId);
            })->with(['messages' => function($query) use ($limit){
                $query->orderBy('id', 'desc')->take($limit)->get();
            }, 'pool.users'])->find($id);
            return $chat;
        }

        public static function clear_unread(&$chat, &$user){
            $lastMessage = $chat->messages->last();
            $poolUser = $chat->pool->users->where('id', $user->id)->first();
            $poolUser->pivot->last_chat_message_id = $lastMessage->id;
            $poolUser->pivot->save();
            return true;
        }

		private static function _push($user, $chat, $message)
		{
		    try {
                broadcast(new MessageSentEvent($user, $message, $chat))->toOthers();
            } catch (\Exception $e){
		        \Log::error("Broadacst error ".$e->getMessage());
            }
            return true;

//			$event = event(new \App\Events\ChatEvent($chatId, $userId, $message));
		}

		public static function update_last_read_id(&$chat, $user, $lastId = null){
            $poolServiceProvider = resolve(\App\Providers\Pool\Pool::class);
            $pool = $chat->pool;
            if($lastId){
                $data = [];
                $data[$user->id] = [
                    'last_chat_message_id' => $lastId
                ];
                $poolServiceProvider->update_user($pool, $data);
                return true;
            }
            return false;
        }

		private static function _mark_all_unread(&$pool, &$message){
	        foreach($pool->users as $user){
	            if($user->id != $message->user_id){
	                $curId = $user->pivot->last_chat_message_id;
	                 //ONLY if user has read his messages, (set to null) do not update
	                if(!$curId){
                        $user->pivot->last_chat_message_id = $message->id;
                        $save = $user->pivot->save();
                    }

                }
            }
        }


	}
