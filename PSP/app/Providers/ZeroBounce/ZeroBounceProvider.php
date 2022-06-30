<?php
	namespace App\Providers\ZeroBounce;
	use Illuminate\Support\Facades\Http;
	
	class ZeroBounceProvider
	{
		
		private $_apiKey;
		
		public function __construct()
		{
			$this->_apiKey = __conf('zerobounce.api_key','string', NULL);
		}
		
		public function validate_single_email($emailAddress = null){
			$url = 'https://api.zerobounce.net/v2/validate?api_key='.$this->_apiKey.'&email='.urlencode($emailAddress);
			$resposne = $this->_hit_api($url);
			return $resposne;
		}
		
		public function validate_batch($emails = []){
			$url = "https://bulkapi.zerobounce.net/v2/validatebatch";
			$requestData =[
				'api_key' => $this->_apiKey,
				'email_batch' => []
			];
			foreach($emails as $email){
				$requestData['email_batch'][] = ['email_address' => $email];
			}
			$response = $this->_hit_api($url, $requestData, 'POST');
			return $response;
		}
		
		public function build_update_data($emailResponse = []){
			try{
				$vaildTosend = ($emailResponse['status'] === 'valid' || $emailResponse['status'] === 'catch-all');
				
				return [
					'email_valid' => $vaildTosend,
					'email_status' => $emailResponse['status'] ?? null,
					'email_sub_status' => $emailResponse['sub_status'] ?? null,
					'validated_at' => $emailResponse['processed_at'] ?? null,
					'email_firstname' => $emailResponse['firstname'] ?? null,
					'email_lastname' => $emailResponse['lastname'] ?? null,
					'email_gender' => $emailResponse['gender'] ?? null,
					'email_country' => $emailResponse['country'] ?? null,
					'email_region' => $emailResponse['region'] ?? null,
					'email_city' => $emailResponse['city'] ?? null,
					'email_zipcode' => $emailResponse['zipcode'] ?? null,
				];
			} catch (\Exception $e){
				\Log::error($e);
			}
			return [];
			
		}
		
		private function _hit_api($endpoint, $data = [], $method = 'GET')
		{
			$client = new \GuzzleHttp\Client();

			try {
				if($method === 'GET') {
					$response = Http::get($endpoint);
				} elseif($method === 'POST') {
					$response = Http::withHeaders([
						"x-token"=>env('ZEROB_TOKEN', null),
						"Content-Type"=>"application/json",
						"Cookie"=>"__cfduid=DXD"
					])->post($endpoint, $data);
				}
				if($response->ok()){
					return $response->json();
				} else {
					return [];
				}
			} catch (\Exception $e) {
				\Log::error($e);
				return [];
			}
		}
		
		
	}
	
	