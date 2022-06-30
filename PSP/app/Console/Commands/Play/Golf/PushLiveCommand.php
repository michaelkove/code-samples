<?php

    namespace App\Console\Commands\Play\Golf;

    use App\Events\GolfLiveUpdateEvent;
    use App\Jobs\PlayGolf;
    use App\Jobs\PushGolfLeaderboardLiveJob;
    use App\Jobs\PushGolfLiveJob;
    use App\Providers\Pool\Golf\GolfProvider;
    use App\Providers\Pool\Golf\GolfTournamentProvider;
    use Illuminate\Console\Command;
    use Illuminate\Support\Facades\Bus;


    class PushLiveCommand extends Command
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */

        private $_golfProvider;
        private $_golfTournamentProvider;

        protected $signature = 'push:live-golf {id?}';
        /**
         * The console command description.
         *
         * @var string
         */
        protected $description = 'Push live to golf';

        private $_gtp;
        /**
         * Create a new command instance.
         *
         * @return void
         */
        public function __construct(GolfProvider $golfProvider, GolfTournamentProvider  $golfTournamentProvider)
        {
            $this->_golfProvider = $golfProvider;
            $this->_golfTournamentProvider = $golfTournamentProvider;
            parent::__construct();

        }

        /**
         * Execute the console command.
         *
         * @return mixed
         */
        public function handle()
        {
            $id = $this->argument('id');
            if($id){
                $localTourney = $this->_golfTournamentProvider->find($id);
                if($localTourney){
                    try{
                        $this->_push_tourney($localTourney);
                    }catch (\Exception $e){
                        __dlog($e);
                    }
                } else {
                   // \Log::info("No local tourney to push");
                }
            } else {
                $localTourneys = $this->_golfTournamentProvider->get_actives();
                if($localTourneys->count()){
                    foreach($localTourneys as $localTourney){
                        $this->_push_tourney($localTourney);
                    }
                } else {
                  //  \Log::info("No local tourney to push");
                }
            }
            return true;
        }

        private function _push_tourney(&$tournament){
            if($tournament){
	            PushGolfLeaderboardLiveJob::dispatch($tournament);
                foreach($tournament->golfs as $golf){
                    try{
						PushGolfLiveJob::dispatch($golf);
						
                    }catch (\Exception $e){
                        __dlog($e);
                    }
                }
            }
        }
    }


