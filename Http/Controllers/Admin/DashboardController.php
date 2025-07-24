<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Task;
use App\Models\Message;
use App\Models\Document;
use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Department and Staff counts
        $departmentCount = Department::count();
        $staffCount = Staff::count();
        $totalDocuments = Document::count();
        $totalTasks = Task::count();


        // Recent staff members
        $recentStaff = Staff::with('department')
            ->latest()
            ->take(5)
            ->get();

        // Departments needing attention
        $departmentsNeedingAttention = Department::with(['hod', 'staff'])
            ->withCount('staff')
            ->where(function($query) {
                $query->where('status', 'inactive')
                      ->orWhereNull('hod_id')
                      ->orHas('staff', '<', 3);
            })
            ->orderBy('status')
            ->orderBy('staff_count')
            ->take(5)
            ->get();

        // Pending tasks for current user
        $pendingTasks = Task::where('assigned_to', Auth::id())
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->take(3)
            ->get();

        // Recent messages
        $recentMessages = Auth::user()->receivedMessages()
            ->with(['sender'])
            ->latest()
            ->take(3)
            ->get();

        // Recent documents
        $recentDocuments = Document::accessibleBy(Auth::user())
            ->latest()
            ->take(3)
            ->get();

        // Recent announcements
        $recentAnnouncements = Announcement::latest()
            ->take(3)
            ->get();

        return view('admin.dashboard.index', [
            'departmentCount' => $departmentCount,
            'staffCount' => $staffCount,
            'recentStaff' => $recentStaff,
            'departmentsNeedingAttention' => $departmentsNeedingAttention,
            'pendingTasks' => $pendingTasks,
            'totalDocuments' => $totalDocuments, // New
            'totalTasks' => $totalTasks, // New
            'recentMessages' => $recentMessages,
            'recentDocuments' => $recentDocuments,
            'recentAnnouncements' => $recentAnnouncements,
        ]);
    }
}
