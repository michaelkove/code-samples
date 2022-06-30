<?php

namespace App\Providers\Pool\Golf;

use App\Helpers\GolfHelper;
use App\Helpers\SiteHelper;
use App\Repositories\GolfGroupRepository;
use App\Repositories\GolfTournamentPlayerRepository;

class GolfGroupProvider
{

    private $_repo;
    private $_golfProvider;

    public function __construct(
        GolfGroupRepository $repo
    ) {
        $this->_repo = $repo;
    }

    public function find($id)
    {
        return $this->_repo->find($id);
    }

    public function create($data)
    {
        return $this->_repo->create($data);
    }

    public function update($id, $data)
    {
        return $this->_repo->update($id, $data);
    }

    public function get($filters = [], $queryOnly = false)
    {
        return $this->_repo->get($filters, $queryOnly);
    }

    public function sync_many(&$golf, $config)
    {
        $count = 0;
        foreach ($config->groups as $group) {
            if ($group->enabled) {
                $count++;
            }
        }
        foreach ($golf->entries as &$entry) {
            foreach ($entry->groups as $group) {
                $group->delete();
            }
            $this->create_many($count, ['golf_entry_id' => $entry->id]);
        }
    }

    public function create_many($num, $data = [])
    {
        $groups = [];
        for ($gn = 1; $gn <= $num; $gn++) {
            $groupData = [
                'golf_entry_id' => $data['golf_entry_id'],
                'number' => $gn,
                'name' => "Group " . GolfHelper::group_number_to_letter($gn),
                'total_players' => 0,
            ];
            $groups[] = $this->_repo->create($groupData);
        }
        return $groups;
    }
	
	public function replace_player($group, $removeId, $addId, $order = null){
		try{
			$idToAdd = [];
			$group->tournament_players()->detach($removeId);
			$idToAdd[$addId] = [
				'commissioned' => true,
				'order' => $order,
				'by_user_id' => \Auth::user()->id,
			];
			$group->tournament_players()->attach($idToAdd);
			return true;
		} catch(\Exception $e){
			\Log::error($e);
		}
		return false;
		
	}

    public function add_players(&$golf, &$group, &$players, $reset = false, $overrideRestrictions = [], $order = 1)
    {

        try {
            $overridePicksAllowed = (isset($overrideRestrictions['commissioner']) && $overrideRestrictions['commissioner'] === true) ?? false;
            $overrideMax = (isset($overrideRestrictions['max']) && $overrideRestrictions['max'] === true) ?? false;
            $overrideGroup = (isset($overrideRestrictions['group']) && $overrideRestrictions['group'] === true) ?? false;
            $overrideOtherGroup = (isset($overrideRestrictions['other_group']) && $overrideRestrictions['other_group'] === true) ?? false;
            $userId = \Auth::user()->id;
            $this->_golfProvider = resolve(GolfProvider::class);

            $picksAllowed = $this->_golfProvider->picks_allowed($golf, $overridePicksAllowed);
            $idsToSync = [];
            $max = $this->_golfProvider->get_group_max($golf->config, $group->number);
            if ($picksAllowed) {
                $syncMax = ($reset) ? $players->count() : $group->tournament_players->count();
                // Sync or Adding - IF reset and syncing up to max ALLOW || not reseting and sync is less than max
                $syncAndReset = (($syncMax <= $max) && $reset);
                $addPlayersNoReset = (!$reset && ($syncMax < $max));
                $proceedAllowed = ($syncAndReset || $addPlayersNoReset);
                if ($proceedAllowed || $overrideMax) {
                    foreach ($players as $tournamentPlayer) {
                        $groupedTournamentPlayer = $this->_golfProvider->get_grouped_player($golf, $tournamentPlayer->id);
                        //they are trying to add tournament player BUT he's not been grouped by comish
                        if ($groupedTournamentPlayer || $overrideGroup) { // OK this guy is in Tournament
                            $assignedGroupNumber = isset($groupedTournamentPlayer->pivot->group_number) ? $groupedTournamentPlayer->pivot->group_number : null;
                            if ($this->_allow_group_jump($assignedGroupNumber, $group->number, $golf->lower_to_higher) || $overrideOtherGroup) {
                                $idsToSync[$tournamentPlayer->id] = ['order' => $order, 'by_user_id' => $userId];
                            }
                        }
                    }
                }
                if (count($idsToSync)) {
                    $group->tournament_players()->sync($idsToSync, $reset);
                    $updateQuery = ['total_players' => $group->tournament_players()->count()];
                    $this->update($group->id, $updateQuery);
                    $group->refresh();
                }
            }
        } catch (\Exception $e) {
            \Log::error($e);
        }

        return $group;
    }

    public function remove_players(&$golf, &$group, &$players, $overrideRestrictions = [])
    {
        try {
            $overridePicksAllowed = (isset($overrideRestrictions['commissioner']) && $overrideRestrictions['commissioner'] === true) ?? false;
            $this->_golfProvider = resolve(GolfProvider::class);
            $picksAllowed = $this->_golfProvider->picks_allowed($golf, $overridePicksAllowed);
            $idsToDetach = [];
            if ($picksAllowed) {
                foreach ($players as $tournamentPlayer) {
                    $idsToDetach[] = $tournamentPlayer->id;
                }
                if (count($idsToDetach)) {
                    $group->tournament_players()->detach($idsToDetach);
                    $updateQuery = ['total_players' => $group->tournament_players()->count()];
                    $this->update($group->id, $updateQuery);
                    $group->refresh();
                }
            }
        } catch (\Exception $e) {
            \Log::error($e);
        }

        return $group;
    }

    private function _allow_group_jump($assignedGroupNumber = null, $targetGroupNumber = 1, $lowerToHigher = false)
    {
        $aG = intval($assignedGroupNumber);
        $tG = intval($targetGroupNumber);
        return (($aG === $tG) || (($aG > $tG) && $lowerToHigher));
    }
}
