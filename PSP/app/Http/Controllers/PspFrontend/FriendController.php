<?php
	namespace app\Http\Controllers\PspFrontend;

use App\Models\User;
use App\Models\Friend;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use DB;

class FriendController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $friends = [];
        $groups = [];
        $query = '';

        try {

            $user_id = auth()->user()->id;
            // get friends from user_friends
            $friendIds = Friend::where('user_id', $user_id)->where('blocked', 0)->where('removed', 0)->pluck('friend_user_id');
            $friends = User::whereIn('id', $friendIds)->with(["groups" => function ($query) {
                $user_id = auth()->user()->id;
                $query->where('creater_id', $user_id);
            }])->get();

            $groups = Group::where('creater_id', $user_id)->get();

            $query = $request->get('query');
            if ($query) {
                $friends = $friends->where(function ($subQuery) use ($query) {
                    $subQuery
                        ->where('username', 'like', $query . '%')
                        ->orWhere('email', 'like', $query . '%');
                });
            }
        } catch (\Exception $e) {
            \Log::error($e);
        }

        return view('pspfrontend.user.friends', [
            'users' => $friends,
            'groups' => $groups,
            'query' => $query
        ]);
    }

    public function group_add(Request $request)
    {
        $name = $request->name;
        $color = $request->color;
		$bgColor = $request->bgColor;
        if (!$name) {
            return response()->json(
                [
                    'group' => null,
                    'error' => false,
                    'message' => ['type' => 'success', 'message' => 'Group name is empty']
                ]
            );
        }
        if (!$color) {
            $color = '#fff';
        }
		if(!$bgColor){
			$bgColor = '#000';
		}

        try {
            $user_id = auth()->user()->id;
            $group = Group::create([
                'name' => $name,
                'color' => $color,
				'bg_color' => $bgColor,
                'creater_id' => $user_id
            ]);
            return response()->json(
                [
                    'group' => $group,
                    'error' => false,
                    'message' => ['type' => 'success', 'message' => 'Players successfully added to group ' . $group->name]
                ]
            );
        } catch (\Exception $e) {
            \Log::error($e);
			
        }

        return response()->json(
            [
                'group' => [],
                'error' => true,
                'message' => ['type' => 'success', 'message' => 'Add group is failed: '.$e->getMessage()]
            ]
        );
    }

    public function group_update_bulk(Request $request)
    {
        $type = $request->type;
        $groups = $request->groups;
        $users = $request->users;

        if (!$type || !$users || ($type != 'delete' && !$groups)) {
            return response()->json(
                [
                    'group' => null,
                    'error' => false,
                    'message' => ['type' => 'success', 'message' => 'Please select required fields']
                ]
            );
        }

        try {
            $users = explode(",", $users);
            for ($i = 0; $i < count($users); $i++) {
                $user_id = $users[$i];
                $user = User::find($user_id);
                if ($user) {
                    if ($type == 'delete') {
                        $user->groups()->detach();
                        $this->delete_friend($user->id);
                    } else {
                        for ($j = 0; $j < count($groups); $j++) {
                            $group_id = $groups[$j]['value'];
                            if ($type == 'add') {
                                $user->groups()->detach($group_id);
                                $user->groups()->attach($group_id);
                            } else if ($type == 'remove') {
                                $user->groups()->detach($group_id);
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return response()->json(
            [
                'group' => '',
                'error' => false,
                'message' => ['type' => 'success', 'message' => 'success']
            ]
        );
    }

    public function delete_friend($user_id)
    {
        try {
            $commissioner = auth()->user();
            Friend::where('user_id', $commissioner->id)->where('friend_user_id', $user_id)->update(array('removed' => 1));
        } catch (\Throwable $th) {
            // var_dump($th);
        }
        return;
    }


    public function add_friend($pool, Request $request)
    {
        try {
            $commissioner_id = $pool->commissioner_id;
            $friend = auth()->user();
            $accept_friend = $request->accept_friend;

            $removed = $accept_friend == 'true' ? 1 : 0;

            Friend::updateOrCreate(
                [
                    'user_id' => $commissioner_id,
                    'friend_user_id' => $friend->id
                ],
                [
                    'name' => $friend->global_display_name ? $friend->global_display_name : $friend->email,
                    'email' => $friend->email,
                    'note' => 'invited on pool ' . $pool->pool_name,
                    'auto' => 1,
                    'blocked' => 0,
                    'lists' => ' ',
                    'removed' => $removed
                ]
            );
            return $pool->type;
        } catch (\Throwable $th) {
            return 0;
        }

    }
}
