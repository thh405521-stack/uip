<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DataAnalysisDashboardService;
use Illuminate\Http\Request;

class DataAnalysisDashboardApiController extends Controller
{
    public function __construct(private DataAnalysisDashboardService $dashboard)
    {
    }

    /** GET /api/v1/data-analysis/dashboard */
    public function index(Request $request)
    {
        if (!in_array($request->attributes->get('uip_role'), ['data_analyst', 'admin'], true)) {
            return $this->apiError('Only Data Analysis Portal accounts can view this dashboard.', null, 403);
        }

        $userId = $request->attributes->get('uip_user_id');

        return $this->apiSuccess(array_merge(
            $this->dashboard->overview(),
            [
                'saved_dashboards' => $this->dashboard->savedDashboardsFor($userId),
                'recent_exports' => $this->dashboard->recentExportsFor($userId),
            ]
        ), 'Dashboard data retrieved successfully.');
    }
}
