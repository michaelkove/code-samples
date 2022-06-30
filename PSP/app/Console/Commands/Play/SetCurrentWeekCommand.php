<?php

namespace App\Console\Commands\Play;

use App\Helpers\SiteHelper;
use Illuminate\Console\Command;
use App\Providers\Game\Game as GameProvider;

class SetCurrentWeekCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nfl:setweek';
    private $_gameProvider;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set NFL current week.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GameProvider $gameProvider)
    {
        $this->_gameProvider = $gameProvider;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $currentSeason = SiteHelper::conf('schwindy.season');
        $weekPlayed = [];

        $max_week = __conf('schwindy.max_week', 'text', '18');
        for ($week = 1; $week <= $max_week; $week++) {

            $leftToPlay = 0;
            $games = $this->_gameProvider->get([
                'sport' => 'football',
                'league' => 'professional',
                'season_year' => $currentSeason,
                'season' => 'Regular Season',
                'week' => $week,
            ]);
            foreach ($games as $game) {
                if (!$game->is_final) {
                    $leftToPlay++;
                }
            }
            $weekPlayed[$week] = ($leftToPlay === 0);
        }
        $lastWeekPlayed = 0;
        foreach ($weekPlayed as $week => $played) {
            if ($played) {
                $lastWeekPlayed = $week;
            }
        }
        $lastWeekPlayed++;
        SiteHelper::set_conf('schwindy.current_week', $lastWeekPlayed);
    }
}
