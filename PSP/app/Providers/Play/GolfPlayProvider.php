<?php
	namespace App\Providers\Play;
	
 	use App\Functions\Golf\Entry\PlayEntryStats;
    use App\Functions\Golf\Golf\PlayPlayer;
    use App\Helpers\Functions\Golf\PlayEntryFunctionHelper;
    use App\Helpers\GolfHelper;
    use App\Models\Golf;
    use App\Models\GolfEntry;
    use App\Models\RunMonitor;
   
    class GolfPlayProvider{
	
		private $_trigger;
		private $_monitor;
		
		
	
		function play_full($golf, $trigger = 'job', $monitor = true){
			try{
				$golf->load([
					'grouped_tournament_players',
					'grouped_tournament_players.player',
					'entries',
					'entries.groups',
					'entries.groups.tournament_players'
				]);
				$this->_trigger = $trigger;
				$this->_monitor = $monitor;
				$this->_play_players($golf);
				$this->_play_entries($golf);
				$golf->refresh();
				$this->_update_positions($golf);
			} catch (\Exception $e){
				\Log::error($e);
				return false;
			}
			
			return true;
			
		}
	
	    private function _play_players($golf){
			if($this->_monitor){
				$playId = "P: ".rand(1,2222);
				$runMonitor = RunMonitor::create([]);
				$runMonitor->start('Players: ', 'ID: '.$playId, $this->_trigger);
			}
			try{
				$golf->load(['grouped_tournament_players','grouped_tournament_players.player','tournament','tournament.tournament_players']);
				$players = $golf->grouped_tournament_players;
				$tournamentPlayers = $golf->tournament->tournament_players;
				$countCut = $golf->count_cut;
				$cutLine = $golf->cut_line;
				$cutPosition = $golf->cut_position;
				if($countCut){
					if($cutLine || !$cutPosition){
						$cutPosition = GolfHelper::get_last_active_position($tournamentPlayers, 1);
					}
				} else {
					$cutPosition = null;
				}
				
				$pCount = 0;
				foreach($players as &$player){
					$pCount++;
					$playData = null;
					$playerData = [
						'pos' => $player->pos,
						'status' => $player->status,
						'round_1' => $player->round_1,
						'round_2' => $player->round_2,
						'round_3' => $player->round_3,
						'round_4' => $player->round_4,
						'total' =>  $player->total,
						'prize' =>  $player->prize,
						'handicap' => $player->pivot->handicap,
					];
					$playData = PlayPlayer::main(
						[
							'player' => $playerData,
							'count_cut' => $countCut,
							'cut_position' => $cutPosition,
						]
					);
					$player->pivot->stats = json_encode($playData);
					$player->pivot->save();
					
				}
				if($this->_monitor){
					$runMonitor->stop("<strong>{$golf->pool->pool_name}</strong> | Players ".$pCount);
				}
			} catch (\Exception $e){
				if($this->_monitor){
					$runMonitor->stop("<strong>{$golf->pool->pool_name}</strong> | Players ".$pCount, true, [
						'error' => $e->getMessage(),
						'trace' => $e->getTraceAsString()
					]);
				}
				\Log::error($e);
				return false;
			}
			return true;
		   
	    }
	
	    private function _play_entries($golf){
			try{
				if($this->_monitor){
					$playId = "E: ".rand(1,2222);
					$runMonitor = RunMonitor::create([]);
					$runMonitor->start('Entries: ', 'ID: '.$playId, $this->_trigger);
				}
				
				$golf->load(['grouped_tournament_players','grouped_tournament_players.player','entries', 'entries.groups','entries.groups.tournament_players']);
				$groupedTournamentPlayers = $golf->grouped_tournament_players;
				$type = $golf->type;
				$eCount =0;
				$countCut = $golf->count_cut;
				foreach($golf->entries as $entry){
					$updateData = PlayEntryStats::main([
						'type' => $type,
						'towards_score' => $golf->toward_score,
						'entry' => $entry->toArray(),
						'players' => $golf->grouped_tournament_players->toArray(),
						'count_cut' => $countCut,
					]);
					
					foreach($updateData as $key => $value){
						$entry->{$key} = $value;
					}
					$entry->save();
					
					$eCount++;
				}
				if($this->_monitor){
					$runMonitor->stop("<strong>{$golf->pool->pool_name}</strong> | Entries: ".$eCount);
				}
			} catch (\Exception $e){
				if($this->_monitor){
					$runMonitor->stop("<strong>{$golf->pool->pool_name}</strong> | Entries: ".$eCount, true, [
						'error' => $e->getMessage(),
						'trace' => $e->getTraceAsString()
					]);
				}
				\Log::error($e);
				return false;
			}
		    return true;
	    }
	
	    private function _update_positions($golf){
			try{
				if($this->_monitor){
					$playId = "POS: ".rand(1,2222);
					$runMonitor = RunMonitor::create([]);
					$runMonitor->start('Positions: ', 'ID: '.$playId, $this->_trigger);
				}
				
				$golf->load(['entries']);
				
				$userEntries = $golf->entries->toArray();
				$positionedEntries = GolfHelper::sort_entry($userEntries, $golf->type, $golf->number_of_winners);
				
//				$userEntries = $golf->entries->toArray();
//				GolfHelper::sort_entry_pos($userEntries, $golf->type, 'total', $golf->number_of_winners);
				$eCount=0;
				foreach($positionedEntries as $entry){
					$eCount++;
					GolfEntry::where('id', $entry['id'])->update([
						'pool_winner' => $entry['pool_winner'],
						'pos' => $entry['pos'],
						'tpos' => $entry['tpos'],
					]);
				}
				if($this->_monitor){
					$runMonitor->stop("<strong>{$golf->pool->pool_name}</strong> | Entries: ".$eCount);
				}
			} catch (\Exception $e){
				if($this->_monitor){
					$runMonitor->stop("<strong>{$golf->pool->pool_name}</strong> | Entries: ".$eCount, true, [
						'error' => $e->getMessage(),
						'trace' => $e->getTraceAsString()
					]);
				}
				\Log::error($e);
				return false;
			}
		    return true;
	    }
		
		
	}