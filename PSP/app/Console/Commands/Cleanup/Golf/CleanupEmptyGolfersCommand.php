<?php
	
	namespace App\Console\Commands\Cleanup\Golf;
	
	use App\Events\SchedulerRunStatusEvent;
	
	use App\Models\GolfPlayer;
	use Illuminate\Console\Command;
	use PHPUnit\Exception;
	
	class CleanupEmptyGolfersCommand extends Command
	{
		/**
		 * The name and signature of the console command.
		 *
		 * @var string
		 */
		protected $signature = 'cleanup:golf {type}';
		/**
		 * The console command description.
		 *
		 * @var string
		 */
		protected $description = 'Cleanup Golf Stale Data';
		
		private $_type;
		/**
		 * Create a new command instance.
		 *
		 * @return void
		 */
		public function __construct(
		
		) {
		
			parent::__construct();
		}
		
		
		/**
		 * Execute the console command.
		 *
		 * @return mixed
		 */
		public function handle()
		{
			$this->_type = $this->argument('type');
			switch ($this->_type) {
				case 'players':
					$data = $this->_cleanup_players();
					break;
					
			}
			return true;
		}
		
		private function _cleanup_players(){
			$players = GolfPlayer::with(['golf_plays'])
			                     ->get();
			$withPlay = 0;
			$deleted = 0;
			foreach($players as $player){
				if($player->golf_plays->count() > 0){
					$withPlay++;
				} else {
					$deleted++;
					GolfPlayer::where('id', $player->id)->delete();
				}
			}
			return ['deleted' => $deleted, 'kept' => $withPlay];
		}
	}
	
	
	