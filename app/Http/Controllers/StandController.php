<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\Services\StandService;

class StandController extends BaseController
{

    public function getEligibleStands(string $catastralNbr) {
        $standService = new StandService();

        $stands = $standService->getEligibleStands($catastralNbr);
        return $stands;
    }

}