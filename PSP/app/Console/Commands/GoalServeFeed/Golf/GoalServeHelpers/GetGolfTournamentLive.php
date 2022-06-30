<?php

namespace App\Console\Commands\GoalServeFeed\Golf\GoalServeHelpers;

use App\Helpers\GolfHelper;
use App\Helpers\GolfHelper as GH;
use App\Helpers\UserNotificationHelper;
use App\Hooks\System\CriticalErrorLogging;
use App\Models\GolfTournament;
use App\Providers\Pool\Golf\GolfTournamentProvider;
use App\Providers\Pool\Golf\GolfPlayerProvider;
use App\Providers\Pool\Golf\GolfTournamentPlayerProvider;
use App\Providers\Pool\Golf\GolfTournamentPlayerRoundProvider;
use App\Providers\Pool\Golf\GolfTournamentPlayerRoundHoleProvider;

class GetGolfTournamentLive
{

    private $_golfHelper;
    private $_golfTournamentProvider;
    private $_golfPlayerProvider;
    private $_golfTournamentPlayerProvider;
    private $_golfTournamentPlayerRoundProvider;
    private $_golfTournamentPlayerRoundHoleProvider;
    private $_localGolfPlayers;
    private $_localTournamentPlayers;
    private $_localTournament;
    private $_gsTournament;
    private $_gsRounds;
    private $_gsHoles;


    private $_start;

    /**
     * GetGolfLive constructor.
     * @param GH $gh
     * @param GolfTournamentPlayerProvider $gtpp
     * @param GolfPlayerProvider $gpp
     * @param GolfTournamentProvider $gtp
     * @param GolfTournamentPlayerRoundProvider $gtprp
     * @param GolfTournamentPlayerRoundHoleProvider $gtprhp
     */
    public function __construct(
        GH $gh,
        GolfTournamentPlayerProvider $gtpp,
        GolfPlayerProvider $gpp,
        GolfTournamentProvider $gtp,
        GolfTournamentPlayerRoundProvider $gtprp,
        GolfTournamentPlayerRoundHoleProvider $gtprhp,
        CriticalErrorLogging $criticalErrorLogging

    ) {
        $this->_golfHelper = $gh;
        $this->_golfPlayerProvider = $gpp;
        $this->_golfTournamentProvider = $gtp;
        $this->_golfTournamentPlayerProvider = $gtpp;
        $this->_golfTournamentPlayerRoundProvider = $gtprp;
        $this->_golfTournamentPlayerRoundHoleProvider = $gtprhp;
        $this->_start = microtime(true);
        $this->_criticalLog = $criticalErrorLogging;
    }

    /**
     * @return bool
     */
    public function get_tournament_live()
    {
        $this->_fix_bad_tournaments();
        return $this->_get_tournament_live();
    }

    private function _fix_bad_tournaments()
    {
        $alltourneys = GolfTournament::get();
        foreach ($alltourneys as $tourney) {
            $exId = $tourney->external_id;
            if (count(explode('-', $exId)) < 2) {
                //                    \Log::info("Fixing ".$exId);
                $newId = GolfHelper::make_external_id($exId, $tourney->start_date);
                $tourney->external_id = $newId;
                $tourney->save();
            }
        }
    }

    private function _get_tournament_live()
    {
        try {
            $this->_load_local_data();
            if ($this->_localTournament && $this->_gsTournament) {
                return $this->_do_tournament();
            }
        } catch (\Exception $e) {
            \Log::error($e);
        }

        return true;
    }

    private function _load_local_data()
    {
        $this->_gsTournament = $this->_golfHelper->getCurrentTournamentAsArray();
        $externalTournamentId = $this->_gsTournament['external_id'];
        $this->_localTournament = $this->_golfTournamentProvider->get(['external_id' => $externalTournamentId], true)->with(['tournament_players'])->first();
        return true;
    }

    private function _do_tournament()
    {

        $localTournamentData = [
            'country' => $this->_gsTournament['country'],
            'venue' => $this->_gsTournament['venue'],
            'type' => $this->_gsTournament['type'],
            'par' => $this->_gsTournament['par'],
            'gender' => $this->_gsTournament['gender'],
            'active' => ($this->_gsTournament['status'] === 'Final') ? false : true,
            'external_id' => ($this->_gsTournament['status'] === 'Final') ? 'A' . $this->_gsTournament['external_id'] : $this->_gsTournament['external_id'],
        ];

        $buildString = "<h3>" . $this->_gsTournament['name'] . "</h3>" . "<p>STATUS CHANGED FROM <br><strong>" . $this->_localTournament->status . "</strong> TO <strong>" . $this->_gsTournament['status'] . "</strong></p>";
        if ($this->_localTournament->status !== $this->_gsTournament['status']) {
            UserNotificationHelper::send_admin(" STATUS CHANGE " . $this->_localTournament->name . " [" . $this->_gsTournament['status'] . "]", $buildString);
        }

        if ($this->_gsTournament['status'])
            $localTournamentData = array_merge($localTournamentData, ['status' => $this->_gsTournament['status']]);

        return $this->_golfTournamentProvider->update($this->_localTournament->id, $localTournamentData);
    }
}
