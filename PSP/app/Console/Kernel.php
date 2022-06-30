<?php

namespace App\Console;

use App\Console\Commands\GetGolfCurrentTournamentCommand;
use App\Console\Commands\GetGolfCurrentTournamentRoundStatsCommand;
use App\Console\Commands\GetGolfLiveScoreCommand;
use App\Console\Commands\GetGolfRankingCommand;
use App\Console\Commands\SyncMailChimpCommand;
use App\Helpers\SiteHelper;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use MongoDB\Driver\Command;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\GoalServeFeed\Golf\GetData::class,
	    Commands\GoalServeFeed\NFL\GetData::class,
	    Commands\GoalServeFeed\NCAAFootball\GetData::class,
	    
	    Commands\Play\Golf\PlayEntryCommand::class,
	    Commands\Play\Golf\LockCommand::class,
	    Commands\Play\Golf\CleanPlayersCommand::class,
	    Commands\Play\Golf\PushLiveCommand::class,
	    Commands\Play\Golf\GolfMasterLockCommand::class,
	    Commands\Play\Golf\ProcessPaymentCommand::class,
	    
	    Commands\Play\Squareboard\RemindCommand::class,
	    
	    Commands\Play\Schwindy\LockSchwindyCommand::class,
	    Commands\Play\Schwindy\PlayPicks::class,
	    Commands\Play\Schwindy\Autopick::class,
	    Commands\Play\Schwindy\Live::class,
	    
	    Commands\Play\PlaySquare::class,
	    Commands\Play\PlayNCAASquare::class,
	    Commands\Play\SetCurrentWeekCommand::class,
	    
	
	    Commands\FlushSessionCommand::class,
	    Commands\GetCollegeScores::class,
	    Commands\GetGolfLiveScoreCommand::class,
	    Commands\SyncMailChimpCommand::class,
	    Commands\GetGolfCurrentTournamentRoundStatsCommand::class,
	    Commands\SchwindyPickReminderCommand::class,
    ];
		
	/**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
		
	    if (SiteHelper::conf('schedule.golf.datagolf.activate_tournaments', 'boolean', 1)) {
		    $schedule->command('datagolf:golf activate')
		             ->daily()
		             ->at('02:00'); //activate next tourneys
	    }
	    if (SiteHelper::conf('schedule.golf.datagolf.deactivate_tournaments', 'boolean', 1)) {
		    $schedule->command('datagolf:golf deactivate')
		             ->daily()
		             ->at('02:01'); //activate next tourneys
	    }
	    if (SiteHelper::conf('schedule.golf.datagolf.get_schedule', 'boolean', 1)) {
		    $schedule->command('datagolf:golf schedule')
		             ->daily()
		             ->at('00:00'); //grab all the tournaments daily from Datagolf
	    }
	    if (SiteHelper::conf('schedule.golf.datagolf.get_players', 'boolean', 1)) {
		    $schedule->command('datagolf:golf players')
		             ->everyThirtyMinutes(); //grab all the players every 30 mins
	    }
	    if (SiteHelper::conf('schedule.golf.datagolf.get_rankings', 'boolean', 1)) {
		    $schedule->command('datagolf:golf rankings')
		             ->everyThirtyMinutes(); //grab rankings every 30 mins
	    }
	    if (SiteHelper::conf('schedule.golf.datagolf.get_scores_golf', 'boolean', 1)) {
		    $schedule->command('datagolf:golf live')
		             ->everyMinute(); //play tournament stats every minute
		    $schedule->command('datagolf:golf live-check')
		             ->thursdays()->timezone('America/New_York')->at('7:00'); //play tournament stats every minute
		    $schedule->command('datagolf:golf live-check')
		             ->thursdays()->timezone('America/New_York')->at('10:00'); //play tournament stats every minute
	    }
	    if (SiteHelper::conf('schedule.golf.datagolf.get_field', 'boolean', 1)) {
		    $schedule->command('datagolf:golf field')->everyMinute(); //not much shit changes here
	    }
	
	    
		
	    if (SiteHelper::conf('schedule.golf.datagolf.get_in_play_scores', 'boolean', 1)) {
		    $schedule->command('datagolf:golf in-play')->everyMinute(); //play tournament stats every minute
	    }

        if (SiteHelper::conf('schedule.golf.play_entry', 'boolean', 1))
		{
            $schedule->command('play:entry-golf')->everyMinute(); //play golf entry
            $schedule->command('push:live-golf')->everyMinute(); //PUSH GOLF LIVE
        }
		
		
		
        if (SiteHelper::conf('schedule.golf.play_lock_entry', 'boolean', 1)) {
            $schedule->command('play:lock-golf')->everyMinute();
	        //TODO: we need to fix this to run on SECOND day of the tournament not on Friday. Because some tourney run on Wed
            $schedule->command('play:lock-golf-master')->days([4,5])->timezone('America/New_York')->at('13:00'); //LOCK COMISH OUT - either Friday or Thur if Tournament started on Wd
            $schedule->command('play:lock-golf-master')->daily()->timezone('America/New_York')->at('13:00');
            $schedule->command('pay:process-golf')->fridays()->at('3:00');  // unused
            $schedule->command('billing:charge-golf')->fridays()->at('3:00');
        }
		
		//Maintenance Golf Tasks
	    $schedule->command('play:build-percent')->everyFiveMinutes();
		
        //ranking run once a week


        if (SiteHelper::conf('schedule.goalserve.get_scores_nfl', 'boolean', 1)) {
            $schedule->command('goalserve:nfl score')->everyMinute(); //update schwindy picks
            $schedule->command('goalserve:nfl odds')->everyMinute(); //update schwindy picks
            $schedule->command('goalserve:nfl schedule')->hourly(); //hourly update schedule
        }
        if (SiteHelper::conf('schedule.goalserve.get_scores_ncaa', 'boolean', 1)) {
            $schedule->command('goalserve:ncaa score')->everyMinute(); //update schwindy picks
            $schedule->command('goalserve:ncaa odds')->everyMinute(); //update schwindy picks
            $schedule->command('goalserve:ncaa schedule')->hourly(); //hourly update schedule
        }
        if (SiteHelper::conf('schedule.squareboard.play', 'boolean', 1)) {
            $schedule->command('play:square')->everyMinute()->withoutOverlapping(); //update odds
        }

        if (SiteHelper::conf('schedule.ncaa.play', 'boolean', 1)) {
            $schedule->command('play:ncaasquare')->everyMinute(); //update odds
        }

        if (SiteHelper::conf('schedule.schwindy.play', 'boolean', 1)) {
            $schedule->command('schwindy:play')->everyMinute(); //set schwindy current week so we know whatsup
            $schedule->command('schwindy:live')->everyMinute(); //push live
            $schedule->command('nfl:setweek')->everyMinute();
            $schedule->command('schwindy:lock game')->everyMinute(); //lock and show picks per game basis
            $schedule->command('schwindy:lock all')->sundays()->timezone('America/New_York')->at('13:00'); //lock and show picks for entire week sunday.
            $schedule->command('schwindy:lock all')->sundays()->timezone('America/New_York')->at('23:59'); //lock and show picks for entire week sunday.
            // $schedule->command('schwindy:lock all')->thursdays()->timezone('America/New_York')->at('07:05'); //LOCK TEST
        }


        if (SiteHelper::conf('schedule.mailchimp.sync', 'boolean', 1)) {
            $schedule->command('mailchimp:sync')->daily()->at('00:00');
        }

        $schedule->command('pick:reminder')->thursdays()->at('15:00')->onOneServer();
        $schedule->command('pick:reminder')->sundays()->at('09:00')->onOneServer();


        $schedule->command('broadcast:test')->everyMinute();
        $schedule->command('remove_jobs:test')->daily()->at('00:00');  //grab rankings
	
	    $schedule->command('model:prune')->daily();
	
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
