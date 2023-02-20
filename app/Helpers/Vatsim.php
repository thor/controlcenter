<?php

namespace App\Helpers\Vatsim;

use Illuminate\Support\Collection;

/**
* Checks if a position belongs to the division.
* @param string callsign
* @param Collection<string> divisionCallsignPrefixes
*/
function isDivisionCallsign(string $callsign, Collection $divisionCallsignPrefixes)
{
    $validAtcSuffixes = ["DEL" => true, "GND" => true, "TWR" => true, "APP" => true, "DEP" => true, "CTR" => true, "FSS" => true];
    // Filter away invalid ATC suffixes
    $suffix = substr($callsign, -3);
    if (!array_key_exists($suffix, $validAtcSuffixes)) {
        return false;
    }

    // PREFIX
    if ($divisionCallsignPrefixes->contains(substr($callsign, 0, 4))) {
        return true;
    }

    return false;
}

?>
