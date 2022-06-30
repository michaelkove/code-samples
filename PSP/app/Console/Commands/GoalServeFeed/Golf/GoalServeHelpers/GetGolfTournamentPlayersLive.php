<?php

namespace App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers;

use App\Helpers\GolfHelper;
use App\Helpers\GolfHelper as GH;
use App\Helpers\SiteHelper;
use App\Mail\AdminNotice;
use App\Providers\Pool\Golf\GolfTournamentProvider;
use App\Providers\Pool\Golf\GolfPlayerProvider;
use App\Providers\Pool\Golf\GolfTournamentPlayerProvider;
use App\Providers\Pool\Golf\GolfTournamentPlayerRoundProvider;
use App\Providers\Pool\Golf\GolfTournamentPlayerRoundHoleProvider;
use App\Helpers\UserNotificationHelper;

class GetGolfTournamentPlayersLive
{

    private $_golfHelper;
    private $_golfTournamentProvider;
	private $_golfTournamentPlayerProvider;
    private $_golfPlayerProvider;
    private $_golfTournamentPlayerRoundProvider;
    private $_golfTournamentPlayerRoundHoleProvider;
    private $_localGolfPlayers;
    private $_localTournamentPlayers;
    private $_localTournament;
    private $_gsTournament;
    private $_par;
	private $_reset;
    private $_newWithdrawnPlayers;
    private $_start;
	private $_statusMismatch;
	private $_message;
	private $_notifyAdminFlag;
	private $_force;
	private $_clean;
	
    /**
     * GetGolfLive constructor.
     * @param GH $gh
     * @param GolfTournamentPlayerProvider $gtpp
     * @param GolfPlayerProvider $gpp
     * @param GolfTournamentProvider $gtp
     * @param GolfTournamentPlayerRoundProvider $gtprp
     * @param GolfTournamentPlayerRoundHoleProvider $gtprhp
     */
    public function __construct(
        GH $gh,
        GolfTournamentPlayerProvider $gtpp,
        GolfPlayerProvider $gpp,
        GolfTournamentProvider $gtp,
        GolfTournamentPlayerRoundProvider $gtprp,
        GolfTournamentPlayerRoundHoleProvider $gtprhp

    ) {
        $this->_golfHelper = $gh;
        $this->_golfPlayerProvider = $gpp;
        $this->_golfTournamentProvider = $gtp;
        $this->_golfTournamentPlayerProvider = $gtpp;
        $this->_golfTournamentPlayerRoundProvider = $gtprp;
        $this->_golfTournamentPlayerRoundHoleProvider = $gtprhp;
        $this->_start = microtime(true);
        $this->_newWithdrawnPlayers = [];
		$this->_statusMismatch=[];
		$this->_notifyAdminFlag = false;
		$this->_message = "";
		$this->_reset = __conf('golf.goalserve.reset_players_status_to_feed', 'boolean', "0");
		$this->_clean =  __conf('golf.clean_golfer', 'boolean', "0");
		$this->_force = ($this->_clean) ?? __conf('golf.play_force_replay_all_rounds', 'boolean', 1);
    }

//$notifyMessage = "<strong>".$item['player_name']."</strong> GSID: <em style='color:red;'>".$item['player_id']."</em>";
//$type = "NEW GOLFER";
//$this->_add_message($notifyMessage,$type);
    /**
     * @return bool
     */
    public function get_golf_tournament_players()
    {
		if($this->_reset){
			$this->_notifyAdminFlag = true;
			$notifyMessage = "<strong>RESET SET</strong>";
			$type = "ADMIN FLAG";
			$this->_add_message($notifyMessage,$type);
		}
	    if($this->_clean){
		    $this->_notifyAdminFlag = true;
		    $notifyMessage = "<strong>GOLFERS CLEANED UP</strong>";
		    $type = "ADMIN FLAG";
		    $this->_add_message($notifyMessage,$type);
	    }
	 
        $returnValue = $this->_get_golf_tournament_players();
	    if($this->_reset){
		    __set_conf('golf.goalserve.reset_players_status_to_feed','0');
		    $notifyMessage = "<strong>RESET UNSET</strong>";
		    $type = "ADMIN FLAG";
		    $this->_add_message($notifyMessage,$type);
	    }
	    if($this->_clean){
			__set_conf('clean_golfer', '0');
		    $this->_notifyAdminFlag = true;
		    $notifyMessage = "<strong>GOLFERS CLEANED FLAG OFF</strong>";
		    $type = "ADMIN FLAG";
		    $this->_add_message($notifyMessage,$type);
	    }
		$this->_notify_admin();
		return $returnValue;
    }

    private function _get_golf_tournament_players()
    {
	    
        $this->_load_local_data();
        $addedPlayersOnLive = [];
        if ($this->_localTournament && $this->_gsTournament) {
            $count = count($this->_gsTournament);
            $i = 0;
            foreach ($this->_gsTournament as $item) {
                try {
                    $i++;
                    $player = $this->_localGolfPlayers->where('external_id', $item['player_id'])->first();
                    
                    if (!$player) {
	                    //Player did not exist, needs to be added to DB and created relationship
	                    //OK THIS IS RED FLAG BECAUSE GS SHOULD BE REUSING SAME PLAYERS LET'S DO THIS
	                    $this->_notifyAdminFlag = true; //set the flag
                        $player = $this->_golfPlayerProvider->create([
                            'name' => $item['player_name'],
                            'external_id' => $item['player_id'],
                            'country' => "-",
                            'pos' => 9999,
                            'avg_points' => 0,
                            'points_total' => 0,
                            'events' => 0,
                            'today' => $item['today'],
                            "internal_note" => "GET LIVE: Created on " . date('F-j-Y G:i:a') . " TID: " . $item['tournament_id'],
                            'order' => 0
                        ]);
                        $this->_localGolfPlayers->push($player);

                        $addedPlayersOnLive[] = [
                            'name' => $item['player_name'],
                            'external_id' => $item['player_id'],
                        ];
						$this->_notifyAdminFlag = true;
						$notifyMessage = "<strong>".$item['player_name']."</strong> GSID: <em style='color:red;'>".$item['player_id']."</em>";
						$type = "NEW GOLFER";
                        $this->_add_message($notifyMessage,$type);

                    }
                    if ($player && $this->_localTournament) {
                        $tournamentPlayer = $this->_process_cur_tourney($item, $player);
                    }
                } catch (\Exception $e) {
                    \Log::error($e);
                }
            }
	        $this->_golfTournamentPlayerProvider->play_prizes($this->_localTournament->tournament_players, $this->_localTournament->prizes);
        }

        if (count($this->_newWithdrawnPlayers)) {
            $this->_golfTournamentProvider->notify_withdrawn_players($this->_localTournament, $this->_newWithdrawnPlayers);
        }
        return true;
    }

    private function _load_local_data()
    {
        $this->_gsTournament = $this->_golfHelper->getCurrentTournamentAsCollection();
        $updatingPlayerIds = [];
      
        foreach ($this->_gsTournament as $item) {
            try {
                $updatingPlayerIds[] = intval($item['player_id']);
              
            } catch (\Exception $e) {
				\Log::error($e);
            }
        }
        $this->_localGolfPlayers = $this->_golfPlayerProvider->get([], true)->whereIn('external_id', $updatingPlayerIds)->get();
	    $this->_localTournament = $this->_golfTournamentProvider->get_current();

		
        if ($this->_localTournament) {
            try {
                $this->_localTournament->load(['tournament_players', 'tournament_players.completed_rounds']);
            } catch (\Exception $e) {
                \Log::error($e);
            }

            $this->_par = ($this->_localTournament && isset($this->_localTournament->par)) ? intval($this->_localTournament->par) : 0;
            $localPlayersId = [];
            foreach ($this->_localGolfPlayers as $gp) {
                $localPlayersId[] = $gp->id;
            }
            $this->_localTournamentPlayers =  $this->_get_local_tournament_players();//  ($this->_localTournament) ? $this->_localTournament->tournament_players : collect([]);
            return true;
        }
        return false;
    }
	
	private function _get_local_tournament_players(){
		$localTournamentPlayers = $this->_golfTournamentPlayerProvider->get(['tournament_id' => $this->_localTournament->id], true)->with([
			'player',
			'rounds',
			'rounds.holes'
		])->get();
		return $localTournamentPlayers;
	}

    private function _flag_withdrawn($updatingPlayerIds, &$tournament)
    {
        $allGTPs = $tournament->tournament_players;
        foreach ($allGTPs as $gtp) {
            $exId = $gtp->player->external_id;
            if (!in_array($exId, $updatingPlayerIds)) {
	            $this->_golfTournamentPlayerProvider->set_status($localTournamentPlayer, 'WD');
            }
            $localIds[$gtp->player->external_id] = $gtp->player->external_id;
        }
    }

    private function _process_cur_tourney(&$item, &$player)
    {
        try {
            $useHeader = SiteHelper::conf('golf.goalserve.live_use_header_data', 'boolean', 1);
            $tournamentPlayer = $this->_localTournamentPlayers->where('player_id', $player->id)->first();
            $teeTime = null;
            $scoreToPar = (isset($item['score_to_par'])) ? $this->_get_par($item['score_to_par']) : 0;
            $today = isset($item['today']) ? $item['today'] : 0;
            $par = (isset($item['par'])) ? $this->_get_par($item['par']) : 0;
         
			
            if ($today === "CUT")
                $par = "CUT";
        
			
            if (isset($item['tee_time'])) {
                $teeTime = $item['tee_time'];
            }


            if (!$tournamentPlayer) {
				
                $tPlayerData = [
                    'player_id' => $player->id,
                    'tournament_id' => $this->_localTournament->id,
                    //                        'pos' =>  intval($item['pos']),
                    'par' => $par,
                    'score_to_par' => $scoreToPar,
                    'drive_dist_avg' => (isset($item['drive_dist_avg'])) ? $item['drive_dist_avg'] : 0,
                    'drive_accuracy_pct' => (isset($item['drive_accuracy_pct'])) ? $item['drive_accuracy_pct'] : 0,
                    'gir' => (isset($item['gir'])) ? $item['gir'] : 0,
                    'putts_gir_avg' => (isset($item['putts_gir_avg'])) ? $item['putts_gir_avg'] : 0,
                    'saves' => (isset($item['saves'])) ? $item['saves'] : 0,
                    'eagles' => (isset($item['eagles'])) ? $item['eagles'] : 0,
                    'birdies' => (isset($item['birdies'])) ? $item['birdies'] : 0,
                    'pars' => ($par !== 'CUT') ? $item['pars'] : 0,
                    'bogeys' => (isset($item['bogeys'])) ? $item['bogeys'] : 0,
                    'doubles' => (isset($item['doubles'])) ? $item['doubles'] : 0,
                    //                        'live_total' => null,
                    //                        'live_today' => null,
                    'winner' => (isset($item['winner'])) ? $item['winner'] : 0,
                    'status' => $this->_check_player_status($par),
                    'rank' => $player->pos,
                ];
             
                $tPlayerData['hole'] = (isset($item['hole'])) ? $item['hole'] : 0;
                if ($teeTime && $teeTime !== "" && $teeTime !== null) {
                    $tPlayerData['tee_time'] = $teeTime;
                }
                $tournamentPlayer = $this->_golfTournamentPlayerProvider->create($tPlayerData);
                $tournamentPlayer = $this->_golfTournamentPlayerProvider->set_soft_status($tournamentPlayer, $this->_check_player_status($par));
                $this->_localTournamentPlayers->push($tournamentPlayer);
				$this->_notifyAdminFlag = true;
	            $notifyMessage = "<strong>".$item['player_name']."</strong> GSID: <em style='color:red;'>".$item['player_id']."</em></strong> ID: <em style='color:green;'>".$player->id."</em>";
	            $type = "NEW TOURNAMENT PLAYER";
	            $this->_add_message($notifyMessage,$type);
            }
			else
			{
				if($this->_clean){
					$tPlayerUpdateData = [
							'round_1' => null,
							'round_2' => null,
							'round_3' => null,
							'round_4' => null,
							'total' => null,
							'round_completed' => null,
							'current_round' => null,
							'disqualified_round' => null,
							'percent' => null,
							'prize' => null,
							'winner' => null,
							'pos' => null,
							'par' => null,
							'score_to_par' => null,
							'hole' => null,
							'start_hole' => null,
							'today' => null,
							'live_total' => null,
							'live_today' => null,
						];
					$this->_notifyAdminFlag = true;
					$data = "<span style='color:red'>R1,R2,R3,R4,Total,Hole</span>";
					$set = "<strong style='color:green'>NULL</strong>";
					$player = "<strong>".$tournamentPlayer->player->name."</strong> GSID: <em>".$tournamentPlayer->player->external_id."</em>";
					$notifyMessage = $player." | ".$data." >> ".$set;
					$type = "CLEAN";
					$this->_add_message($notifyMessage,$type);
				}
				else {
					$hasRounds =  $tournamentPlayer->rounds->count();
					unset($item['player_id']);
					unset($item['tournament_id']);
					unset($item['player_name']);
					unset($item['po']);
					unset($item['id']);
					
					$tPlayerUpdateData = [
						'par' => $par,
						"drive_dist_avg" => $item['drive_dist_avg'],
						"drive_accuracy_pct" => $item['drive_accuracy_pct'],
						"gir" => $item['gir'],
						"putts_gir_avg" => $item['putts_gir_avg'],
						"saves" => $item['saves'],
						"eagles" => $item['eagles'],
						"birdies" => $item['birdies'],
						"pars" => $item['pars'],
						"bogeys" => $item['bogeys'],
						"doubles" => $item['doubles'],
						'pos' => ($item['pos'] && $item['pos'] !== '-') ? intval($item['pos']) : 9999,
					];
		
					if ($useHeader) {
						if($this->_localTournament->tournament_current_round != 0){
							$roundString = "round_".$this->_localTournament->tournament_current_round;
							$tPlayerUpdateData[$roundString] = $item['par'];
						}
						
						$tPlayerUpdateData['score_to_par'] = $scoreToPar;
						$tPlayerUpdateData['live_total'] = $this->_golfHelper::parse_scoring_to_par($item['total'], $par);
						$tPlayerUpdateData['live_today'] = $this->_golfHelper::parse_scoring_to_par($item['today']);
						$tPlayerUpdateData['total'] = $scoreToPar;
						$tPlayerUpdateData['today'] = $today;
						$tPlayerUpdateData['hole'] = $item['hole'];
						
					} else {
						if($item['hole'] === '-'){
							$tPlayerUpdateData['hole'] = $item['tee_time'];
						}
						if ($hasRounds === 0) {
							$tPlayerUpdateData['tee_time'] = $item['tee_time'];
						}
						$this->_play_player_by_rounds();
					}
					
					$status = $this->_check_player_status($par, $today);
					
					if($this->_reset){ //reset status if shit went apeshit
						$this->_notifyAdminFlag = true;
						$oldStatus = "<span style='color:red'>".$tournamentPlayer->status."</span>";
						$newStatus = "<strong style='color:green'>".$tournamentPlayer->status."</strong>";
						$player = "<strong>".$tournamentPlayer->player->name."</strong> GSID: <em>".$tournamentPlayer->player->external_id."</em>";
						$notifyMessage = $player." | ".$oldStatus." >> ".$newStatus;
						$type = "RESET STATUS UPDATE";
						$this->_add_message($notifyMessage,$type);
						$tPlayerUpdateData['status'] = $status;
					} else {
						//now process WD
						if ($wdId = $this->_get_withdrawn_id($status, $tournamentPlayer)) {
							$this->_notifyAdminFlag = true;
							$oldStatus = "<span style='color:red'>".$tournamentPlayer->status."</span>";
							$newStatus = "<strong style='color:green'>WD</strong>";
							$player = "<strong>".$tournamentPlayer->player->name."</strong> GSID: <em>".$tournamentPlayer->player->external_id."</em>";
							$notifyMessage = $player." | ".$oldStatus." >> ".$newStatus;
							$type = "WITHDRAWAL";
							$this->_add_message($notifyMessage,$type);
							$this->_newWithdrawnPlayers[] = $wdId;
						}
						
						$this->_golfTournamentPlayerProvider->set_status($tournamentPlayer, $status);
					}
					
				}
                
                $this->_golfTournamentPlayerProvider->update($tournamentPlayer->id, $tPlayerUpdateData);
				$tournamentPlayer->refresh();

                return $tournamentPlayer;
            }
        } catch (\Exception $e) {
            \Log::error($e);
        }
        return true;
    }

    private function _get_withdrawn_id($status, $tournamentPlayer)
    {
        //There is no point to renotify crons of Inactive players. Only if switching FROM active and it's not to Active.
        if ($status !== 'Active' && $tournamentPlayer->status === 'Active') {
            if ($status != $tournamentPlayer->status) {
                return $tournamentPlayer->id;
            }
        }
        return false;
    }

    private function _get_par($score)
    {
        $score = trim(strtoupper($score));
        if (is_numeric($score)) {
            return $score;
        }
        if ($this->_is_status_in_par($score)) {
            return $score;
        }
        return 0;
    }

    private function _check_player_status($score, $today = null, $reset = false)
    {
		if($today === 'WD'){
			return $today;
		}
        return ($this->_is_status_in_par($score)) ? $score : 'Active';
    }

    private function _is_status_in_par($score)
    {
        $score = strtoupper(strval(trim($score)));
        $statuses = ['DQ', 'WD', 'NS', 'NC', 'DNF', 'NR', 'CUT'];
        return (in_array($score, $statuses));
    }
	
	private function _play_player_by_rounds(){
		return true;
	}
	
	private function _add_message($message, $type = ""){
		$this->_message .= "<tr><td style='padding:20px;'>".$message."</td><td style='padding:20px;'>".$type."</td></tr>";
	}
	
	private function _notify_admin(){
		if($this->_notifyAdminFlag){
			$header = "<table style='width:100%;' border='1'>
						<thead>
						<tr>
							<th style='padding:20px;font-weight: bold;'>Message</th>
							<th style='padding:20px;font-weight:bold;'>Type</th>
						</tr>
						</thead><tbody>";
			$footer = "</tbody></table>";
			$subject = "CHANGES: LIVE RUN STATUS: " . $this->_localTournament->name;
			UserNotificationHelper::send_admin($subject, $header.$this->_message.$footer);
		}
		
	}
	
	
}
