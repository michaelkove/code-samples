<?php

namespace App\Console\Commands\Play\Golf;

use App\Helpers\UserNotificationHelper;
use App\Models\Golf;
use App\Providers\Pool\Golf\GolfTournamentProvider;
use Illuminate\Console\Command;
use App\Mail\Pool\Golf\SendGolf;


class LockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'play:lock-golf {id?}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lock golf at Tee time';

    private $_gtp;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GolfTournamentProvider $gtp)
    {
        $this->_gtp = $gtp;
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

        if ($id) {
            $tournes = $this->_gtp->find($id);
            $this->_do_tourney($tournes);
        } else {
            $tournes = $this->_gtp->get_playing();
            foreach ($tournes as $tourney) {
                $this->_do_tourney($tourney);
            }
        }
    }

    private function _do_tourney($tourney)
    {
        $prematureLocks = [];
        $regularLocks = [];
        $teeTime = "N/A";
        //do we have an active tourney
        if ($tourney) {
            //grab that tee
            $teeTime = $this->_gtp->get_first_tee_time($tourney);
            //cycle golfs
            foreach ($tourney->golfs as $golf) {
                if (!$golf->entry_lock) { //DO NOT LOCKED LOCKED ENTRIES
                    //if there is time set to lock and it's past that time
                    if ($golf->locks_at) {
                        if ($this->_is_past_custom_lock_time($golf->locks_at)) {
                            $prematureLocks[] = $golf->pool->pool_name . " - <span class='color:darkgreen;'>" . date('F j, Y G:i A', strtotime($golf->locks_at)) . "</span>";
                            $this->_lock_pickable($golf);
                        }
                    } else { //check if started
                        if ($tourney->teed_off) {
                            try {
                                $regularLocks[] = $golf->pool->pool_name . " - <span class='color:darkgreen;'>LOCKED AT "
                                    . date('F j, Y H:i:s', $teeTime)
                                    . "</span>";
                            } catch (\Exception $e) {
                                __dlog($e);
                            }
                            $this->_lock_pickable($golf);
                        }
                    }
                }
            }
        }

        if (count($prematureLocks)) {
            $message = "<p>Following Golfs Were Locked At Custom Times</p><ul>";
            foreach ($prematureLocks as $pl) {
                $message .= "<li>" . $pl . "</li>";
            }
            $message .= "</ul><p>Only commissioners now can edit those pools until next full lock.</p>";
            $subject = $tourney->name . " POOLS LOCKED AT CUSTOM";
            UserNotificationHelper::send_admin($subject, $message);
        }
        if (count($regularLocks)) {
            $message = "<p>Following Golfs Were Locked At Tee Off</p><ul>";
            foreach ($regularLocks as $rl) {
                $message .= "<li>" . $rl . "</li>";
            }
            $message .= "</ul><p>Only commissioners now can edit those pools until next full lock.</p>";
            $subject = $tourney->name . " POOLS LOCKED AT TEE TIME: " . date('F j, Y, G:i A', $teeTime);
            UserNotificationHelper::send_admin($subject, $message);
        }
    }

    private function _is_past_custom_lock_time($locksAt)
    {
        $locksAtTimestamp = strtotime($locksAt);
        if ($locksAtTimestamp && date('U') > $locksAtTimestamp) {
            return true;
        }
        return false;
    }

    private function _lock_pickable(&$golf)
    {
        $golf->entry_lock = true;
        // $golf->locked = true;
        $golf->save();

        // send email ----------------------------------------
        $content = 'Picking is now locked for <b>' . $golf->pool->pool_name . '</b> Click the links below to view the picks in your pool.<br/><br/>';
        $userDetachEmailData = [
            'pool' => $golf->pool,
            'golf' => $golf,
            'content' => $content
        ];
        $sendGolf = new SendGolf('locked', 'Pool Data now available', $userDetachEmailData);
        foreach ($golf->pool->users as $user) {
            $sendGolf->send_golf_email($user->email);
        }
        // send email -----------------------------------------

    }
}
