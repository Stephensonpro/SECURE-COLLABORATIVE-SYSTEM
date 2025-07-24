<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class MessageController extends Controller
{
    public function index()
    {
        $messages = Auth::user()->receivedMessages()
            ->with(['sender', 'attachments'])
            ->latest()
            ->paginate(10);

        return view('admin.messages.index', compact('messages'));
    }

    public function sent()
    {
        $messages = Message::where('sender_id', Auth::id())
            ->with(['recipients', 'attachments'])
            ->latest()
            ->paginate(10);

        return view('admin.messages.sent', compact('messages'));
    }

    public function create()
    {
        $recipients = Admin::where('id', '!=', Auth::id())->get();
        return view('admin.messages.create', compact('recipients'));
    }




    public function store(Request $request)
    {
        $request->validate([
            'recipients' => 'required|array',
            'recipients.*' => 'exists:admins,id',
            'content' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240' // 10MB max
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'content' => $request->content
        ]);

        $message->recipients()->sync($request->recipients);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('message_attachments','public');

                MessageAttachment::create([
                    'message_id' => $message->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize()
                ]);
            }
        }

        return redirect()->route('admin.messages.index')
            ->with('success', 'Message sent successfully');
    }

    public function destroy(Message $message)
    {
        // Only sender or recipient can delete
        if ($message->sender_id !== Auth::id() && !$message->recipients->contains(Auth::id())) {
            abort(403);
        }

        // Delete attachments
        foreach ($message->attachments as $attachment) {
            Storage::delete($attachment->file_path);
            $attachment->delete();
        }

        $message->delete();

        return back()->with('success', 'Message deleted successfully');
    }

 



    public function show(Message $message)
    {
        // Mark as read if recipient
        if ($message->recipients->contains(Auth::id())) {
            $message->recipients()->updateExistingPivot(Auth::id(), [
                'is_read' => true,
                'read_at' => now()
            ]);
        }

        return view('admin.messages.show', compact('message'));
    }



    public function markAsRead(Message $message)
    {
        $message->recipients()->updateExistingPivot(Auth::id(), [
            'is_read' => true,
            'read_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

public function downloadAttachment(MessageAttachment $attachment)
{
    $attachment->load(['message.sender', 'message.recipients']);

    if (!Auth::user()->canViewAttachment($attachment)) {
        abort(403);
    }

    return Storage::download($attachment->file_path, $attachment->file_name);
}
}
