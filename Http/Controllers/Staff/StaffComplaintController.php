<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\ComplaintStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Notifications\NewComplaintNotification;
use App\Notifications\ComplaintResponseNotification;

class StaffComplaintController extends Controller
{
    /**
     * Display a listing of complaints with filters
     */
    public function index(Request $request)
    {
        $query = Complaint::with(['category', 'user'])
            ->latest();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }


        $complaints = $query->paginate(10);
        $categories = ComplaintCategory::all();

        return view('staff.complaints.index', compact('complaints', 'categories'));
    }

    /**
     * Show the form for creating a new complaint
     */
    public function create()
    {
        $categories = ComplaintCategory::all();
        return view('staff.complaints.create', compact('categories'));
    }

    /**
     * Store a newly created complaint
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:complaint_categories,id',
            'description' => 'required|string|min:10',
            'flight_number' => 'nullable|string|max:20',
            'flight_date' => 'nullable|date',
            'attachments.*' => 'nullable|file|max:2048', // 2MB max per file
        ]);

        // Generate ticket number
        $ticketNumber = 'TKT-' . strtoupper(Str::random(8));

        // Create complaint
        $complaint = auth()->user()->complaints()->create([
            'ticket_number' => $ticketNumber,
            'category_id' => $validated['category_id'],
            'description' => $validated['description'],
            'flight_number' => $validated['flight_number'],
            'flight_date' => $validated['flight_date'],
            'status' => 'pending',
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('complaint-attachments');

                $complaint->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('staff.complaints.show', $complaint->id)
            ->with('success', 'Complaint submitted successfully!');
    }

    /**
     * Display the specified complaint
     */
public function show(Complaint $complaint)
{
    $complaint->load([
        'category',
        'passenger',
        'responses' => function($query) {
            $query->with(['responder' => function($q) {
                $q->withDefault([
                    'name' => 'System'
                ]);
            }]);
        },
        'attachments',
        'statusHistory.changedBy'
    ]);

    return view('staff.complaints.show', compact('complaint'));
}

    /**
     * Show the form for editing the specified complaint
     */
    public function edit(Complaint $complaint)
    {
        if (!in_array($complaint->status, ['pending', 'in_progress'])) {
            return redirect()->back()
                ->with('error', 'Only pending or in-progress complaints can be edited');
        }

        $categories = ComplaintCategory::all();
        return view('staff.complaints.edit', compact('complaint', 'categories'));
    }

    /**
     * Update the specified complaint
     */
    public function update(Request $request, Complaint $complaint)
    {
        if (!in_array($complaint->status, ['pending', 'in_progress'])) {
            return redirect()->back()
                ->with('error', 'Only pending or in-progress complaints can be edited');
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:complaint_categories,id',
            'description' => 'required|string|min:10',
            'flight_number' => 'nullable|string|max:20',
            'flight_date' => 'nullable|date',
            'attachments.*' => 'nullable|file|max:2048',
        ]);

        $complaint->update($validated);

        // Handle new attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('complaint-attachments');

                $complaint->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('staff.complaints.show', $complaint->id)
            ->with('success', 'Complaint updated successfully!');
    }

    /**
     * Store a response to a complaint
     */
 public function storeResponse(Request $request, Complaint $complaint)
{
    $validated = $request->validate([
        'response' => 'required|string',
        'internal' => 'sometimes|boolean',
    ]);

    $response = $complaint->responses()->create([
        'responder_id' => auth()->id(),
        'responder_type' => 'App\\Models\\Staff',
        'response' => $validated['response'],
        'internal' => $validated['internal'] ?? false,
    ]);

    // Only notify if not an internal note
    if (!$response->internal) {
        $complaint->passenger->notify(new ComplaintResponseNotification($complaint, $response));
    }

    return back()->with('success', 'Response added successfully!');
}


    /**
     * Update the status of a complaint
     */


public function updateStatus(Request $request, Complaint $complaint)
{
    $validated = $request->validate([
             'status' => [
                'required',
                'in:pending,in_progress,resolved,rejected',
                function ($attribute, $value, $fail) use ($complaint) {
                    $validTransitions = [
                        'pending' => ['in_progress', 'resolved', 'rejected'],
                        'in_progress' => ['resolved', 'rejected'],
                        'resolved' => [],
                        'rejected' => []
                    ];

                    if (!in_array($value, $validTransitions[$complaint->status])) {
                        $fail('Invalid status transition');
                            }
                        }
                    ],


            'notes' => 'nullable|string',
    ]);

    $previousStatus = $complaint->status;
    $newStatus = $validated['status'];

    // Update complaint status
    $updateData = ['status' => $newStatus];

    if ($newStatus === 'resolved') {
        $updateData['resolved_at'] = now();
    } elseif ($previousStatus === 'resolved' && $newStatus !== 'resolved') {
        $updateData['resolved_at'] = null;
    }

    $complaint->update($updateData);

    // Record status change
    ComplaintStatusHistory::create([
        'complaint_id' => $complaint->id,
        'status' => $newStatus,
        'notes' => $validated['notes'],
        'changed_by' => auth()->id(),
    ]);

    // Notify passenger of status change
    $complaint->passenger->notify(new ComplaintResponseNotification($complaint));

    return back()->with('success', 'Status updated successfully!');
}


    /**
     * Remove an attachment
     */
    public function destroyAttachment(Complaint $complaint, $attachmentId)
    {
        $attachment = $complaint->attachments()->findOrFail($attachmentId);

        // Delete file from storage
        Storage::delete($attachment->file_path);

        // Delete record
        $attachment->delete();

        return back()->with('success', 'Attachment deleted successfully!');
    }
}
