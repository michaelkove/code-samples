<?php

namespace App\Repositories;

use App\Models\Golf;

class GolfRepository
{
    public function __construct(Golf $model)
    {
        $this->model = $model;
    }

    public function find($id)
    {
        return $this->model::find($id);
    }

    public function get($filters = [], $queryOnly = false)
    {
        $golfPools = $this->model::where('id', '<>', ''); //calling static on non-static
        //            if($filters){
        foreach ($filters as $key => $filter) {
            $golfPools = $golfPools->where($key, $filter);
        }
        //            }
        return ($queryOnly) ? $golfPools : $golfPools->get();
    }

    public function create($data)
    {
        try {
            $ct = $this->model->create($data);
            return $ct;
        } catch (\Exception $e) {
            \Log::error($e);
        }
        return null;
    }

    public function update($id, $data)
    {
        $item = $this->find($id);

        try {
            $item->update($data);
        } catch (\Exception $e) {
            \Log::error($e);
        }
        return $item;
    }

}
