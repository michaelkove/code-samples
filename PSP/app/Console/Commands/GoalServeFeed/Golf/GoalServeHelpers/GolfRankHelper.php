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
	
	class GolfRankHelper
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
		
		public function get_rankings()
		{
			$rankings = $this->_golfHelper->getRankingsAsCollection();
			$createdPlayers = [];
			foreach ($rankings as $rankedPlayer) {
				try {
					$gsPlayerId = intval($rankedPlayer['player_id']);
					$gsPlayerRank = intval($rankedPlayer['pos']);
					$localPlayer = $this->_golfPlayerProvider->get([], true)->where('external_id', $gsPlayerId)->first();
					$pos = ($gsPlayerRank > 0) ? $gsPlayerRank : 9999;
					if ($localPlayer) {
						$updateData = ['pos' => $pos];
						$this->_golfPlayerProvider->update($localPlayer->id, $updateData);
					} else {
						$createData = [
							'pos' => $pos,
							'name' => isset($rankedPlayer['name']) ? $rankedPlayer['name'] : "No Name",
							'external_id' => $gsPlayerId,
							'avg_points' => isset($rankedPlayer['avg_points']) ? $rankedPlayer['avg_points'] : "0",
							'points_total' => isset($rankedPlayer['points_total']) ? $rankedPlayer['points_total'] : "0",
							'events' => isset($rankedPlayer['events']) ? intval($rankedPlayer['events']) : "0",
							"internal_note" => "Created on " . date('F j, Y H:i:s'),
							'order' => 0,
							'config' => ['pga_id' => null, 'status' => null],
							'pga_id' => null
						];
						$this->_golfPlayerProvider->create($createData);
						$createdPlayers[] = $createData;
						
						$this->_notifyAdminFlag = true;
						$notifyMessage = "<strong>".$createData['name']."</strong> RANK: <strong style='color:red'>".$pos."</strong> GSID: <em style='color:red;'>".$createData['external_id']."</em>";
						$type = "NEW GOLFER";
						$this->_add_message($notifyMessage,$type);
					}
				} catch (\Exception $e) {
					
					\Log::error($e);
				}
			}
			$this->_notify_admin();
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
				$subject = "NEW RANKS";
				UserNotificationHelper::send_admin($subject, $header.$this->_message.$footer);
			}
			
		}
	}