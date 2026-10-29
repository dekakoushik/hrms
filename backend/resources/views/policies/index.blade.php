@extends('layouts.app')

@section('title', 'HR & Policies - HRMS')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Controls -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                    Document Center
                </span>
                <span class="text-xs text-slate-400 font-medium">• Workplace Compliance</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i data-lucide="book-open-check" class="w-6 h-6 text-brand-600"></i>
                <span>HR & Workplace Policies</span>
            </h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">
                Access official company policies, staff guidelines, and compliance handbooks.
            </p>
        </div>

        <div class="flex items-center flex-wrap gap-2.5">
            @if($canManage)
                <button 
                    type="button" 
                    onclick="openCreateFolderModal()" 
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl border border-slate-200 transition-colors shadow-xs"
                >
                    <i data-lucide="folder-plus" class="w-4 h-4 text-slate-600"></i>
                    <span>Create Folder</span>
                </button>

                <button 
                    type="button" 
                    onclick="openUploadDocModal()" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl shadow-sm shadow-brand-500/25 transition-all"
                >
                    <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                    <span>Upload Document</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-xs text-emerald-800 font-medium">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 font-medium">
            <ul class="list-disc pl-4 space-y-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Folders Grid -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                Policy Folders ({{ $folders->count() }})
            </h2>
            @if($currentFolder)
                <a href="{{ route('policies.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>View All Folders</span>
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- "All Documents" Virtual Folder -->
            <a 
                href="{{ route('policies.index') }}" 
                class="group relative p-4 rounded-2xl border transition-all duration-200 bg-white {{ is_null($currentFolder) ? 'border-brand-500 ring-2 ring-brand-500/20 shadow-sm' : 'border-slate-200/90 hover:border-slate-300 hover:shadow-xs' }}"
            >
                <div class="flex items-start justify-between">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors {{ is_null($currentFolder) ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 group-hover:bg-brand-50 group-hover:text-brand-600' }}">
                        <i data-lucide="files" class="w-5 h-5"></i>
                    </div>
                    <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                        {{ $folders->sum('documents_count') }} files
                    </span>
                </div>
                <h3 class="text-sm font-bold text-slate-900 mt-3 group-hover:text-brand-600 transition-colors">
                    All Documents
                </h3>
                <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                    Browse all workplace policies
                </p>
            </a>

            <!-- Dynamic Folders -->
            @foreach ($folders as $folder)
                @php
                    $isSelected = ($currentFolder && $currentFolder->id === $folder->id);
                    $colorBg = match($folder->color) {
                        'emerald' => 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100',
                        'purple'  => 'bg-purple-50 text-purple-600 group-hover:bg-purple-100',
                        'amber'   => 'bg-amber-50 text-amber-600 group-hover:bg-amber-100',
                        'rose'    => 'bg-rose-50 text-rose-600 group-hover:bg-rose-100',
                        default   => 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100',
                    };
                @endphp
                <div class="group relative p-4 rounded-2xl border transition-all duration-200 bg-white {{ $isSelected ? 'border-brand-500 ring-2 ring-brand-500/20 shadow-sm' : 'border-slate-200/90 hover:border-slate-300 hover:shadow-xs' }}">
                    <div class="flex items-start justify-between">
                        <a href="{{ route('policies.index', ['folder_id' => $folder->id]) }}" class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors {{ $colorBg }} shrink-0">
                                <i data-lucide="folder" class="w-5 h-5"></i>
                            </div>
                        </a>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                                {{ $folder->documents_count }}
                            </span>
                            @if($canManage)
                                <form method="POST" action="{{ route('policies.folders.destroy', $folder->id) }}" onsubmit="return confirm('Delete folder \'{{ $folder->name }}\' and all files inside it?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete Folder" class="p-1 text-slate-400 hover:text-rose-600 rounded-md transition-colors">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('policies.index', ['folder_id' => $folder->id]) }}" class="block mt-3">
                        <h3 class="text-sm font-bold text-slate-900 group-hover:text-brand-600 transition-colors truncate">
                            {{ $folder->name }}
                        </h3>
                        <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5" title="{{ $folder->description }}">
                            {{ $folder->description ?: 'No description' }}
                        </p>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Search & Active Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <div class="text-xs font-bold text-slate-700">
                @if($currentFolder)
                    <span>Folder: <span class="text-brand-600">{{ $currentFolder->name }}</span></span>
                @else
                    <span>All Policy Documents</span>
                @endif
            </div>
            <span class="text-slate-300">•</span>
            <span class="text-xs text-slate-500">{{ $documents->count() }} file(s) available</span>
        </div>

        <form method="GET" action="{{ route('policies.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
            @if($currentFolder)
                <input type="hidden" name="folder_id" value="{{ $currentFolder->id }}">
            @endif
            <div class="relative w-full sm:w-64">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}" 
                    placeholder="Search by title, name..." 
                    class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
                >
            </div>
            <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition-colors">
                Search
            </button>
            @if($search)
                <a href="{{ route('policies.index', $currentFolder ? ['folder_id' => $currentFolder->id] : []) }}" class="p-1.5 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </a>
            @endif
        </form>
    </div>

    <!-- Documents Listing Table / Card View -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
        @if($documents->isEmpty())
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="file-question" class="w-6 h-6"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800">No documents found</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    @if($search)
                        No policies match your search term "{{ $search }}". Try another keyword.
                    @else
                        There are currently no documents uploaded in this folder.
                    @endif
                </p>
                @if($canManage)
                    <div class="mt-4">
                        <button type="button" onclick="openUploadDocModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                            <span>Upload Document Now</span>
                        </button>
                    </div>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-4">Document Title</th>
                            <th class="py-3 px-4">Folder</th>
                            <th class="py-3 px-4">Version</th>
                            <th class="py-3 px-4">File Size</th>
                            <th class="py-3 px-4">Updated On</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach ($documents as $doc)
                            @php
                                $ext = strtolower($doc->file_type);
                                $icon = match($ext) {
                                    'pdf' => 'file-text',
                                    'doc', 'docx' => 'file-type',
                                    'xls', 'xlsx' => 'sheet',
                                    'ppt', 'pptx' => 'presentation',
                                    default => 'file',
                                };
                                $iconColor = match($ext) {
                                    'pdf' => 'text-rose-600 bg-rose-50',
                                    'doc', 'docx' => 'text-blue-600 bg-blue-50',
                                    'xls', 'xlsx' => 'text-emerald-600 bg-emerald-50',
                                    'ppt', 'pptx' => 'text-amber-600 bg-amber-50',
                                    default => 'text-slate-600 bg-slate-100',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors group">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $iconColor }}">
                                            <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block group-hover:text-brand-600 transition-colors">
                                                {{ $doc->title }}
                                            </span>
                                            <span class="text-[11px] text-slate-500 block truncate max-w-xs">
                                                {{ $doc->file_name }}
                                            </span>
                                            @if($doc->description)
                                                <span class="text-[10px] text-slate-400 block line-clamp-1 mt-0.5">
                                                    {{ $doc->description }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                        <i data-lucide="folder" class="w-3 h-3 text-slate-400"></i>
                                        <span>{{ $doc->folder->name ?? 'General' }}</span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-mono text-[11px] font-bold text-slate-600 bg-slate-50 px-2 py-0.5 rounded border border-slate-200">
                                        v{{ $doc->version ?: '1.0' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]">
                                    {{ $doc->file_size ?: 'N/A' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                    <div>{{ $doc->updated_at->format('d M, Y') }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $doc->uploader->name ?? 'HR Team' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Inline Preview for PDF / Images -->
                                        @if(in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'txt']))
                                            <a 
                                                href="{{ route('policies.documents.preview', $doc->id) }}" 
                                                target="_blank" 
                                                title="View / Preview Document" 
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-brand-600 hover:bg-brand-50 border border-slate-200 hover:border-brand-200 transition-colors"
                                            >
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </a>
                                        @endif

                                        <!-- Download Button (Available for EVERY user) -->
                                        <a 
                                            href="{{ route('policies.documents.download', $doc->id) }}" 
                                            title="Download Policy Document" 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition-colors"
                                        >
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                            <span class="hidden sm:inline">Download</span>
                                        </a>

                                        <!-- Delete Button (Admin / Superadmin only) -->
                                        @if($canManage)
                                            <form method="POST" action="{{ route('policies.documents.destroy', $doc->id) }}" onsubmit="return confirm('Permanently delete \'{{ $doc->title }}\'?')">
                                                @csrf
                                                @method('DELETE')
                                                <button 
                                                    type="submit" 
                                                    title="Delete Document" 
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                >
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

@if($canManage)
    <!-- Modal: Create Folder -->
    <div id="createFolderModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Create Policy Folder</h3>
                    <p class="text-xs text-slate-500">Organize documents into categorized folders.</p>
                </div>
                <button type="button" onclick="closeCreateFolderModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('policies.folders.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Folder Name *</label>
                    <input 
                        type="text" 
                        name="name" 
                        required 
                        placeholder="e.g. Compensation & Bonus Guidelines" 
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Description</label>
                    <textarea 
                        name="description" 
                        rows="2" 
                        placeholder="Brief summary of what this folder contains..." 
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Folder Accent Color</label>
                    <select name="color" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                        <option value="blue">Indigo / Blue</option>
                        <option value="emerald">Emerald / Green</option>
                        <option value="purple">Purple / Violet</option>
                        <option value="amber">Amber / Warm Orange</option>
                        <option value="rose">Rose / Red</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeCreateFolderModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors">
                        Create Folder
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Upload Document -->
    <div id="uploadDocModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Upload Policy Document</h3>
                    <p class="text-xs text-slate-500">Upload PDF, Word, or spreadsheet documentation.</p>
                </div>
                <button type="button" onclick="closeUploadDocModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('policies.documents.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Target Folder *</label>
                    <select name="folder_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                        @foreach ($folders as $f)
                            <option value="{{ $f->id }}" {{ ($currentFolder && $currentFolder->id === $f->id) ? 'selected' : '' }}>
                                📁 {{ $f->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Document Title *</label>
                        <input 
                            type="text" 
                            name="title" 
                            required 
                            placeholder="e.g. Employee Code of Conduct 2026" 
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Version</label>
                        <input 
                            type="text" 
                            name="version" 
                            value="1.0" 
                            placeholder="e.g. 1.0" 
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono font-medium text-slate-900 focus:ring-2 focus:ring-brand-500"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Short Description / Notes</label>
                    <textarea 
                        name="description" 
                        rows="2" 
                        placeholder="Key highlights or guidelines covered in this document..." 
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Select File *</label>
                    <input 
                        type="file" 
                        name="document_file" 
                        required 
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.png,.jpg"
                        class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 cursor-pointer border border-slate-300 rounded-xl bg-slate-50 p-1"
                    >
                    <p class="text-[10px] text-slate-400 mt-1">Supported: PDF, Word, Excel, PowerPoint, Images (Max: 25MB)</p>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeUploadDocModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors flex items-center gap-1.5">
                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                        <span>Upload File</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
    function openCreateFolderModal() {
        const modal = document.getElementById('createFolderModal');
        if (modal) modal.classList.remove('hidden');
    }
    function closeCreateFolderModal() {
        const modal = document.getElementById('createFolderModal');
        if (modal) modal.classList.add('hidden');
    }

    function openUploadDocModal() {
        const modal = document.getElementById('uploadDocModal');
        if (modal) modal.classList.remove('hidden');
    }
    function closeUploadDocModal() {
        const modal = document.getElementById('uploadDocModal');
        if (modal) modal.classList.add('hidden');
    }
</script>
@endpush
