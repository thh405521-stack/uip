<?php

namespace App\Services;

use App\Models\Project;
use App\Models\SavedDashboard;
use App\Models\University;
use App\Models\User;
use App\Models\DataExport;
use Illuminate\Support\Facades\DB;

/**
 * Every number this returns is a real query against the seeded tables —
 * nothing here is hardcoded. Shape matches exactly what
 * DataAnalysisDashboard.jsx expects from GET /api/v1/data-analysis/dashboard.
 */
class DataAnalysisDashboardService
{
    private const MONTH_LABELS = [
        1 => ['en' => 'Jan', 'ar' => 'يناير'], 2 => ['en' => 'Feb', 'ar' => 'فبراير'],
        3 => ['en' => 'Mar', 'ar' => 'مارس'], 4 => ['en' => 'Apr', 'ar' => 'أبريل'],
        5 => ['en' => 'May', 'ar' => 'مايو'], 6 => ['en' => 'Jun', 'ar' => 'يونيو'],
        7 => ['en' => 'Jul', 'ar' => 'يوليو'], 8 => ['en' => 'Aug', 'ar' => 'أغسطس'],
        9 => ['en' => 'Sep', 'ar' => 'سبتمبر'], 10 => ['en' => 'Oct', 'ar' => 'أكتوبر'],
        11 => ['en' => 'Nov', 'ar' => 'نوفمبر'], 12 => ['en' => 'Dec', 'ar' => 'ديسمبر'],
    ];

    public function overview(): array
    {
        return [
            'kpis' => $this->kpis(),
            'user_growth' => $this->userGrowthSeries(6),
            'users_by_role' => $this->usersByRoleSeries(),
            'category_dist' => $this->categoryDistribution(8),
            'leaderboard' => $this->universityLeaderboard(5),
            'trends' => $this->categoryGrowthTrends(30, 6),
            'university_map' => $this->universityGrowthMap(),
        ];
    }

    private function kpis(): array
    {
        $published = Project::where('status', 'published')->count();
        $rejected = Project::where('status', 'rejected')->count();
        $decided = $published + $rejected;

        return [
            'total_users' => User::count(),
            'new_users_this_month' => User::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'verified_universities' => University::where('verification_status', 'verified')->count(),
            'approval_rate' => $decided > 0 ? (int) round($published / $decided * 100) : 0,
        ];
    }

    private function userGrowthSeries(int $months): array
    {
        $rows = User::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
            ->where('created_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $rows->map(function ($row) {
            $monthNum = (int) substr($row->month, 5, 2);
            return [
                'label' => self::MONTH_LABELS[$monthNum] ?? ['en' => $row->month, 'ar' => $row->month],
                'value' => (int) $row->total,
            ];
        })->all();
    }

    private function usersByRoleSeries(): array
    {
        $rows = DB::table('roles')
            ->join('user_roles', 'user_roles.role_id', '=', 'roles.id')
            ->select('roles.name_en', 'roles.name_ar', DB::raw('COUNT(*) as total'))
            ->groupBy('roles.id', 'roles.name_en', 'roles.name_ar')
            ->get();

        return $rows->map(fn ($r) => [
            'label' => ['en' => $r->name_en, 'ar' => $r->name_ar],
            'value' => (int) $r->total,
        ])->all();
    }

    private function universityLeaderboard(int $limit): array
    {
        $rows = University::query()
            ->withCount('projects')
            ->orderByDesc('projects_count')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($u) => [
            'id' => $u->id,
            'university' => ['en' => $u->name_en, 'ar' => $u->name_ar],
            'projects' => $u->projects_count,
        ])->all();
    }

    private function categoryDistribution(int $limit): array
    {
        $rows = Project::query()
            ->where('status', '!=', 'draft')
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'label' => ['en' => $r->category, 'ar' => $r->category],
            'value' => (int) $r->total,
        ])->all();
    }

    /** Real period-over-period growth: last $days vs. the $days before that, per category. */
    private function categoryGrowthTrends(int $days, int $limit): array
    {
        $now = now();
        $currentStart = $now->copy()->subDays($days);
        $previousStart = $now->copy()->subDays($days * 2);

        $categories = Project::query()->distinct()->pluck('category');

        $rows = $categories->map(function ($category) use ($currentStart, $previousStart, $now) {
            $current = Project::where('category', $category)
                ->whereBetween('created_at', [$currentStart, $now])
                ->count();
            $previous = Project::where('category', $category)
                ->whereBetween('created_at', [$previousStart, $currentStart])
                ->count();

            $growthPct = $previous > 0
                ? (int) round((($current - $previous) / $previous) * 100)
                : ($current > 0 ? null : 0);

            return [
                'label' => ['en' => $category, 'ar' => $category],
                'current' => $current,
                'previous' => $previous,
                'growth_pct' => $growthPct,
            ];
        });

        return $rows->sortByDesc(fn ($r) => $r['growth_pct'] ?? -1000)->take($limit)->values()->all();
    }

    private function universityGrowthMap(): array
    {
        $now = now();
        $currentStart = $now->copy()->subDays(30);
        $previousStart = $now->copy()->subDays(60);

        $map = [];
        foreach (University::all() as $university) {
            $current = Project::where('university_id', $university->id)
                ->whereBetween('created_at', [$currentStart, $now])
                ->count();
            $previous = Project::where('university_id', $university->id)
                ->whereBetween('created_at', [$previousStart, $currentStart])
                ->count();

            $growthPct = $previous > 0
                ? (int) round((($current - $previous) / $previous) * 100)
                : ($current > 0 ? null : 0);

            $map[$university->id] = [
                'current' => $current,
                'previous' => $previous,
                'growth_pct' => $growthPct,
            ];
        }

        return $map;
    }

    public function savedDashboardsFor(int $userId): array
    {
        return SavedDashboard::where('user_id', $userId)
            ->get(['id', 'name', 'is_default'])
            ->toArray();
    }

    public function recentExportsFor(int $userId): array
    {
        return DataExport::where('user_id', $userId)
            ->latest()
            ->limit(5)
            ->get(['export_type', 'status'])
            ->toArray();
    }
}
