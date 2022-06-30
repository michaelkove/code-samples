<?php

namespace App\Http\Controllers\PspFrontend;

use App\Helpers\SiteHelper;
use App\Models\BillingPaymentProfile;
use App\Http\Controllers\Controller;
use App\Models\UserBilling;
use App\Providers\Pool\Pool as PoolProvider;
use App\Providers\User\BillingPaymentProfileProvider;
use App\Providers\User\UserBillingProvider;
use Illuminate\Http\Request;

class BillingPaymentProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();
        $billingProfiles = [];
        try {
            $user->load(['billings', 'billings.payment_profiles']);
            foreach ($user->billings as $billing) {
                foreach ($billing->payment_profiles as $pf) {
                    if ($pf->flag === 'clean') {
                        $billingProfiles[] = $pf;
                    }
                }
            }
            $billingProfiles = collect($billingProfiles);
        } catch (\Exception $e) {
            \Log::error("BillingPaymentProfileController index: " . $e->getMessage());
        }
        return view('pspfrontend.user.billing.index', ['user' => $user, 'billingProfiles' => $billingProfiles]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = auth()->user();
        $user->load(['billings', 'billings.payment_profiles']);
        SiteHelper::set_help($this->help_title, $this->help_content, 'profiles', 'billing');
        $help_title = $this->help_title;
        $help_content = $this->help_content;
        return view('pspfrontend.user.billing.create', compact('user', 'help_content', 'help_title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $saveCardFromRequest = $request->get('save_card');

        $saveCard = ($request->has('save_card'));
        $sendTo = $request->get('sendTo');
        $poolId = $request->get('pool_id');
        $user = auth()->user();
        $provider = $request->get('provider') ?? 'authorize_net';

        try {
            $user->load(['billings', 'billings.payment_profiles']);
            $userBilling = ($user->billings->count()) ? $user->billings->where('provider', $provider)->first() : null;
            $userBillingProvider = resolve(UserBillingProvider::class);
            if (!$userBilling) {
                $userBillingCreateData = [
                    'provider' => $provider,
                    'user_id' => $user->id,
                    'note' => "Created by adding CC - Front End: " . date('Y-m-d H:i:s'),
                ];
                $userBilling = $userBillingProvider->create($userBillingCreateData);
            }
            $defaultPayment = $userBillingProvider->get_default($userBilling);

            $parts = $request->only('cc1', 'cc2', 'cc3', 'cc4');
            $creditCardNumber =  (__conf('authorizeNet.settings.live_environment', 'boolean', false)) ? implode($parts) : '4007000000027'; //FAKE CARD FOR TEST
            $paymentProfileData = $request->except('_token', 'cc1', 'cc2', 'cc3', 'cc4');
            $paymentProfileData['cc_number'] = $creditCardNumber;
            $paymentProfileData['flag'] = ($saveCard) ? 'clean' : 'temp';
            $paymentProfileData['default'] = ($defaultPayment === null); // only set if default doesn't exist
            $paymentProfileProvider = resolve(BillingPaymentProfileProvider::class);
            $paymentProfile = $paymentProfileProvider->create($userBilling->customer_profile_id, $userBilling->id, $paymentProfileData);

            if ($poolId && $paymentProfile && $user->is_commissioner($poolId)) {
                $poolProvider = resolve(PoolProvider::class);
                $poolProvider->update_payment_profile($poolId, $paymentProfile->id);
            }
        } catch (\Exception $e) {
            \Log::error("BillingPaymentProfileController store :" . $e->getMessage());
        }
        return redirect($sendTo);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\BillingPaymentProfile  $billingPaymentProfile
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BillingPaymentProfile $billingPaymentProfile)
    {
        //
        try {
            $user = auth()->user();
            $provider = $request->get('provider') ?? 'authorize_net';

            $user->load(['billings', 'billings.payment_profiles']);
            $userBilling = ($user->billings->count()) ? $user->billings->where('provider', $provider)->first() : null;
            $paymentProfileProvider = resolve(BillingPaymentProfileProvider::class);
            if ($userBilling) {
                foreach ($userBilling->payment_profiles as $paymentProfile) {
                    $data['default'] = ($paymentProfile->id === $billingPaymentProfile) ? $request->get('default') : false;
                    $paymentProfileProvider->update($paymentProfile, $data);
                }
            }
        } catch (\Exception $e) {
            \Log::error("BillingPaymentProfileController update:" . $e->getMessage());
        }

        return redirect()->route('psp.user.billing.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\BillingPaymentProfile  $billingPaymentProfile
     * @return \Illuminate\Http\Response
     */
    public function destroy(BillingPaymentProfile $billingPaymentProfile)
    {
        try {
            $user = auth()->user();
            $billingPaymentProfile->load(['user_billing']);
            $userBilling = $billingPaymentProfile->user_billing;

            if ($userBilling->user_id === $user->id) {
                $paymentProfileProvider = resolve(BillingPaymentProfileProvider::class);
                $paymentProfileProvider->delete($billingPaymentProfile);
            }
        } catch (\Exception $e) {
            \Log::error("BillingPaymentProfileController destory:" . $e->getMessage());
        }
        return redirect()->route('psp.user.billing.index');
    }
}
