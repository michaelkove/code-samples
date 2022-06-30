<?php

namespace App\Console\Commands\Play\Golf;

use App\Helpers\UserNotificationHelper;
use App\Providers\Pool\Golf\GolfProvider;
use App\Providers\Pool\Golf\GolfTournamentProvider;
use Illuminate\Console\Command;


class GolfMasterLockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'play:lock-golf-master {id?}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lock golf for commissioners.';

    private $_gtp;
    private $_gp;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GolfTournamentProvider $gtp, GolfProvider $gp)
    {
        $this->_gtp = $gtp;
        $this->_gp = $gp;
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
        } else {
            $tournes = $this->_gtp->get_current();
        }

        //TODO: @Jovan
        /*
	     * this used to run Friday at midnight, change Kernel.php to make it run Thur, Fri and see if start_date + 1 day. If it is older - lock it.
	     * DO NOT run if pool is already locked. See Golf.php model for expl.
	     *
	     */
        //do we have an active tourney
        if ($tournes) {
            $tournes = $tournes->load(['golfs', 'golfs.pool']);
            $message = "On " . date('F j, Y G:i A') . " Following Golfs Were LOCKED for commissioner edit: <ul>";
            $lockCount = 0;

            foreach ($tournes->golfs as $golf) {
                if (!$golf->locked) {
                    $current_date = date_create(date('Y-m-d'));
                    $start_date = date_create($tournes->start_date);
                    $diff = date_diff($start_date, $current_date);
                    if (intval($diff->format("%R%a")) == 1) {
                        $lockCount++;
                        $this->_gp->update($golf->id, ['locked' => true]);
                        $message .= "<li><strong>{$golf->pool->pool_name}</strong> - <span style='color:darkgreen;'>{$golf->entries->count()}</span> Entires</li>";
                    }
                }
            }
            if ($lockCount > 0) {
                $message .= "</ul><p>Commissioners are <strong style='color:red;'>UNABLE</strong> to make changes now</p>";
                $subject = "Tourney " . $tournes->name . " Locked";
                UserNotificationHelper::send_admin($subject, $message);
            }
        }
    }
}
