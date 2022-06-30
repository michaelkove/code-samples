<?php
	
	namespace App\Http\Controllers\PspFrontend;
	
	use App\Helpers\ControllerHelper;
	use App\Models\GolfTournament;
	use App\Models\User;
	use App\Models\Friend;
	use App\Models\Group;
	use App\Providers\Pool\Golf\GolfTournamentProvider;
	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use App\Helpers\SiteHelper;
	use DB;
	
	class GolfTournamentController extends Controller
	{
		/**
		 * Display a listing of the resource.
		 *
		 * @return \Illuminate\Http\Response
		 */
		
		public $cHelper;
		private $_golfTournamentProvider;
		
		public function __construct(Request $request, GolfTournamentProvider $golfTournamentProvider)
		{
			$this->cHelper = new ControllerHelper($request);
			$this->_golfTournamentProvider = $golfTournamentProvider;
		}
		
		public function index(Request $request)
		{
			$ts = GolfTournament::where('active',true)->get();
			foreach($ts as $t){
				dump($t->slug);
			}
		}
		
		public function show($slug, Request $request)
		{
			$tournament = GolfTournament::where('slug',$slug)->where('archived', false)->first();
			SiteHelper::set_help($this->help_title, $this->help_content, 'live', 'golf');
			
			$data = [];
			$roundOverride = $request->get('round');
			// $roundOverride = 1;
			try {
				$data['players'] = $this->_golfTournamentProvider->load_players_live($tournament); //  $this->golfProvider->load_players_live_leaderboard($tournament, [], $roundOverride);
				
				
				
				
				$data['tournament'] = [
					'name' => $tournament->name,
					'par' => $tournament->par,
					'status' => $tournament->status,
					'finished' => $tournament->finished,
					'current_round' => $tournament->current_round,
					'players' => [],
				];
				
			} catch (\Exception $e) {
				\Log::error($e);
				$tournament = null;
			}
			
			$this->_build_crumbs(
				$tournament->name
			);
			return $this->cHelper->build_response('pspfrontend.pool.golf.live_leaderboard', [
				'liveData' => $data,
				'tournament' => $tournament,
			]);
		}
		
		private function _build_crumbs($label)
		{
			SiteHelper::add_crumb($this->crumbs, __c('pool.breadcrumbs_my_pools', "My Pools"), route('psp.user.pools'), false);
//			if ($this->pool) {
//				SiteHelper::add_crumb($this->crumbs, $this->pool->pool_name, route('psp.pool.show', ['pool' => $this->pool->id]), false);
//			}
			SiteHelper::add_crumb($this->crumbs, $label, '#', true);
			$this->cHelper->set_crumbs($this->crumbs);
		}
	}
