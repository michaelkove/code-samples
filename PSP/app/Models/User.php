<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
	
	
	const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';
	
	protected $primaryKey = 'id';
	
	public $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */


    protected $fillable = [
        'name',
        'username',
        'first',
        'last',
        'global_display_name',
        'email',
        'phone',
        'is_admin',
        'is_test',
        'is_system',
        'status',
        'password',
        'timezone',
        'verified',
        'verify_hash',
        'tfa_code',
        'transactional_only',
        'verify_attempt_count',
        'rate_limit',
        'no_email',
	    'email_valid',
		'email_status',
		'email_sub_status',
		'validated_at',
		'email_firstname',
		'email_lastname',
		'email_gender',
		'email_country',
		'email_region',
		'email_city',
		'email_zipcode',
    ];


    /**
     *
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    //	public $with = [
    //	    'squares', 'schwindyPicks', 'display_names',
    //    ];

    public function squares($squareBoardId = null)
    {
        $queryBuilder = $this->hasMany('\App\Models\Square', 'user_id');
        return ($squareBoardId) ? $queryBuilder->where('squareboard_id', $squareBoardId) : $queryBuilder;
    }

    //	public function squares1()
    //    {
    //        return $this->belongsToMany('App\Models\Square','squares_users', 'user_id','square_id')->withPivot(['label','short_label','populated_by_user_id']);
    //    }

    public function is_admin()
    {
        return ($this->is_admin == 'yes');
    }
	
	public function friends_groups(){
		return $this->hasMany(Group::class, 'creater_id');
	}
	
	public function friends(){
		return $this->hasMany(Friend::class, 'user_id');
	}

    public function pools()
    {
        return $this->belongsToMany('\App\Models\Pool', 'pool_users', 'user_id', 'pool_id')->withPivot(['status', 'commissioner']);
    }

    public function groups()
    {
        return $this->belongsToMany('\App\Models\Group', 'user_groups', 'user_id', 'group_id');
    }

    public function is_commissioner($poolId)
    {
        return ($this->commissioned_pools()->where('pools.id', $poolId)->count() > 0);
    }

    public function commissioned_pools()
    {
        return $this->pools()->wherePivot('commissioner', true);
    }

    public function no_commissioned_pools()
    {
        return $this->pools()->wherePivot('commissioner', false);
    } 

    public function schwindys()
    {
        return $this->belongsToMany('\App\Models\Schwindy', 'schwindy_users', 'user_id', 'schwindy_id');
    }

    public function display_names()
    {
        return $this->hasMany('\App\Models\UserDisplayName');
    }

    public function schwindy_picks()
    {
        return $this->hasMany('\App\Models\SchwindyPick', 'user_id');
    }

    public function schwindyPicks($schwindyId = null)
    {
        if ($schwindyId) {
            return $this->hasMany('\App\Models\SchwindyPick', 'user_id')->where('schwindy_id', $schwindyId);
        } else {
            return $this->hasMany('\App\Models\SchwindyPick', 'user_id');
        }
    }

    public function golf_user_group_players()
    {
        return $this->hasMany(GolfUserGroupPlayer::class);
    }

    // GolfPool model is not exit  
    public function golf_pools()
    {
        return $this->hasMany(GolfPool::class, 'commissioner_id');
    }

    public function invites()
    {
        return $this->hasMany(GolfEmailInvite::class, 'pool_users_id');
    }

    // GolfPool model is not exit
    public function invited_pools()
    {
        return $this->hasManyThrough(GolfPool::class, GolfEmailInvite::class, 'pool_users_id', 'pool_id', 'id', 'id');
    }

    public function entries()
    {
        return $this->hasMany(GolfEntry::class);
    }

    public function getFallbackAttribute()
    {
        return ($this->global_display_name) ?? explode('@', $this->email)[0];
    }

    public function getDisplayNameAttribute()
    {
        return $this->global_display_name;
    }

    public function getVerifyUrlAttribute()
    {
        return ($this->verify_hash) ? route('psp.user.verify', ['hash' => $this->verify_hash]) : route('psp.login');
    }

    public function getVerifyFourDigitCodeAttribute()
    {
        return $this->tfa_code;
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function billings()
    {
        return $this->hasMany(UserBilling::class);
    }
}
