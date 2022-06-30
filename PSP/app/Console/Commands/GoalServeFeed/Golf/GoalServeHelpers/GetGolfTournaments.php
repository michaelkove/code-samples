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
	
	class GetGolfTournaments
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
		
		
		public function get_tournaments()
		{
			$tournaments = $this->_golfHelper->getTournamentsAsCollection();
			foreach ($tournaments as $tournament) {
				try {
					$year = date('Y', strtotime($tournament['start_date']));
					$skipYear = __conf('goalserve.skip_golf_year_fuck_goalserve', 'text', '2021');
					
					if ($year != $skipYear) {
						$gsId = $tournament['tournament_id'];
						
						$localTournament = $this->_golfTournamentProvider->get(['external_id' => $gsId], true)
						                                                 ->first();
						if ($localTournament) {
							$data = [
								'name' => strval($tournament['name']),
								'start_date' => date('Y-m-d', strtotime($tournament['start_date'])),
								'end_date' => date('Y-m-d', strtotime($tournament['end_date'])),
								'external_id' => $gsId,
							];
							$this->_golfTournamentProvider->update($localTournament->id, $data);
							$gsWinnerId = intval($tournament['winner_id']);
							if ($gsWinnerId) {
								$golfer = $this->_golfPlayerProvider->get(['external_id' => $gsWinnerId], true)
								                                    ->first();
								if ($golfer) {
									$this->_notifyAdminFlag = true;
									$notifyMessage = "<strong>".$tournament->name."</strong>";
									$type = "SETTING WINNER....";
									$this->_add_message($notifyMessage,$type);
									$this->_update_tournament_winner($localTournament->id, $golfer->id);
								}
							}
						} else {
							$purse = intval($tournament['purse']);
							$data = [
								'name' => strval($tournament['name']),
								'start_date' => date('Y-m-d', strtotime($tournament['start_date'])),
								'end_date' => date('Y-m-d', strtotime($tournament['end_date'])),
								'external_id' => $gsId,
								'status' => (isset($tournament['status'])) ? $tournament['status'] : "Not Started",
								'purse' => ($purse) ?? 0,
								'active' => false,
							];
							$this->_golfTournamentProvider->create($data);
							$this->_notifyAdminFlag = true;
							$name = "<strong>".$tournament['name']."</strong>";
							$purse = "<em>$".$data['purse']."</em>";
							$status = "(".$data['status'].")";
							$date = "<em style='color:blue;'>".$data['start_date']."</em>";
							$id = "<em style='color:darkgreen;'>".$data['external_id']."</em>";
							$notifyMessage = "{$name} {$status} - {$purse} | {$date} | GSID: {$id}";
							$type = "NEW TOURNAMENT";
							$this->_add_message($notifyMessage,$type);
						}
					}
				}
				catch (\Exception $e) {
					\Log::error($e);
				}
			}
			$this->_notify_admin();
			return true;
		}
		
		
		private function _update_tournament_winner($tId, $gId)
		{
			$tournamentPlayer = $this->_golfTournamentPlayerProvider->get(['tournament_id' => $tId, 'player_id' => $gId],
				true)
			                                                        ->first();
			if ($tournamentPlayer) {
				$data = [
					'winner' => true
				];
				$this->_golfTournamentPlayerProvider->update($tournamentPlayer->id, $data);
				
				$notifyMessage = "<strong>".$tournamentPlayer->player->name."</strong>";
				$type = "WINNER";
				$this->_add_message($notifyMessage,$type);
			} else {
				$notifyMessage = "<strong> N/A </strong>";
				$type = "NO WINNER";
				$this->_add_message($notifyMessage,$type);
			}
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
				$subject = "NEW TOURNAMENTS FROM FEED";
				UserNotificationHelper::send_admin($subject, $header.$this->_message.$footer);
			}
			
		}
	}