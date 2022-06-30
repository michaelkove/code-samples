<?php
    namespace App\Providers\Pool\Golf;

    use App\Repositories\GolfPlayerRepository;

    class GolfPlayerProvider {

        private $_repo;

        public function __construct(
            GolfPlayerRepository $repo
        ) {
            $this->_repo = $repo;
        }

        public function find($id){
            return $this->_repo->find($id);
        }

        public function create($data){
            return $this->_repo->create($data);
        }

        public function update($id, $data){
            return $this->_repo->update($id, $data);
        }

        public function get($filters = [], $queryOnly = false){
            return $this->_repo->get($filters, $queryOnly);
        }

    }
