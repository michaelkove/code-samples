<?php
    namespace App\Http\Controllers\PspFrontend;
    use App\Helpers\NotificationHelper;
    use App\Helpers\SiteHelper;
    use App\Helpers\UserNotificationHelper;
    use App\Http\Controllers\Controller;
    use App\Models\UserNotification;
    use \App\Providers\Pool\Schwindy\Schwindy as SchwindyProvider;
    use \App\Providers\User\Notification as NotificationProvider;
    use http\Env\Request;
    use Illuminate\Support\Facades\Auth;

    class UserNotificationController extends Controller {

        private $_userId;
        private $_schwindyPick;
        private $_pool;
        private $_schwindy;
        private $_playAsUser;
        private $_schwindyProvider;
        private $_userNotificationProvider;
        public $user;

        public function __construct(
            SchwindyProvider $schwindyProvider,
            NotificationProvider $userNotificationProvider
        ) {
            $this->_schwindyProvider = $schwindyProvider;
            $this->_userNotificationProvider = $userNotificationProvider;
        }


        public function index(){
            $this->_load_user();

            $notifications = $this->_userNotificationProvider->get(null, ['user_id' => $this->user->id],true)->with(['pool'])->orderBy('id', 'desc')->paginate(30);
            SiteHelper::set_help($this->help_title, $this->help_content, 'show', 'notifications');
            $help_title = $this->help_title;
            $help_content = $this->help_content;

            return view('pspfrontend.user.notification.index', compact('notifications','help_title','help_content'));
        }

        // public function create(){
        //     return null;
        // }

        // public function edit(UserNotification $userNotification){
        //     return null;
        // }

        // public function show(UserNotification $userNotification){
        //     return null;
        // }

        // public function store(Request $request){
        //     return null;
        // }

        // public function delete(UserNotification $userNotification){
        //     return null;
        // }

        // public function update(UserNotification $userNotification, Request $request){
        //     return null;
        // }

        public function count(){

            $this->_load_user();
            $notifications = $this->_userNotificationProvider->get(null, ['user_id' => $this->user->id],true)->with(['pool','user'])->orderBy('id', 'desc')->take(8)->get();
            $unread =  UserNotificationHelper::unread_count($this->user->id);
            return response()->json([
				'unread' => $unread,
	            'notifications' => $notifications
            ]);

        }

        public function mark_read(){
            $this->_load_user();
            $notifications = $this->_userNotificationProvider->get(null, ['user_id' => $this->user->id, 'read' => false]);
            foreach($notifications as $notification){
                $this->_userNotificationProvider->update($notification->id, ['read' => true]);
            }
            return $this->count();
        }

        private function _load_user(){
            $this->user = Auth::user();
        }

    }
