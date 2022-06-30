<?php

/**
 * Created by PhpStorm.
 * User: mkova
 * Date: 10/12/2018
 * Time: 10:37 AM
 */

namespace App\Repositories;


class UserRepository
{
    public $model;

    public function __construct(\App\Models\User $model)
    {
        $this->model = $model;
    }

    public function authenticated()
    {
        return auth()->user();
    }

    public function find($id)
    {
        return $this->model::find($id);
    }

    public function get($filters = [], $queryOnly = false)
    {
        $users = $this->model::where('id', '<>', ''); //calling static on non-static
        if (count($filters)) {
            foreach ($filters as $key => $filter) {
                try {
                    $users = $users->where($key, $filter);
                } catch (\Exception $e) {
                    \Log::error($e);
                }
            }
        }

        return ($queryOnly) ? $users : $users->get();
    }

    public function user_exists($email, $username)
    {
        return ($this->model::where('email', $email)->orWhere('username', $username)->count() > 0);
    }

    public function get_by_email($email = null)
    {
        return $this->model::where('email', strtolower(trim($email)))->first();
    }

    public function create($data = [])
    {
        try {
            $ct = $this->model::create($data);
            return $ct;
        } catch (\Exception $e) {
            \Log::error($e);
        }
        return null;
    }

    public function findWith($id, $with = [])
    {
        return $this->model::with($with)->find($id);
    }

    public function delete($id)
    {
        return $this->model::find($id)->delete();
    }

    public function update($id, $data)
    {
        try {
            $up = $this->model::where('id', $id)->update($data);
            return $up;
        } catch (\Exception $e) {
            \Log::error($e);
            
        }
        return null;
    }

    public function updateMany($condition, $data)
    {
        try {
            $up = $this->model::where($condition)->update($data);
            return $up;
        } catch (\Exception $e) {
            \Log::error($e);
            
        }
        return null;
    }

    public function userSquare($id, $userId)
    {
        return $this->model::where('user_id', $userId)->find($id);
    }

    public function poolByType($id, $typeId)
    {
        return $this->model::where('type', $typeId)->find($id);
    }

    public function search($term, $take)
    {
        $users = $this->model::where('username', 'like', $term . '%')
            ->orWhere('email', 'like', $term . '%')
            ->orWhere('global_display_name', 'like', $term . '%');
        if ($take) {
            $users->take($take);
        }
        return $users->get();
    }
}
