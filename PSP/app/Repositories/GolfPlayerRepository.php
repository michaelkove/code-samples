<?php

namespace App\Repositories;

use App\Models\GolfPlayer;
use App\Mail\AdminNotice;
use App\Models\User;

class GolfPlayerRepository
{
    public function __construct(GolfPlayer $model)
    {
        $this->model = $model;
    }

    public function find($id)
    {
        return $this->model::find($id);
    }

    public function get($filters = [], $queryOnly = false)
    {
        $items = $this->model::where('id', '<>', ''); //calling static on non-static
        //            if($filters){
        foreach ($filters as $key => $filter) {
            $items = $items->where($key, $filter);
        }
        //            }
        return ($queryOnly) ? $items : $items->get();
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        return $this->model->where('id', $id)->update($data);
    }
}
