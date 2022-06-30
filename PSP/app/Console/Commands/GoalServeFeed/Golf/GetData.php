<?php

namespace App\Console\Commands\GoalServeFeed\Golf;

use App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers\GetGolfPlayers;
use App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers\GetGolfTournamentEntrants;
use App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers\GetGolfTournamentLive;
use App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers\GetGolfTournamentPlayerRoundHolesLive;
use App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers\GetGolfTournamentPlayerRoundsLive;
use App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers\GetGolfTournamentPlayersLive;
use App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers\GetGolfTournaments;
use App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers\GetGolfTournamentScoresLive;
use App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers\GolfRankHelper;

use App\Helpers\SiteHelper;
use App\Models\GolfTournamentPlayer;
use App\Providers\Pool\Golf\GolfTournamentPlayerProvider;
use App\Providers\Pool\Golf\GolfTournamentProvider;
use Illuminate\Console\Command;

class GetData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goalserve:golf {type} {--id=}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Golf Data';
	
	private $_golfTournamentLiveHelper;
	private $_golfPlayersHelper;
	private $_golfRankHelper;
	private $_golfTournamentEntrantsHelper;
	private $_golfTournamentsHelper;
    private $_getGolfTournamentPlayersLiveHelper;
    private $_getGolfTournamentScoresLiveHelper;
    private $_getGolfTournamentPlayerRoundsLiveHelper;
    private $_getGolfTournamentPlayerRoundHolesLiveHelper;
	private $_golfTournamentProvider;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        GetGolfTournamentLive $golfTournamentLiveHelper,
		GetGolfPlayers $golfPlayersHelper,
		GolfRankHelper $golfRankHelper,
        GetGolfTournamentEntrants $golfTournamentEntrantsHelper,
        GetGolfTournaments $golfTournamentsHelper,
        GetGolfTournamentPlayersLive $getGolfTournamentPlayersLiveHelper,
        GetGolfTournamentScoresLive $getGolfTournamentScoresLiveHelper,
        GetGolfTournamentPlayerRoundsLive $getGolfTournamentPlayerRoundsLiveHelper,
        GetGolfTournamentPlayerRoundHolesLive $getGolfTournamentPlayerRoundHolesLiveHelper,
		GolfTournamentProvider $golfTournamentProvider
    ) {
		$this->_golfPlayersHelper = $golfPlayersHelper;
		$this->_golfTournamentsHelper = $golfTournamentsHelper;
		$this->_golfRankHelper = $golfRankHelper;
        $this->_golfTournamentLiveHelper = $golfTournamentLiveHelper;
		$this->_golfTournamentEntrantsHelper = $golfTournamentEntrantsHelper;
        $this->_getGolfTournamentPlayersLiveHelper = $getGolfTournamentPlayersLiveHelper;
        $this->_getGolfTournamentScoresLiveHelper = $getGolfTournamentScoresLiveHelper;
        $this->_getGolfTournamentPlayerRoundsLiveHelper = $getGolfTournamentPlayerRoundsLiveHelper;
        $this->_getGolfTournamentPlayerRoundHolesLiveHelper = $getGolfTournamentPlayerRoundHolesLiveHelper;
        $this->_start = microtime(true);
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

        $this->_type = $this->argument('type');
        switch ($this->_type) {
            case 'tournaments':
				$this->_golfTournamentsHelper->get_tournaments();
                break;
            case 'players':
				$this->_golfPlayersHelper->get_players();
                break;
            case 'rankings':
				$this->_golfRankHelper->get_rankings();;
                break;
            case 'live-tournament':
                $this->_golfTournamentLiveHelper->get_tournament_live();
                break;
            case 'live-players':
                $this->_getGolfTournamentPlayersLiveHelper->get_golf_tournament_players();
                break;
            case 'live-scores':
                $this->_getGolfTournamentScoresLiveHelper->get_golf_tournament_scores_live();
                break;
            case 'live-rounds':
                $this->_getGolfTournamentPlayerRoundsLiveHelper->get_golf_tournament_player_rounds_live();
                break;
            case 'live-holes':
                $this->_getGolfTournamentPlayerRoundHolesLiveHelper->get_golf_tournament_player_round_holes_live();
                break;
            case 'live':

                $this->_golfTournamentLiveHelper->get_tournament_live();
                $this->_getGolfTournamentPlayersLiveHelper->get_golf_tournament_players(); //we have to play players here other
                $this->_getGolfTournamentPlayerRoundsLiveHelper->get_golf_tournament_player_rounds_live();
                $this->_getGolfTournamentPlayerRoundHolesLiveHelper->get_golf_tournament_player_round_holes_live();
				//play tourney by scoring IF not header
	            if(!SiteHelper::conf('golf.goalserve.live_use_header_data', 'boolean',1)){
		            $this->_golfTournamentProvider->play_current_tournament();
	            }
                break;
            case 'tournament':
                $id = $this->option('id');
				$this->_golfTournamentEntrantsHelper->get_tournament_entrants($id);
                break;
        }
        return true;
    }
}
