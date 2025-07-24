<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentController extends Controller
{
    public function index()
    {
        
        $documents = Document::with(['user', 'permissions'])
            ->where(function($query) {
                $query->whereHas('permissions', function($q) {
                    $q->where('role_id', auth()->user()->roles->pluck('id')->toArray())
                    ->where('can_view', true);
                })->orWhere('user_id', auth()->id());
            })
            ->latest()
            ->paginate(10);

        return view('admin.documents.index', compact('documents'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.documents.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:20480', // 20MB max
            'is_confidential' => 'sometimes|boolean',
            'permissions' => 'required|array',
            'permissions.*.role_id' => 'exists:roles,id',
            'permissions.*.can_view' => 'sometimes|boolean',
            'permissions.*.can_download' => 'sometimes|boolean',
            'permissions.*.can_edit' => 'sometimes|boolean'
        ]);

        $file = $request->file('file');
        $path = $file->store('documents','public');

        $document = Document::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'is_confidential' => $request->is_confidential ?? false
        ]);

        // Sync permissions
        $permissions = [];
        foreach ($request->permissions as $perm) {
            $permissions[$perm['role_id']] = [
                'can_view' => $perm['can_view'] ?? false,
                'can_download' => $perm['can_download'] ?? false,
                'can_edit' => $perm['can_edit'] ?? false
            ];
        }
        $document->permissions()->sync($permissions);

        // Log access
        DocumentAccessLog::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'ip_address' => $request->ip()
        ]);

        Alert::success('Success', 'Document uploaded successfully');
        return redirect()->route('admin.documents.index');
    }

    public function show(Document $document)
    {
        if (!Auth::user()->canViewDocument($document)) {
            abort(403);
        }

        // Log access
        DocumentAccessLog::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'viewed',
            'ip_address' => request()->ip()
        ]);

        return view('admin.documents.show', compact('document'));
    }

    public function download(Document $document)
    {
        if (!Auth::user()->canDownloadDocument($document)) {
            abort(403);
        }

        // Log access
        DocumentAccessLog::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'downloaded',
            'ip_address' => request()->ip()
        ]);

        return Storage::download($document->file_path, $document->file_name);
    }

    public function edit(Document $document)
    {
        if (!Auth::user()->canEditDocument($document)) {
            abort(403);
        }

        $roles = Role::all();
        $currentPermissions = $document->permissions->keyBy('id');

        return view('admin.documents.edit', compact('document', 'roles', 'currentPermissions'));
    }

    public function update(Request $request, Document $document)
    {
        if (!Auth::user()->canEditDocument($document)) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_confidential' => 'sometimes|boolean',
            'permissions' => 'required|array',
            'permissions.*.role_id' => 'exists:roles,id',
            'permissions.*.can_view' => 'sometimes|boolean',
            'permissions.*.can_download' => 'sometimes|boolean',
            'permissions.*.can_edit' => 'sometimes|boolean'
        ]);

        $document->update([
            'title' => $request->title,
            'description' => $request->description,
            'is_confidential' => $request->is_confidential ?? false
        ]);

        // Sync permissions
        $permissions = [];
        foreach ($request->permissions as $perm) {
            $permissions[$perm['role_id']] = [
                'can_view' => $perm['can_view'] ?? false,
                'can_download' => $perm['can_download'] ?? false,
                'can_edit' => $perm['can_edit'] ?? false
            ];
        }
        $document->permissions()->sync($permissions);

        // Log access
        DocumentAccessLog::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'ip_address' => $request->ip()
        ]);

        Alert::success('Success', 'Document updated successfully');
        return redirect()->route('admin.documents.index');
    }

    public function destroy(Document $document)
    {
        if (!Auth::user()->canEditDocument($document)) {
            abort(403);
        }

        Storage::delete($document->file_path);
        $document->delete();

        Alert::success('Success', 'Document deleted successfully');
        return back();
    }

    public function accessLogs(Document $document)
    {
        if (!Auth::user()->canViewDocumentLogs($document)) {
            abort(403);
        }

        $logs = DocumentAccessLog::with('user')
            ->where('document_id', $document->id)
            ->latest()
            ->paginate(10);

        return view('admin.documents.access-logs', compact('document', 'logs'));
    }
}
