<?php

    namespace App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers;

    use App\Helpers\GolfHelper as GH;
    use App\Providers\Pool\Golf\GolfTournamentProvider;
    use App\Providers\Pool\Golf\GolfPlayerProvider;
    use App\Providers\Pool\Golf\GolfTournamentPlayerProvider;
    use App\Providers\Pool\Golf\GolfTournamentPlayerRoundProvider;
    use App\Providers\Pool\Golf\GolfTournamentPlayerRoundHoleProvider;

    class GetGolfTournamentScoresLive{

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
        private $_gsScores;
        private $_par;

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
        }

        /**
         * @return bool
         */
        public function get_golf_tournament_scores_live(){
            return $this->_get_golf_tournament_scores_live();
        }

        private function _get_golf_tournament_scores_live(){
            $this->_load_local_data();
            if($this->_localTournament && $this->_gsTournament){
                foreach($this->_gsScores as $item) {
                    try {
                        $player = $this->_localGolfPlayers->where('external_id', $item['player_id'])->first();
                        if ($player && $this->_localTournament) {
                            $data = $this->_do_scores($item);
                            $tournamentPlayer = $this->_localTournamentPlayers->where('player_id', $player->id)->first();

                            $this->_golfTournamentPlayerProvider->update($tournamentPlayer->id, $data);
                        }
                    } catch (\Exception $e) {
                        \Log::error($e);
                    }
                }
            }
            return true;
        }

        private function _load_local_data(){
            $this->_gsTournament = $this->_golfHelper->getCurrentTournamentAsCollection();
            $this->_gsScores = $this->_golfHelper->getCurrentScoreAsCollection();

            $updatingPlayerIds = [];
            $externalTournamentId = null;
            foreach($this->_gsTournament as $item){

                try{
                    $updatingPlayerIds[] = intval($item['player_id']);
                    $externalTournamentId = intval($item['tournament_id']);
                } catch(\Exception $e){
                    \Log::error($e);
                }

            }
//            \Log::info("out of  loop");

            $this->_localGolfPlayers = $this->_golfPlayerProvider->get([], true)->whereIn('external_id', $updatingPlayerIds)->get();
//            \Log::info("got local players");
//            \Log::info($externalTournamentId);
	        $this->_localTournament = $this->_golfTournamentProvider->get_by_dated_external_id($externalTournamentId);
	        $this->_localTournament->load([ 'tournament_players','tournament_players.completed_rounds']);
           
            $this->_par = $this->_localTournament->par;
            $localPlayersId = [];
//            \Log::info("before local play");
            foreach($this->_localGolfPlayers as $gp){
                $localPlayersId[] = $gp->id;
            }
            $this->_localTournamentPlayers = $this->_localTournament->tournament_players;
//            \Log::info("Pulled ".$this->_localTournamentPlayers->count());
//            \Log::info("Live local data loaded");
            return true;
        }


        private function _do_scores($scoring){
            return [
//                'pos' => (isset($scoring['pos']) && $scoring['pos'] != "") ? intval($scoring['pos']) : "9999",
//                'tee_time' => (isset($scoring['tee_time']) && $scoring['tee_time'] != "") ? $scoring['tee_time'] : "23:59",
                'par' => (isset($scoring['par']) && $scoring['par'] != "") ? $scoring['par'] : "0",
                'hole' => (isset($scoring['hole']) && $scoring['hole'] != "") ? $scoring['hole'] : "0",
                'score_to_par' => (isset($scoring['score_to_par']) && $scoring['score_to_par'] != "") ? $scoring['score_to_par'] : "0",
                'drive_dist_avg' => (isset($scoring['driveDistAvg']) && $scoring['driveDistAvg'] != "") ? $scoring['driveDistAvg'] : "0",
                'drive_accuracy_pct' => (isset($scoring['driveAccuracyPct']) && $scoring['driveAccuracyPct'] != "") ? $scoring['driveAccuracyPct'] : "0",
                'gir' => (isset($scoring['gir']) && $scoring['gir'] != "") ? $scoring['gir'] : "0",
                'putts_gir_avg' => (isset($scoring['puttsGirAvg']) && $scoring['puttsGirAvg'] != "") ? $scoring['puttsGirAvg'] : "0",
                'saves' => (isset($scoring['saves']) && $scoring['saves'] != "") ? $scoring['saves'] : "0",
                'eagles' => (isset($scoring['eagles']) && $scoring['eagles'] != "") ? $scoring['eagles'] : "0",
                'birdies' => (isset($scoring['birdies']) && $scoring['birdies'] != "") ? $scoring['birdies'] : "0",
                'pars' => (isset($scoring['pars']) && $scoring['pars'] != "") ? $scoring['pars'] : "0",
                'bogeys' => (isset($scoring['bogeys']) && $scoring['bogeys'] != "") ? $scoring['bogeys'] : "0",
                'doubles' => (isset($scoring['doubles']) && $scoring['doubles'] != "") ? $scoring['doubles'] : "0",
//                'today' => (isset($scoring['today']) && $scoring['today'] != "") ? $scoring['today'] : "0",
//                'total' => (isset($scoring['total']) && $scoring['total'] != "") ? $scoring['total'] : "0",
//                'live_total' => (isset($scoring['total'])) ? $this->_parse_scoring($scoring['total']) : null,
//                'live_today' => (isset($scoring['today'])) ? $this->_parse_scoring($scoring['today']) : null,
            ];
        }

        private function _parse_scoring($scoring){
            if($scoring === 'E'){
                return 0;
            }
            if($scoring === "-" || $scoring === ""){
                return null;
            }
            return intval($scoring - $this->_par);

        }
    }
