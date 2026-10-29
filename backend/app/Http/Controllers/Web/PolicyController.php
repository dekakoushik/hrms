<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PolicyDocument;
use App\Models\PolicyFolder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PolicyController extends Controller
{
    /**
     * Display all folders or documents inside a selected folder.
     */
    public function index(Request $request)
    {
        $selectedFolderId = $request->query('folder_id');
        $search = $request->query('search');

        $folders = PolicyFolder::withCount('documents')
            ->orderBy('name')
            ->get();

        $currentFolder = null;
        if ($selectedFolderId) {
            $currentFolder = PolicyFolder::find($selectedFolderId);
        }

        $documentsQuery = PolicyDocument::with(['folder', 'uploader']);

        if ($currentFolder) {
            $documentsQuery->where('folder_id', $currentFolder->id);
        }

        if ($search) {
            $documentsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        $documents = $documentsQuery->latest()->get();

        $canManage = in_array(auth()->user()->role, ['superadmin', 'admin']);

        return view('policies.index', compact('folders', 'currentFolder', 'documents', 'canManage', 'search'));
    }

    /**
     * Create a new folder (Admin/SuperAdmin only).
     */
    public function storeFolder(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:policy_folders,name',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:30',
        ]);

        PolicyFolder::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? 'brand',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('policies.index')->with('success', 'Policy folder created successfully.');
    }

    /**
     * Delete a folder (Admin/SuperAdmin only).
     */
    public function destroyFolder(PolicyFolder $folder): RedirectResponse
    {
        $this->authorizeAdmin();

        // Delete associated files from storage
        foreach ($folder->documents as $doc) {
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
        }

        $folder->delete();

        return redirect()->route('policies.index')->with('success', "Folder '{$folder->name}' and all its documents deleted.");
    }

    /**
     * Upload a new document into a folder (Admin/SuperAdmin only).
     */
    public function storeDocument(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'folder_id' => 'required|exists:policy_folders,id',
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'version' => 'nullable|string|max:20',
            'document_file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,png,jpg,jpeg|max:25600', // 25MB max
        ]);

        $file = $request->file('document_file');
        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());
        $sizeBytes = $file->getSize();

        // Human readable size
        $fileSize = $this->formatBytes($sizeBytes);

        // Store file in storage/app/public/policies
        $path = $file->store('policies', 'public');

        PolicyDocument::create([
            'folder_id' => $request->input('folder_id'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'version' => $request->input('version') ?: '1.0',
            'file_path' => $path,
            'file_name' => $originalName,
            'file_size' => $fileSize,
            'file_type' => $ext,
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('policies.index', ['folder_id' => $request->input('folder_id')])
            ->with('success', "Document '{$originalName}' uploaded successfully.");
    }

    /**
     * Download or view policy document (All authenticated users).
     */
    public function downloadDocument(PolicyDocument $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            return back()->withErrors(['error' => 'The requested document file was not found on server storage.']);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    /**
     * Preview inline policy document (PDF/Images).
     */
    public function previewDocument(PolicyDocument $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            return back()->withErrors(['error' => 'File not found.']);
        }

        $fullPath = Storage::disk('public')->path($document->file_path);
        return response()->file($fullPath, [
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"'
        ]);
    }

    /**
     * Delete document (Admin/SuperAdmin only).
     */
    public function destroyDocument(PolicyDocument $document): RedirectResponse
    {
        $this->authorizeAdmin();

        $folderId = $document->folder_id;
        $title = $document->title;

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('policies.index', ['folder_id' => $folderId])
            ->with('success', "Document '{$title}' deleted successfully.");
    }

    /**
     * Helper to verify admin permissions.
     */
    protected function authorizeAdmin(): void
    {
        if (!in_array(auth()->user()->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized action. Only Admin or Superadmin can modify policies.');
        }
    }

    /**
     * Helper to format file size.
     */
    protected function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
