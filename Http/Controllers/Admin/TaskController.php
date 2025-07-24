<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskAttachment;
use App\Models\Department;
use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::with(['creator', 'assignee', 'department'])
            ->where(function($query) {
                $query->where('created_by', Auth::id())
                      ->orWhere('assigned_to', Auth::id());
            })
            ->latest()
            ->paginate(10);

        return view('admin.tasks.index', compact('tasks'));
    }

    public function create()
    {
        $departments = Department::active()->get();
        $staff = Admin::active()->get();

        return view('admin.tasks.create', compact('departments', 'staff'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'assigned_to' => 'required|exists:admins,id',
            'priority' => 'required|in:low,medium,high,critical',
            'due_date' => 'required|date|after:today',
            'attachments.*' => 'nullable|file|max:10240' // 10MB max
        ]);

        $task = Task::create([
            'created_by' => Auth::id(),
            'assigned_to' => $request->assigned_to,
            'department_id' => $request->department_id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'status' => 'pending'
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task_attachments','public');

                TaskAttachment::create([
                    'task_id' => $task->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize()
                ]);
            }
        }

        Alert::success('Success', 'Task created successfully');
        return redirect()->route('admin.tasks.index');
    }

    public function show(Task $task)
    {
        if ($task->created_by !== Auth::id() && $task->assigned_to !== Auth::id()) {
            abort(403);
        }

        return view('admin.tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        if ($task->created_by !== Auth::id()) {
            abort(403);
        }

        $departments = Department::active()->get();
        $staff = Admin::active()->get();

        return view('admin.tasks.edit', compact('task', 'departments', 'staff'));
    }

    public function update(Request $request, Task $task)
    {
        if ($task->created_by !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'assigned_to' => 'required|exists:admins,id',
            'priority' => 'required|in:low,medium,high,critical',
            'due_date' => 'required|date|after:today',
            'attachments.*' => 'nullable|file|max:10240'
        ]);

        $task->update([
            'assigned_to' => $request->assigned_to,
            'department_id' => $request->department_id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'due_date' => $request->due_date
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task_attachments');

                TaskAttachment::create([
                    'task_id' => $task->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize()
                ]);
            }
        }

        Alert::success('Success', 'Task updated successfully');
        return redirect()->route('admin.tasks.index');
    }

    public function destroy(Task $task)
    {
        if ($task->created_by !== Auth::id()) {
            abort(403);
        }

        // Delete attachments
        foreach ($task->attachments as $attachment) {
            Storage::delete($attachment->file_path);
            $attachment->delete();
        }

        // Delete comments
        $task->comments()->delete();

        $task->delete();

        Alert::success('Success', 'Task deleted successfully');
        return back();
    }

    public function updateStatus(Request $request, Task $task)
    {
        if ($task->assigned_to !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled'
        ]);

        $task->update([
            'status' => $request->status,
            'completed_at' => $request->status === 'completed' ? now() : null
        ]);

        Alert::success('Success', 'Task status updated successfully');
        return back();
    }

    public function addComment(Request $request, Task $task)
    {
        $request->validate([
            'comment' => 'required|string'
        ]);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'comment' => $request->comment
        ]);

        Alert::success('Success', 'Comment added successfully');
        return back();
    }

    public function downloadAttachment(TaskAttachment $attachment)
    {
        if ($attachment->task->created_by !== Auth::id() && $attachment->task->assigned_to !== Auth::id()) {
            abort(403);
        }

        return Storage::download($attachment->file_path, $attachment->file_name);
    }




    public function deleteAttachment(TaskAttachment $attachment)
{
    if ($attachment->task->created_by !== Auth::id()) {
        abort(403);
    }

    Storage::delete($attachment->file_path);
    $attachment->delete();

    return response()->json(['success' => true]);
}


}
