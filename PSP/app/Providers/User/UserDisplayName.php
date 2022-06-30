<?php
    namespace App\Providers\User;


    class UserDisplayName {

        private $_userRepo;

        public function __construct(
            \App\Repositories\UserDisplayNameRepository $userDisplayNameRepo
        )
        {
            $this->_userDisplayNameRepo = $userDisplayNameRepo;
        }



        public function get($id = null)
        {
            return $this->_userDisplayNameRepo->get($id);
        }

        public function create($data){
//            \Log::info("Creating");
//            \Log::info($data);
            return $this->_userDisplayNameRepo->create($data);
        }

        public function update($id, $data){
//            \Log::info("Updating");
//            \Log::info($data);
            return $this->_userDisplayNameRepo->update($id, $data);
        }

        public function delete($id){
            return $this->_userDisplayNameRepo->delete($id);
        }

        public function set_primary($id, &$displayNames){
            return $this->_userDisplayNameRepo->set_primary($id, $displayNames);
        }

        public function has_primary($userId){
            return ($this->_userDisplayNameRepo->get(null, ['user_id' => $userId, 'primary' => true], true)->count() > 0);
        }


    }
