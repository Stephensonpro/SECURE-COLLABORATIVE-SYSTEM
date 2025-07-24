<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use Carbon\Carbon;

class StaffDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::guard('staff')->user();

        // Update last login time
        $user->update(['last_login_at' => now()]);

        // Get complaint statistics and recent complaints
        $stats = $this->getComplaintStats($user);
        $recentComplaints = $this->getRecentComplaints($user);
        $categoryStats = $this->getCategoryStats($user);

        return view('staff.dashboard', [
            'stats' => $stats,
            'recentComplaints' => $recentComplaints,
            'notifications' => $user->unreadNotifications()->latest()->limit(5)->get(),
            'categoryStats' => $categoryStats,
            'statusDistribution' => $this->getStatusDistribution($stats)
        ]);
    }

    protected function getComplaintStats($user)
    {
        $query = $this->baseComplaintQuery($user);

        return [
            'total' => $query->count(),
            'pending' => $query->clone()->where('status', 'pending')->count(),
            'in_progress' => $query->clone()->where('status', 'in_progress')->count(),
            'resolved' => $query->clone()->where('status', 'resolved')->count(),
            'rejected' => $query->clone()->where('status', 'rejected')->count(),
            'today' => $query->clone()->whereDate('created_at', today())->count(),
            'week' => $query->clone()->whereBetween('created_at',
                [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
    }

    protected function getRecentComplaints($user)
    {
        return $this->baseComplaintQuery($user)
            ->with(['category', 'passenger'])
            ->latest()
            ->limit(5)
            ->get();
    }

    protected function getCategoryStats($user)
    {
        return ComplaintCategory::withCount([
            'complaints' => function($query) use ($user) {
                $this->applyUserFilters($query, $user);
            }
        ])
        ->orderBy('complaints_count', 'desc')
        ->limit(5)
        ->get();
    }

    protected function getStatusDistribution($stats)
    {
        $total = $stats['total'] ?: 1; // Avoid division by zero
        return [
            'pending' => round(($stats['pending'] / $total) * 100, 2),
            'in_progress' => round(($stats['in_progress'] / $total) * 100, 2),
            'resolved' => round(($stats['resolved'] / $total) * 100, 2),
            'rejected' => round(($stats['rejected'] / $total) * 100, 2)
        ];
    }

    /**
     * Base query with user-specific filters
     */
    protected function baseComplaintQuery($user)
    {
        $query = Complaint::query();
        $this->applyUserFilters($query, $user);
        return $query;
    }

    /**
     * Apply filters based on user type
     */
    protected function applyUserFilters($query, $user)
    {
        if ($user->isAirlineStaff() && $user->airline_id) {
            $query->where('airline_id', $user->airline_id);
        } elseif ($user->isAirportStaff() && $user->airport_id) {
            $query->where('airport_id', $user->airport_id);
        }

        // If no specific association, show all complaints (like in StaffComplaintController)
        return $query;
    }
}
