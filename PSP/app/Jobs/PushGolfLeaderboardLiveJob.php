<?php

namespace App\Jobs;

use App\Events\GolfLeaderboardLiveUpdateEvent;
use App\Events\GolfLiveUpdateEvent;
use App\Models\GolfTournament;
use App\Models\RunMonitor;
use App\Providers\Pool\Golf\GolfEntryProvider;
use App\Providers\Pool\Golf\GolfTournamentPlayerProvider;
use App\Providers\Pool\Golf\GolfTournamentProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PushGolfLeaderboardLiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
	
	private $_tournament;
	private $_golfTournamentProvider;
    public $tries = 1;

    public function __construct($tournament)
    {
       $this->_tournament = $tournament;
	   $this->_golfTournamentProvider = resolve(GolfTournamentProvider::class);
    }

    public function handle()
    {
        try{
	        $this->_tournament->load(['tournament_players','tournament_players.player']);
            $tData = [
                'par' => $this->_tournament->par,
                'status' => $this->_tournament->status,
            ];
            $players = $this->_golfTournamentProvider->load_players_live($this->_tournament);

            broadcast(new GolfLeaderboardLiveUpdateEvent($this->_tournament, $players));
        } catch (\Exception $e){
            __dlog($e);
        }
        return;
    }

    public function failed(\Throwable $exception)
    {
        __dlog($exception);
    }
}






