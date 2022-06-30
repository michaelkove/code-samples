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
	
	class GetGolfPlayers
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
		
		public function get_players()
		{
			$players = $this->_golfHelper->getPlayersAsCollection();
			foreach ($players as $player) {
				try {
					$gsId = intval($player['player_id']);
					$curPlayer = $this->_golfPlayerProvider->get(['external_id' =>  $gsId], true)->first();
					if (false) {
						//                        if(file_exists( public_path()."/assets/images/golf/{$gsId}.png")){
						$photo = $gsId . ".png";
					} else {
						$playerData = $this->_golfHelper->getPlayerDataAsObject($gsId);
						if ($playerData && isset($playerData->player->image)) {
							try {
								$imgString = strval($playerData->player->image);
								$img = Image::make($imgString);
								$imgPath = public_path('assets/images/golf/' . $gsId . ".png");
								if($img){
									$rec = $img->save($imgPath, 100);
									$photo = $gsId . ".png";
								} else {
									$photo = "no-photo.png";
								}
							} catch (\Exception $e) {
								$photo = "no-photo.png";
								\Log::error($e);
							}
						} else {
							$photo = "no-photo.png";
						}
					}
					if ($curPlayer) {
						$fullName = strval($player['name']);
						$data = [
							'country' => strval($player['country']),
							'photo' => $photo
						];
						$this->_golfPlayerProvider->update($curPlayer->id, $data);
					} else {						
						$fullName = strval($player['name']);
						$partedName = __split_name($fullName);
						$data = [
							'name' => $fullName,
							'first_name' => $partedName['first_name'],
							'last_name' => $partedName['last_name'],
							'middle_name' => $partedName['middle_name'],
							'external_id' => $gsId,
							'country' => strval($player['country']),
							'pos' => 9999,
							'avg_points' => 0,
							'points_total' => 0,
							'events' => 0,
							"internal_note" => "GET PLAYERS: Created on " . date('F-j-Y G:i:a'),
							'order' => 0,
							'photo' => $photo
						
						];
						$this->_golfPlayerProvider->create($data);
						$this->_notifyAdminFlag = true;
						$notifyMessage = "<strong>".$data['name']."</strong> GSID: <em style='color:red;'>".$data['external_id']."</em>";
						$type = "NEW GOLFER";
						$this->_add_message($notifyMessage,$type);
						
					}
				} catch (\Exception $e) {
					\Log::error($e);
				}
			}
			$this->_notify_admin();
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
				$subject = "NEW GOLFERS";
				UserNotificationHelper::send_admin($subject, $header.$this->_message.$footer);
			}
			
		}
	}