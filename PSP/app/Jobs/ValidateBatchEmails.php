<?php
	
	namespace App\Jobs;
	
	use App\Helpers\SiteHelper;
	use App\Helpers\UserNotificationHelper;
	use App\Models\User;
	use App\Providers\ZeroBounce\ZeroBounceProvider;
	use Illuminate\Bus\Queueable;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Support\Facades\DB;
	
	class ValidateBatchEmails implements ShouldQueue
	{
		use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
		
		
		public $override = false;
		private $_enabled = false;
		public $tries = 1;
		public $timeout = 10800; 
		
		private $_message = "";
		private $_subject = "Batch email";
		private $_jobid;
		
		public function __construct($override = false)
		{
			$this->_message = "";
			$this->override = $override;
			$this->_enabled = __conf('zerobounce.enable_verification','boolean', false);
			
			
		}
		
		public function handle()
		{
			$this->_jobid = date('U')."-".rand(1,9999);
			$zeroBounceValidator = new ZeroBounceProvider();
			
			$batch = 0;
			$queryBuilder = ($this->override) ?
				DB::table('users')->orderBy('id')->where('status','active') :
				DB::table('users')->orderBy('id')->where('validated_at',null)->where('status','active');
			$sub =($this->_enabled) ? "" : "[DRY RUN]";
			$this->_subject = "EMAIL VERIFICATION STARTED (ID: {$this->_jobid}) [".date('F-j, Y G:i a')."] {$sub}";
			$s = ($this->override) ? "ON" : "OFF";
			$this->_message = "Job dispatched at ".date('F j, Y G:i a')." Chunking emails by 100. Overriding is ".$s;
			$this->_notify_admin();
			$validationResponse = [];
			$this->_message = "";
			$counts = [
				'total' => 0,
				'invalid' => 0,
				'valid' => 0,
				'questionable' => 0,
			];
			try{
				$queryBuilder->chunk(100, function ($users) use ($zeroBounceValidator, $batch, $counts) {
					try{
						$emails = [];
						foreach ($users as $user) {
							$counts['total']++;
							$batch++;
							$emails[] = $user->email;
						}
						$this->_message .= "\n\r<br> Batching ".count($emails)." items sleeping 20 seconds...\n\r<br>";
						try {
							if($this->_enabled){
								sleep(25);
								$validationResponse = $zeroBounceValidator->validate_batch($emails);
							}
						} catch (\Exception $e){
							\Log::error($e);
							$this->_message .= "\n\r<br> API ERROR: ".$e->getMessage();
						}
						if(isset($validationResponse['email_batch'])){
							foreach($validationResponse['email_batch'] as $emailObject){
								
								try{
									if($this->_enabled) {
										$emailAddress = $emailObject['address'];
										$updateData = $zeroBounceValidator->build_update_data($emailObject);
										$res = User::where('email', $emailAddress)->first()->update($updateData);
										if($updateData['email_valid']){
											$counts['valid']++;
											if($updateData['email_status'] === 'catch-all'){
												$counts['questionable']++;
											}
										} else {
											$counts['invalid']++;
										}
										
									}
								} catch (\Exception $e){
									\Log::error($e);
									$this->_message .= "\n\r<br> ERR: ".$e->getMessage();
								}
							}
						} else {
							$this->_message .= "\n\r<br>API DID NOT RUN: Possibly disabled.";
						}
					} catch (\Exception $e){
						\Log::error($e);
						$this->_message .= "\n\r<br>Batch Error: ".$e->getMessage();
					}
					
				});
			} catch (\Exception $e){
				\Log::error($e);
				$this->_message .= "\n\r<br>Query Error: ".$e->getMessage();
			}
			
			$this->_subject = "BATCH EMAIL FINISHED  (ID: {$this->_jobid})";
			$this->_message .= "\n\r<br> Validated ".$batch." emails";
			$this->_message .= "<br> STATS: <br>".
				"<br>VALID: <strong style=''color:green;>".$counts['valid']."</strong>".
				"<br>BAD: <strong style=''color:red;>".$counts['invalid']."</strong>".
				"<br>MAYBE: <strong style=''color:orange;>".$counts['questionable']."</strong>";
				
			$this->_notify_admin();
			
		}
		
		public function failed(\Throwable $exception)
		{
			$this->_subject = "BATCH EMAIL FAILED  (ID: {$this->_jobid})";
			$this->_message .= "Job Failed: ".$exception->getMessage().nl2br($exception->getTraceAsString());
			$this->_notify_admin();
		}
		
		private function _notify_admin()
		{
			if (SiteHelper::conf('system.batch_run_notify', 'boolean', 1)) {
				
				UserNotificationHelper::send_admin($this->_subject, $this->_message ,['mkovalch@gmail.com']);
			}
		}
	}