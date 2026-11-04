<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentFolder;
use App\Models\CompanyDocument;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Ensure only admin/superadmin can perform modifying operations.
     */
    protected function checkAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized. Administrative privileges required.');
        }
    }

    /**
     * Get all folders with document counts.
     */
    public function indexFolders(Request $request)
    {
        $query = DocumentFolder::withCount(['documents' => function ($q) use ($request) {
            $user = $request->user();
            if ($user && $user->role === 'employee') {
                $q->where('is_active', true);
            }
        }]);

        // Auto-seed default folders if none exist
        if ($query->count() === 0) {
            $defaultFolders = [
                [
                    'name' => 'HR Policies & Guidelines',
                    'slug' => 'hr-policies',
                    'description' => 'Employee code of conduct, leave policy, attendance norms, and workplace ethics.',
                    'icon' => 'book'
                ],
                [
                    'name' => 'IT & Information Security Policies',
                    'slug' => 'it-security-policies',
                    'description' => 'Cybersecurity rules, data protection, acceptable device use, and software compliance.',
                    'icon' => 'shield'
                ],
                [
                    'name' => 'Statutory & Labor Compliance',
                    'slug' => 'statutory-compliance',
                    'description' => 'EPF, ESIC, Gratuity rules, POSH guidelines, and statutory labor notices.',
                    'icon' => 'file-text'
                ],
                [
                    'name' => 'Employee Benefits & Handbooks',
                    'slug' => 'handbooks-benefits',
                    'description' => 'Corporate health insurance policy, travel allowances, and onboarding welcome kits.',
                    'icon' => 'folder'
                ],
            ];

            foreach ($defaultFolders as $folder) {
                DocumentFolder::create([
                    'name' => $folder['name'],
                    'slug' => $folder['slug'],
                    'description' => $folder['description'],
                    'icon' => $folder['icon'],
                    'created_by_user_id' => $request->user()?->id,
                ]);
            }
        }

        $folders = DocumentFolder::withCount(['documents' => function ($q) use ($request) {
            $user = $request->user();
            if ($user && $user->role === 'employee') {
                $q->where('is_active', true);
            }
        }])->orderBy('name', 'asc')->get();

        return response()->json([
            'folders' => $folders
        ]);
    }

    /**
     * Create a new folder (Admin/Superadmin only).
     */
    public function storeFolder(Request $request)
    {
        $this->checkAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
        ]);

        $folder = DocumentFolder::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . rand(100, 999),
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? 'folder',
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Folder created successfully.',
            'folder' => $folder->loadCount('documents'),
        ], 201);
    }

    /**
     * Update a folder (Admin/Superadmin only).
     */
    public function updateFolder(Request $request, $id)
    {
        $this->checkAdmin($request);

        $folder = DocumentFolder::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
        ]);

        $folder->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? $folder->icon,
        ]);

        return response()->json([
            'message' => 'Folder updated successfully.',
            'folder' => $folder->loadCount('documents'),
        ]);
    }

    /**
     * Delete a folder (Admin/Superadmin only).
     */
    public function destroyFolder(Request $request, $id)
    {
        $this->checkAdmin($request);

        $folder = DocumentFolder::with('documents')->findOrFail($id);

        // Delete underlying physical files
        foreach ($folder->documents as $doc) {
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
        }

        $folder->delete();

        return response()->json([
            'message' => 'Folder and its contents deleted successfully.'
        ]);
    }

    /**
     * List documents with search & filter.
     */
    public function indexDocuments(Request $request)
    {
        $user = $request->user();
        $query = CompanyDocument::with(['folder:id,name,slug,icon', 'uploader:id,name,email']);

        // Employees can only see active documents
        if ($user && $user->role === 'employee') {
            $query->where('is_active', true);
        }

        if ($request->filled('folder_id') && $request->folder_id !== 'all') {
            $query->where('folder_id', $request->folder_id);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhere('file_name', 'like', "%{$s}%");
            });
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'documents' => $documents
        ]);
    }

    /**
     * Upload a document (Admin/Superadmin only).
     */
    public function storeDocument(Request $request)
    {
        $this->checkAdmin($request);

        $request->validate([
            'folder_id' => 'required|exists:document_folders,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'version' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'file' => 'required|file|max:30720', // max 30MB
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $size = $file->getSize();

        // Store file in public/compliance_documents
        $storedPath = $file->store('compliance_documents', 'public');

        $doc = CompanyDocument::create([
            'folder_id' => $request->folder_id,
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $storedPath,
            'file_name' => $originalName,
            'file_size' => $size,
            'file_type' => $extension,
            'version' => $request->input('version', '1.0'),
            'is_active' => $request->boolean('is_active', true),
            'uploaded_by_user_id' => $request->user()->id,
        ]);

        // If published actively, notify all employees and users
        if ($doc->is_active) {
            $allUsers = User::all();
            foreach ($allUsers as $u) {
                AppNotification::create([
                    'user_id' => $u->id,
                    'type' => 'document_published',
                    'title' => 'New Compliance Policy Published',
                    'message' => "\"{$doc->title}\" has been published under {$doc->folder?->name}. Click to view or download.",
                    'action_url' => '/documents',
                    'icon' => 'file-text',
                    'priority' => 'normal',
                    'data' => [
                        'document_id' => $doc->id,
                        'folder_id' => $doc->folder_id,
                    ],
                    'is_read' => false,
                ]);
            }
        }

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'document' => $doc->load(['folder:id,name,slug,icon', 'uploader:id,name,email']),
        ], 201);
    }

    /**
     * Update document metadata or replace file (Admin/Superadmin only).
     */
    public function updateDocument(Request $request, $id)
    {
        $this->checkAdmin($request);

        $doc = CompanyDocument::findOrFail($id);

        $request->validate([
            'folder_id' => 'required|exists:document_folders,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'version' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'file' => 'nullable|file|max:30720',
        ]);

        $updateData = [
            'folder_id' => $request->folder_id,
            'title' => $request->title,
            'description' => $request->description,
            'version' => $request->input('version', $doc->version),
            'is_active' => $request->boolean('is_active', $doc->is_active),
        ];

        if ($request->hasFile('file')) {
            // Delete old file
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
            $file = $request->file('file');
            $updateData['file_path'] = $file->store('compliance_documents', 'public');
            $updateData['file_name'] = $file->getClientOriginalName();
            $updateData['file_size'] = $file->getSize();
            $updateData['file_type'] = strtolower($file->getClientOriginalExtension());
        }

        $doc->update($updateData);

        return response()->json([
            'message' => 'Document updated successfully.',
            'document' => $doc->fresh()->load(['folder:id,name,slug,icon', 'uploader:id,name,email']),
        ]);
    }

    /**
     * Delete document (Admin/Superadmin only).
     */
    public function destroyDocument(Request $request, $id)
    {
        $this->checkAdmin($request);

        $doc = CompanyDocument::findOrFail($id);

        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }

        $doc->delete();

        return response()->json([
            'message' => 'Document deleted successfully.'
        ]);
    }

    /**
     * Download or stream document.
     */
    public function downloadDocument($id)
    {
        $doc = CompanyDocument::findOrFail($id);

        if (!Storage::disk('public')->exists($doc->file_path)) {
            abort(404, 'File not found on server storage.');
        }

        return Storage::disk('public')->download($doc->file_path, $doc->file_name);
    }
}

