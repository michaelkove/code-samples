<?php

    namespace App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers;

    use App\Events\GSLogEvent;
    use App\Helpers\GolfHelper as GH;
    use App\Models\GolfTournament;
    use App\Providers\Pool\Golf\GolfTournamentProvider;
    use App\Providers\Pool\Golf\GolfPlayerProvider;
    use App\Providers\Pool\Golf\GolfTournamentPlayerProvider;
    use App\Providers\Pool\Golf\GolfTournamentPlayerRoundProvider;
    use App\Providers\Pool\Golf\GolfTournamentPlayerRoundHoleProvider;

    class GetGolfTournamentPlayerRoundHolesLive{

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
        private $_gsPlayers;
        private $_gsHoles;


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
        public function get_golf_tournament_player_round_holes_live(){
            return $this->_get_golf_tournament_player_round_holes_live();
        }

        private function _get_golf_tournament_player_round_holes_live(){

            $this->_load_local_data();
//            dd($this->_localTournament, $this->_localTournament->tournament_players->first(), $this->_localGolfPlayers);
            if($this->_localTournament && $this->_gsTournament){
                $i=0;
                foreach($this->_gsPlayers as $gsPlayer){
                    try{
                        $i++;
                        $golfPlayer = $this->_localGolfPlayers->where('external_id',$gsPlayer['player_id'])->first();
                        if($golfPlayer){
                            $tournamentPlayer = $this->_localTournamentPlayers->where('player_id', $golfPlayer->id)->first();
                            if($tournamentPlayer){
                                if(isset($gsPlayer['rounds'])){
                                    foreach($gsPlayer['rounds'] as $round){
                                        $localRound = $this->_golfTournamentPlayerRoundProvider->get(['number' => $round['number'], 'golf_tournament_player_id' => $tournamentPlayer->id])->first();
                                        if(isset($round['holes']) && $localRound){
                                            foreach($round['holes'] as $hole){
                                                $this->_do_hole($hole, $localRound, $tournamentPlayer);
                                            }
                                        }
                                    }
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

 


        private function _do_hole($hole = [], &$round, $golfTournamentPlayer){
            try{
                $name = ($golfTournamentPlayer->player) ? $golfTournamentPlayer->player->name : "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
//                broadcast(new GSLogEvent("<h4>".$golfTournamentPlayer->player->name."</h4>"));
                $holeNumber = intval($hole['number']);
//                \Log::info("Doing Hole ".$holeNumber);
                $localHole = $this->_golfTournamentPlayerRoundHoleProvider->get(
                    [
                        'golf_tournament_player_round_id' => $round->id,
                        'number' => $holeNumber,
                    ], true)
//                    ->with([
//                        'round',
//                        'round.tournament_player',
//                        'round.tournament_player.tournament',
//                        'round.tournament_player.player',
//                        ])
                    ->first();



                $holePar = intval($hole['par']);
                $score = ($hole['score'] && $hole['score'] !== "-") ? intval($hole['score']) : null;
                if($localHole){
                    $data = [];
                    if($holePar){
                        $data['par'] = $holePar;
                    }
                    if($score){
                        $data['score'] = $score;
                    }
                    if($hole['eagle']){
                        $data['eagle'] = $hole['eagle'];
                    }
                    if($hole['birdie']){
                        $data['birdie'] = $hole['birdie'];
                    }
                    if($hole['bogey']){
                        $data['bogey'] = $hole['bogey'];
                    }
                    if($hole['dbl_bogey_worse']){
                        $data['dbl_bogey_worse'] = $hole['dbl_bogey_worse'];
                    }
                    if(count($data)){

//                        $mess = "[UPDATED] R ".$round." >> ".$name." >>> HOLE ".$hole['number']." | ".$score;
//                        \Log::info($mess);
                        $this->_golfTournamentPlayerRoundHoleProvider->update($localHole->id, $data);
//                        broadcast(new GSLogEvent($mess));
                    }
                } else {
//                    \Log::info("NO local hole");
                    $data = [
                        'golf_tournament_player_round_id' => $round->id,
                        'number' => $hole['number'],
                        'par' => $holePar,
                        'score' => $score,
                        'eagle' => $hole['eagle'],
                        'birdie' => $hole['birdie'],
                        'bogey' => $hole['bogey'],
                        'dbl_bogey_worse' => $hole['dbl_bogey_worse'],
                    ];
//                    $mess = "[CREATED] R ".$round." >> ".$name." >>> HOLE ".$hole['number']." | ".$score;
//                    \Log::info($mess);
                    $localHole = $this->_golfTournamentPlayerRoundHoleProvider->create($data);
                }

            } catch(\Exception $e){
                \Log::error($e);
            }
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
            $this->_localTournament = $this->_golfTournamentProvider->get(['external_id' => $externalTournamentId], true)->with(['tournament_players'])->first();
//            $sec = GolfTournament::where('external_id', $externalTournamentId)->get();
//            dd($externalTournamentId, $this->_localTournament, $sec);
            $this->_localGolfPlayers = $this->_golfPlayerProvider->get([], true)->whereIn('external_id', $updatingPlayerIds)->get();
//            \Log::info("GRABBED ".$this->_localTournament);
//            \Log::info("Grabbed Plaeyrs ".$this->_localGolfPlayers->count());
            if($this->_localTournament){
                $localPlayersId = [];
                foreach($this->_localGolfPlayers as $gp){
                    $localPlayersId[] = $gp->id;
                }

                $this->_localTournamentPlayers = $this->_localTournament->tournament_players;
                $this->_gsPlayers = $this->_golfHelper->getRoundsAsCollection();
            }

            return true;
        }
    }
