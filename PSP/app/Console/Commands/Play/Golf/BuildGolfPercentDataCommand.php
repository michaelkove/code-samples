<?php
	
	namespace App\Console\Commands\Play\Golf;
	
	use App\Events\GolfLiveUpdateEvent;
	use App\Events\GSLogEvent;
	use App\Events\SchedulerRunStatusEvent;
	use App\Helpers\SiteHelper;
	use App\Jobs\PlayGolf;
	use App\Jobs\PushGolfLeaderboardLiveJob;
	use App\Jobs\PushGolfLiveJob;
	use App\Models\Golf;
	use App\Providers\Play\GolfPlayProvider;
	use App\Providers\Pool\Golf\GolfProvider;
	use App\Providers\Pool\Golf\GolfEntryProvider;
	use App\Providers\Pool\Golf\GolfTournamentPlayerProvider;
	use App\Providers\Pool\Golf\GolfTournamentProvider;
	use Illuminate\Console\Command;
	use Illuminate\Support\Facades\Bus;
	
	
	class BuildGolfPercentDataCommand extends Command
	{
		/**
		 * The name and signature of the console command.
		 *
		 * @var string
		 */
		
		private $_golfTournamentPlayerProvider;
		private $_golfTournamentProvider;
		
		protected $signature = 'play:build-percent';
		/**
		 * The console command description.
		 *
		 * @var string
		 */
		protected $description = 'Run Golf Percentage';
		
		
		public function __construct(
			GolfTournamentPlayerProvider $golfTournamentPlayerProvider,
			GolfTournamentProvider $golfTournamentProvider
		)
		{
			$this->_golfTournamentPlayerProvider = $golfTournamentPlayerProvider;
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

			$localTourneys = $this->_golfTournamentProvider->get_actives();
			foreach($localTourneys as $tournament){
				$tournament->load(['golfs']);
				foreach($tournament->golfs as $golf){
					try{
						$this->_golfTournamentPlayerProvider->players_percent($golf, $tournament);
					} catch (\Exception $e){
						\Log::error($e);
					}
				}
			}

		}
		
		
	}
	