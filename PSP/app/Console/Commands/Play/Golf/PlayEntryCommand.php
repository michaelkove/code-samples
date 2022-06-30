<?php

    namespace App\Console\Commands\Play\Golf;

    use App\Events\GolfLiveUpdateEvent;
    use App\Events\GSLogEvent;
    use App\Events\SchedulerRunStatusEvent;
    use App\Functions\Golf\Entry\PlayEntryStats;
    use App\Functions\Golf\Golf\PlayPlayer;
    use App\Helpers\GolfHelper;
    use App\Helpers\SiteHelper;
    use App\Jobs\PlayGolf;
    use App\Jobs\PushGolfLeaderboardLiveJob;
    use App\Jobs\PushGolfLiveJob;
    use App\Models\Golf;
    use App\Models\GolfEntry;
    use App\Models\RunMonitor;
    use App\Providers\Play\GolfPlayProvider;
    use App\Providers\Pool\Golf\GolfProvider;
    use App\Providers\Pool\Golf\GolfEntryProvider;
    use App\Providers\Pool\Golf\GolfTournamentPlayerProvider;
    use App\Providers\Pool\Golf\GolfTournamentProvider;
    use Illuminate\Console\Command;
    use Illuminate\Support\Facades\Bus;


    class PlayEntryCommand extends Command
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */

        private $_golfTournamentProvider;
		private$_golfPlayProvider;
        protected $signature = 'play:entry-golf {id?} {--force=}';
        /**
         * The console command description.
         *
         * @var string
         */
        protected $description = 'Update entry on live play';


        public function __construct(
            GolfTournamentProvider $gtp,
	        GolfPlayProvider $gpp
        )
        {
			$this->_golfPlayProvider = $gpp;
            $this->_golfTournamentProvider = $gtp;
            parent::__construct();
        }


        /**
         * Execute the console command.
         *
         * @return mixed
         */


        public function handle()
        {
	       
			
//            $this->_force = ($this->option('force')) ??  SiteHelper::conf('golf.play_force_replay_entries', 'boolean',1);
            $id = $this->argument('id');
            if($id){
                $tournament = $this->_golfTournamentProvider->find($id);
                if($tournament){
                    try{
						$this->_play_tournament($tournament);
                    }catch (\Exception $e){
                        __dlog($e, true);
                    }
                }
            } else {
                $localTourneys = $this->_golfTournamentProvider->get_actives();

                foreach($localTourneys as $tournament){
                    try{
	                    $this->_play_tournament($tournament);
                    }catch (\Exception $e){
                        __dlog($e, true);
                    }
                }
            }
	        
            return true;
        }
		
		private function _play_tournament($tournament){
			$playId = "T: ".rand(1,2222);
			$runMonitor = RunMonitor::create([]);
			$runMonitor->start('Tournament: ', 'ID: '.$playId, 'job');
			$tournament->load(['golfs','golfs.pool']);
			$tCount = 0;
			foreach ($tournament->golfs as $golf) {
				try {
					$tCount++;
					$this->_golfPlayProvider->play_full($golf,'job',true);
				} catch (\Exception $e) {
					__dlog($e, true);
				}
			}
			// $runMonitor->stop("{<strong>$tournament->name}</strong> | Golfs ".$tCount);
			
			PushGolfLeaderboardLiveJob::dispatch($tournament);
		}
		
    }

