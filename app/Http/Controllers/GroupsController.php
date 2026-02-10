<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Site;
use App\Services\GroupMetricsService;
use Illuminate\Support\Collection;

class GroupsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, GroupMetricsService $metricsService)
    {
        $user = $request->user();
        $canViewAllSites = session('is_super_admin', (int) ($user?->cliente_id ?? -1) === 0);

        $sites = $this->resolveSites($user, $canViewAllSites);
        $siteIds = $sites->pluck('site_id')->map(fn ($id) => (string) $id)->all();

        $requestedSite = $request->query('site_id');
        $selectedSiteId = null;
        if ($requestedSite !== null && in_array((string) $requestedSite, $siteIds, true)) {
            $selectedSiteId = (int) $requestedSite;
        } elseif ($sites->count() > 0) {
            $selectedSiteId = (int) $sites->first()->site_id;
        }

        $metrics = $selectedSiteId
            ? $metricsService->getMonthlyMetrics($selectedSiteId)
            : $metricsService->emptyMetrics();

        $periodLabel = $metricsService->periodLabel();

        return view('groups.index', [
            'sites' => $sites,
            'selectedSiteId' => $selectedSiteId,
            'canViewAllSites' => $canViewAllSites,
            'metrics' => $metrics,
            'periodLabel' => $periodLabel,
        ]);

    }

    private function resolveSites($user, bool $canViewAllSites): Collection
    {
        if ($canViewAllSites) {
            return Site::query()
                ->orderBy('site_name')
                ->get(['site_id', 'site_name']);
        }

        $fallbackSite = session('site') ?? $user?->siteId();
        if ($fallbackSite) {
            $site = Site::query()
                ->where('site_id', $fallbackSite)
                ->first(['site_id', 'site_name']);
            if ($site) {
                return collect([$site]);
            }
            return collect([(object) [
                'site_id' => $fallbackSite,
                'site_name' => "Sitio {$fallbackSite}",
            ]]);
        }

        return collect();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
