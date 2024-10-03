<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Http;
use App\Models\Stand;
use App\Models\StandElement;
use Illuminate\Support\Collection;

class StandController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    const ELIGIBLE_YEARS = 10;
    const PINE = "MA";

    private string $baseUrl = "https://register.metsad.ee/portaal/api/rest/eraldis";

    public function getEligibleStands(string $catastralNbr) {
        $stands = $this->getStands($catastralNbr);
        $total = $stands->count();
        $eligibleNow = $stands->where("yearsToLogging", "<=", 0)->count();
        $result = [
            "totalCount" => $total,
            "eligibleNow" => $eligibleNow,
            "eligibleInFuture" => $total - $eligibleNow,
            "stands" => $stands->sortBy([
                ["yearsToLogging", "asc"],
                ["area", "desc"]
            ])->values()->all(),
        ];
        return $result;
    }

    private function getStands(string $catastralNbr) {
        $url = $this->baseUrl . "/otsi?katastriNr='$catastralNbr'";
        $standsHeaders = Http::get($url)->collect();
        
        $stands = collect();

        foreach($standsHeaders as $standHeader) {
            if($standHeader["peapuuliik"] == self::PINE) {
                $url = $this->baseUrl . "/detail/" . $standHeader["id"];
                $standDetails = Http::get($url)->object();
    
                $stand = new Stand();
                $stand->id = $standDetails->id;
                $stand->catastralNbr = $standDetails->katastriNr;
                $stand->quarterNbr = $standDetails->kvartaliNr ?? null;
                $stand->standNbr = $standDetails->eraldiseNr;
                $stand->area = $standDetails->pindala;
                $stand->bonityCode = $standDetails->boniteediKood;
                $stand->age = $standDetails->keskmVanus ?? null;
                $stand->loggingAge = $standDetails->keskmRaievanus ?? null;
                $stand->diameter = $standDetails->keskmDiameeter ?? null;
                $stand->height = $standDetails->korgus ?? null;
                $stand->elements = $standDetails->elemendid;
                $stand->geoJson = json_decode($standDetails->alaGeoJson, true);

                $this->calculateEligibility($stand);
    
                if($stand->eligibility) $stands->push($stand);
            }
        }

        return $stands;
    }

    private function calculateEligibility(Stand &$stand) {
        if($stand->age && $stand->loggingAge) {

            $yearsToLogging = $stand->loggingAge - $stand->age;
            $yearsToLoggingAbs = abs($yearsToLogging);

            if($yearsToLoggingAbs >= 0 && $yearsToLoggingAbs <= self::ELIGIBLE_YEARS) {
                $stand->yearsToLogging = $yearsToLogging;
                if($yearsToLogging <= 0 && $yearsToLogging >= -self::ELIGIBLE_YEARS) {
                    $stand->eligibility = "Now";
                } else if($yearsToLogging > 0 && $yearsToLogging <= self::ELIGIBLE_YEARS) {
                    $stand->eligibility = "In $yearsToLogging years";
                }
            }
        }
    }

}