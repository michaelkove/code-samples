<?php

/**
 * Created by PhpStorm.
 * User: mkova
 * Date: 10/12/2018
 * Time: 10:43 AM
 */

namespace App\Providers\User;


use App\Providers\Pool\Pool;
use Carbon\Carbon;

class User
{

    private $_userRepo;

    public function __construct(
        \App\Repositories\UserRepository $userRepo
    ) {
        $this->_userRepo = $userRepo;
    }

    public function find($userId)
    {
        return $this->_userRepo->find($userId);
    }

    public function create($data = [])
    {
        return $this->_userRepo->create($data);
    }

    public function get($filters = [], $queryOnly = false)
    {
        return $this->_userRepo->get($filters, $queryOnly);
    }

    public function user_exists($email, $username)
    {
        return $this->_userRepo->user_exists($email, $username);
    }

    public function find_by_verify_hash($verifyHash)
    {
        return $this->_userRepo->get(['verify_hash' => $verifyHash], true)->first();
    }

    public function verify($user)
    {
        $userData = [
            'verified' => true
        ];
        return $this->_userRepo->update($user->id, $userData);
    }

    public function get_by_email($email = "")
    {
        return $this->_userRepo->get_by_email($email);
    }

    public function get_current_user()
    {
        return $this->get_user($this->_userRepo - authenticated());
    }


    public function get_user($user)
    {
        return $this->_userRepo->find($user->id);
    }

    public function search($term, $take = null)
    {
        return $this->_userRepo->search($term, $take);
    }

    public function generate_tfa()
    {
        return rand(100000, 999999);
    }

    public function verify_by_2fa($user, $verifyHash, $fourDigitCode)
    {
        $verify_expire_hours = __conf('system.verify_expire_hours', 'text', '12');
        $startTime = Carbon::parse($user->verify_expire_time)->addHours($verify_expire_hours);
        $endTime = Carbon::now();
        $expired =  $startTime->diff($endTime)->invert;

        $verify_attempt_count = __conf('system.verify_attempt_count', 'text', '3');

        if($user->status == 'disabled') {
            return 'LOCKED';
        }

        if ($expired == 0) {
            return 'EXPIRED';
        } else if ($user->verify_attempt_count >= $verify_attempt_count) {
            return 'EXCEED_ATTEMPTED';
        }

        if ($user->verify_hash == $verifyHash && $user->tfa_code == $fourDigitCode) {
            $this->verify($user);
            return 'VERIFIED';
        }
        return 'FAILED';
    }

    public function redirect_user_on_sign_in()
    {
        if ($to = session()->get('redirectTo')) {
            session()->forget('redirectTo');
            return redirect($to);
        }

        $hash = session()->get('joinPoolHash');
        if ($hash) {
            session()->forget('joinPoolHash');
            return redirect()->route('psp.pool.guest_show', ['hash' => $hash]);
        }
        if (session()->get('url.intended')) {
            return redirect()->intended();
        }
        return redirect()->route('psp.user.pools');
    }
}
