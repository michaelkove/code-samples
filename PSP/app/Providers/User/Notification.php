<?php
    /**
     * Created by PhpStorm.
     * User: mkova
     * Date: 10/12/2018
     * Time: 10:43 AM
     */

    namespace App\Providers\User;


    use App\Events\SendNotice;
    use App\Repositories\UserNotificationRepository;

    class Notification {

        private $_notificationRepo;

        public function __construct(
            UserNotificationRepository $notificationRepo
        ) {
            $this->_notificationRepo = $notificationRepo;
        }


        public function create($data){
            return $this->_notificationRepo->create($data);
        }

        public function update($id, $data){
            return $this->_notificationRepo->update($id, $data);
        }

        public function get($id = null, $filters = [], $queryOnly = false){
            return $this->_notificationRepo->get($id, $filters, $queryOnly);
        }

        public function get_notices_by_user_id($userId, $paginate = null){
            $notices = $this->get(null, ['user_id' => $userId], true);
            if($paginate){
                return $notices->paginate($paginate);
            }
            return $notices->get();
        }

        public function dismiss($id){
            $updateData = [
                'read' => true,
                'dismissed' => true,
            ];
            $this->_notificationRepo->update($id, $updateData);
        }

        public function mark_read($id){
            $updateData = [
                'read' => true,
                'read_at' => date('Y-m-d H:i:s'),
            ];
            $this->_notificationRepo->update($id, $updateData);
        }

        public function send_notice($userId, $notification = "", $poolId = null, $action = "viewed", $actionName = "", $commissionerId = null, $type = 'user', $level = 'info', $push = false, $url = null){

            if(!$url){
                $url = ($poolId) ? route('psp.pool.show', ['pool' => $poolId]) : route('psp.user.pools');
            }

            $createData = [
                'commissioner_id' => ($commissionerId) ? $commissionerId : null,
                'pool_id' => ($poolId) ? $poolId : null,
                'user_id' => ($userId) ? $userId : null,
                'type' => $type,
                'level' => $level,
                'action' => $action,
                'action_name' => $actionName,
                'notification' => $notification,
                'push' => $push,
                'read' => false,
                'dismissed' => false,
                'read_at' => null,
                'url' => $url,
            ];
      //      \Log::info($createData);

            $userNotice = $this->_notificationRepo->create($createData);
            if($push){
                $this->_push($userNotice);
            }

        }

        private function _push($userNotice){
            try {
                broadcast(new SendNotice($userNotice))->toOthers();
            } catch (\Exception $e){
                \Log::error("Broadacst error ".$e->getMessage());
            }
        }

    }
