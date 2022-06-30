<?php

    namespace App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers;

    use App\Events\GSLogEvent;
    use App\Helpers\GolfHelper as GH;
    use App\Helpers\SiteHelper;
    use App\Providers\Pool\Golf\GolfTournamentProvider;
    use App\Providers\Pool\Golf\GolfPlayerProvider;
    use App\Providers\Pool\Golf\GolfTournamentPlayerProvider;
    use App\Providers\Pool\Golf\GolfTournamentPlayerRoundProvider;
    use App\Providers\Pool\Golf\GolfTournamentPlayerRoundHoleProvider;

    class GetGolfTournamentPlayerRoundsLive{

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
        private $_par;


        private $_start;

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
        }

        /**
         * @return bool
         */
        public function get_golf_tournament_player_rounds_live(){
            return $this->_get_golf_tournament_player_rounds_live();
        }

        private function _get_golf_tournament_player_rounds_live(){


            $this->_load_local_data();

            if($this->_localTournament && $this->_gsTournament){
                $count = count($this->_gsRounds);
                $i=0;
                foreach($this->_gsRounds as $item)
                {
                    try{
                        $i++;
                        $player = $this->_localGolfPlayers->where('external_id',$item['player_id'])->first();
                        if($player){
                            $tournamentPlayer = $this->_localTournamentPlayers->where('player_id', $player->id)->first();
                            if(isset($item['rounds']) && $item['rounds'] && $tournamentPlayer){
                                $tournamentPlayerRound = $this->_do_rounds($tournamentPlayer, $item['rounds']);
                            } else {
                                if(!$tournamentPlayer){
//                                    \Log::info("NO PL".$item['player_id']);
//                                    \Log::info($tournamentPlayer);
                                }

                            }
                        }
                    } catch(\Exception $e){
                        \Log::error($e);
                    }

                }
            }
            return true;
        }

        private function _load_local_data(){
            $this->_gsTournament = $this->_golfHelper->getCurrentTournamentAsCollection();
            $updatingPlayerIds = [];
            $externalTournamentId = null;
            foreach($this->_gsTournament as $item){
                try{
                    $updatingPlayerIds[] = intval($item['player_id']);
                    $externalTournamentId = $item['tournament_id'];
                } catch(\Exception $e){
                    \Log::error($e);
                }

            }
            $this->_localGolfPlayers = $this->_golfPlayerProvider->get([], true)->whereIn('external_id', $updatingPlayerIds)->get();
            $this->_localTournament = $this->_golfTournamentProvider->get(['external_id' => $externalTournamentId], true)->with(['tournament_players'])->first();
            $this->_par = ($this->_localTournament) ? $this->_localTournament->par : 0;
            $localPlayersId = [];
            foreach($this->_localGolfPlayers as $gp){
                $localPlayersId[] = $gp->id;
            }
            $this->_localTournamentPlayers = ($this->_localTournament) ? $this->_localTournament->tournament_players : collect([]);
            $this->_gsRounds = $this->_golfHelper->getRoundsAsCollection();
            return true;
        }

        private function _do_rounds(&$golfTournamentPlayer, $rounds){

            try {

                $forceRerun = SiteHelper::conf('golf.play_force_replay_tournament', 'boolean',1);
                $playerRound = null;
                if(isset($rounds) && count($rounds) && ($this->_localTournament->status !== 'Not Started')){

                    foreach($rounds as $round){
                        try {
                            $playerRound  = $this->_golfTournamentPlayerRoundProvider->get(['golf_tournament_player_id' => $golfTournamentPlayer->id, 'number' => $round['number']], true)->first();
                            if($playerRound){
                                $data = [
                                    'result' => $this->_golfHelper::parse_scoring_to_par($round['result'], $this->_par)
                                ];
                                $this->_golfTournamentPlayerRoundProvider->update($playerRound->id, $data);
//                                broadcast(new GSLogEvent("[<span class='text-brand-orange'>".$golfTournamentPlayer->player->name."</span>]"."Updated round  ".$round['number']." with >>> ",$data['result']));
                            } else {
                                $data = [
                                    'golf_tournament_player_id' => $golfTournamentPlayer->id,
                                    'number' => intval($round['number']),
                                    'result' => $this->_golfHelper::parse_scoring_to_par($round['result'], $this->_par)
                                ];
                                $playerRound = $this->_golfTournamentPlayerRoundProvider->create($data);

//                                broadcast(new GSLogEvent("[<span class='text-brand-orange'>".$golfTournamentPlayer->player->name."</span>]"."Created round  ".$data['number']." with >>> ",$data['result']));
                            }
                        }catch (\Exception $e){
                            \Log::error($e);
                        }
                    }
                    return $playerRound;
                }
            } catch(\Exception $e){
                \Log::error($e);
            }

            return null;
        }



    }
