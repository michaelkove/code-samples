<?php

    namespace App\Jobs;

    use App\Models\GolfTournament;
    use App\Providers\Pool\Golf\GolfTournamentPlayerProvider;
    use App\Providers\Pool\Golf\GolfTournamentProvider;
    use Illuminate\Bus\Queueable;
    use Illuminate\Queue\SerializesModels;
    use Illuminate\Queue\InteractsWithQueue;
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Illuminate\Foundation\Bus\Dispatchable;

    class PlayGolfTournament implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $golfTournament;

        /**
         * FakePlayGolfRoundJob constructor.
         * @param GolfTournament $golfTournament
         * @param int $round
         */
        public function __construct(GolfTournament $golfTournament)
        {
            $this->golfTournament = $golfTournament;
        }

        /**
         * @param GolfTournamentProvider $golfTournamentProvider
         * @param GolfTournamentPlayerProvider $golfTournamentPlayerProvider
         * @return bool
         */
        public function handle(GolfTournamentProvider $golfTournamentProvider, GolfTournamentPlayerProvider $golfTournamentPlayerProvider)
        {
            //fetch players
            $this->golfTournament->load(['golfs','tournament_players','tournament_players.player','tournament_players.rounds', 'tournament_players.rounds.holes', 'tournament_players.rounds.scored_holes']);
            $golfTournamentProvider->play_tournament($this->golfTournament, true, true);
            return true;
        }
    }
