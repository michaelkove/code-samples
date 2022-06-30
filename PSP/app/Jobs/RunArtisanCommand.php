<?php

namespace App\Jobs;

use App\Mail\AdminNotice;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class RunArtisanCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $commandString;

    /**
     * RunArtisanCommand constructor.
     * @param $commandString
     */
    public function __construct($commandString, $params = [])
    {
        $this->commandString = $commandString;
        $this->params = $params;
    }


    public function handle()
    {
        // $toEmail = __conf('system.send_system_notices_to_email', 'text', "mkovalch@gmail.com");
//        $toEmail =  "mkovalch@gmail.com";
        $dataString = "";
	
        foreach ($this->params as $par => $val) {
            $dataString .= "<br>" . $par . "<strong>" . $val . "</strong>";
        }

        // $startNotices = new AdminNotice("Command " . $this->commandString . " STARTED...", "params " . $dataString);
        // $startNotices->send_admin_email($toEmail);
//	    Artisan::call('email:send', [
//		    'user' => 1, '--queue' => 'default'
//	    ]);
        $exitCode = Artisan::call($this->commandString, $this->params);

        // $endNotices = new AdminNotice("Command " . $this->commandString . " FINISHED:", "With exist code \n" . json_encode($exitCode));
        // $endNotices->send_admin_email($toEmail);
    }
}
