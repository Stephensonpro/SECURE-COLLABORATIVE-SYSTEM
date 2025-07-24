<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with(['user', 'departments'])
            ->latest()
            ->paginate(10);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        $departments = Department::active()->get();
        return view('admin.announcements.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'departments' => 'required|array',
            'departments.*' => 'exists:departments,id',
            'is_pinned' => 'sometimes|boolean',
            'expires_at' => 'nullable|date|after:now'
        ]);

        $announcement = Announcement::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'is_pinned' => $request->is_pinned ?? false,
            'expires_at' => $request->expires_at
        ]);

        $announcement->departments()->sync($request->departments);

        // Send notifications
        $announcement->notifyDepartments();

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement created successfully and notifications sent');
    }

    public function show(Announcement $announcement)
    {
        return view('admin.announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement)
    {
        $departments = Department::active()->get();
        $selectedDepartments = $announcement->departments->pluck('id')->toArray();

        return view('admin.announcements.edit', compact('announcement', 'departments', 'selectedDepartments'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'departments' => 'required|array',
            'departments.*' => 'exists:departments,id',
            'is_pinned' => 'sometimes|boolean',
            'expires_at' => 'nullable|date|after:now'
        ]);

        $announcement->update([
            'title' => $request->title,
            'content' => $request->content,
            'is_pinned' => $request->is_pinned ?? false,
            'expires_at' => $request->expires_at
        ]);

        $announcement->departments()->sync($request->departments);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return back()
            ->with('success', 'Announcement deleted successfully');
    }

    public function togglePin(Announcement $announcement)
    {
        $announcement->update(['is_pinned' => !$announcement->is_pinned]);

        $message = $announcement->is_pinned ? 'pinned' : 'unpinned';
        return back()
            ->with('success', "Announcement {$message} successfully");
    }
}
