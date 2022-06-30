<?php
	
	namespace App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers;
	
	use App\Helpers\GolfHelper;
	use App\Helpers\GolfHelper as GH;
	use App\Helpers\UserNotificationHelper;
	use App\Hooks\System\CriticalErrorLogging;
	use App\Models\GolfTournament;
	use App\Providers\Pool\Golf\GolfTournamentProvider;
	use App\Providers\Pool\Golf\GolfPlayerProvider;
	use App\Providers\Pool\Golf\GolfTournamentPlayerProvider;
	use App\Providers\Pool\Golf\GolfTournamentPlayerRoundProvider;
	use App\Providers\Pool\Golf\GolfTournamentPlayerRoundHoleProvider;
	
	class GetGolfTournamentEntrants
	{
		
		private $_golfHelper;
		private $_golfTournamentProvider;
		private $_golfPlayerProvider;
		private $_golfTournamentPlayerProvider;
		private $_golfTournamentPlayerRoundProvider;
		private $_golfTournamentPlayerRoundHoleProvider;
		private $_localGolfPlayers;
		private $_localTournamentPlayers;
		private $_localTournament;
		private $_gsTournament;
		private $_gsRounds;
		private $_gsHoles;
		private $_message;
		private $_notifyAdminFlag;
		
		
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
			GH                                    $gh,
			GolfTournamentPlayerProvider          $gtpp,
			GolfPlayerProvider                    $gpp,
			GolfTournamentProvider                $gtp,
			GolfTournamentPlayerRoundProvider     $gtprp,
			GolfTournamentPlayerRoundHoleProvider $gtprhp,
			CriticalErrorLogging                  $criticalErrorLogging
		
		) {
			$this->_golfHelper = $gh;
			$this->_golfPlayerProvider = $gpp;
			$this->_golfTournamentProvider = $gtp;
			$this->_golfTournamentPlayerProvider = $gtpp;
			$this->_golfTournamentPlayerRoundProvider = $gtprp;
			$this->_golfTournamentPlayerRoundHoleProvider = $gtprhp;
			$this->_start = microtime(true);
			$this->_criticalLog = $criticalErrorLogging;
			$this->_notifyAdminFlag = false;
			$this->_message = "";
		}
		
		/**
		 * @return bool
		 */
		public function get_tournament_entrants($id = null)
		{
			try{
				$force = ($id) ? true : false;
				$localTournaments = $this->_golfTournamentProvider->get_playing($id);
				$this->_localTournament = $this->_golfTournamentProvider->get_current($id);
				$this->_get_tournament($force);
				$this->_notify_admin();
			} catch (\Exception $e){
				\Log::error($e);
			}
			
			
		}
		
		private function _get_tournament($force = false)
		{
			if ($this->_localTournament) {
				$gsId = $this->_golfHelper->reverse_external_id($this->_localTournament->external_id);
				$curTourney = $this->_golfHelper->getCurrentTournamentAsCollection($gsId);
				
				if ($curTourney) {
					$updatingPlayerIds = [];
					foreach ($curTourney as $tourney) {
						try {
							$updatingPlayerIds[] = intval($tourney['player_id']);
						} catch (\Exception $e) {
							\Log::error($e);
						}
					}
					
					
					$players = $this->_get_local_players($updatingPlayerIds);
					foreach ($curTourney as $item) {
						try {
							$player = $players->where('external_id', $item['player_id'])->first();
						
							if (!$player) {
								try{
									$player = $this->_golfPlayerProvider->create([
										'name' => $item['player_name'],
										'external_id' => $item['player_id'],
										'country' => "-",
										'pos' => 9999,
										'avg_points' => 0,
										'points_total' => 0,
										'events' => 0,
										"internal_note" => "GET TOURNEY: Created on " . date('F-j-Y G:i:a') . " TID: " . $item['tournament_id'],
										'order' => 0
									]);
									$this->_notifyAdminFlag = true;
									$notifyMessage = "<strong>".$item['player_name']."</strong> GSID: <em style='color:red;'>".$item['player_id']."</em>";
									$type = "NEW GOLFER";
									$this->_add_message($notifyMessage,$type);
								} catch (\Exception $e){
									\Log::error($e);
								}
							}
							
							if ($player) {
								$this->_process_entrants($item, $player, $force);
							}
						} catch (\Exception $e) {
							\Log::error($e);
						}
					}
				}
			}
			return null;
	
		}
		
		private function _get_local_players($arrayIds = [])
		{
			return $this->_golfPlayerProvider->get([], true)
			                                 ->whereIn('external_id', $arrayIds)
			                                 ->get();
		}
		
		private function _process_entrants(&$tourney, &$player, $force = false)
		{
			try {
				if ($this->_localTournament->not_started || $force) { //DO NOT RUN RUNING TOURNEYS
					$tournamentPlayer = $this->_golfTournamentPlayerProvider->get([
						'player_id' => $player->id,
						'tournament_id' => $this->_localTournament->id,
					], true)->first();
					
					$teeTime = null;
		
					$scoreToPar = (isset($tourney['score_to_par'])) ? $tourney['score_to_par'] : 0;
					if (!$tournamentPlayer) {
						$teeTime = $tourney['tee_time'];
						$tPlayerData = [
							'player_id' => $player->id,
							'tournament_id' => $this->_localTournament->id,
							'pos' =>  intval($tourney['pos']),
							'par' => (isset($tourney['par'])) ? $tourney['par'] : 0,
							'hole' => (isset($tourney['hole'])) ? $tourney['hole'] : 0,
							'score_to_par' => $scoreToPar,
							'drive_dist_avg' => (isset($tourney['drive_dist_avg'])) ? $tourney['drive_dist_avg'] : 0,
							'drive_accuracy_pct' => (isset($tourney['drive_accuracy_pct'])) ? $tourney['drive_accuracy_pct'] : 0,
							'gir' => (isset($tourney['gir'])) ? $tourney['gir'] : 0,
							'putts_gir_avg' => (isset($tourney['putts_gir_avg'])) ? $tourney['putts_gir_avg'] : 0,
							'saves' => (isset($tourney['saves'])) ? $tourney['saves'] : 0,
							'eagles' => (isset($tourney['eagles'])) ? $tourney['eagles'] : 0,
							'birdies' => (isset($tourney['birdies'])) ? $tourney['birdies'] : 0,
							'pars' => (isset($tourney['pars'])) ? $tourney['pars'] : 0,
							'bogeys' => (isset($tourney['bogeys'])) ? $tourney['bogeys'] : 0,
							'doubles' => (isset($tourney['doubles'])) ? $tourney['doubles'] : 0,
							'live_total' => null,
							'live_today' => null,
							'winner' => (isset($tourney['winner'])) ? $tourney['winner'] : 0,
							'status' => $this->_check_player_status($scoreToPar),
						];
						if ($teeTime) {
							$tPlayerData['tee_time'] = $teeTime;
						}
						
						$this->_notifyAdminFlag = true;
						$notifyMessage = "<strong>".$tourney['player_name']."</strong> GSID: <em style='color:red;'>".$tourney['player_id']."</em></strong> ID: <em style='color:green;'>".$player->id."</em>";
						$type = "NEW TOURNAMENT PLAYER";
						$this->_add_message($notifyMessage,$type);
						
						return $this->_golfTournamentPlayerProvider->create($tPlayerData);
					
					}
				}
			} catch (\Exception $e) {
				\Log::error($e->getLine());
			}
			return null;
		}
		
		private function _check_player_status($score)
		{
			$score = strtoupper(strval(trim($score)));
			$statuses = ['DQ', 'WD', 'NS', 'NC', 'DNF', 'NR', 'CUT'];
			return (in_array($score, $statuses)) ? $score : 'Active';
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
				$subject = "CHANGES: ENTRANTS FEED: " . $this->_localTournament->name;
				UserNotificationHelper::send_admin($subject, $header.$this->_message.$footer);
			}
			
		}
	}