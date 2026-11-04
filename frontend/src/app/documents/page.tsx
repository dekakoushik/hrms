'use client';

import React, { useState, useEffect } from 'react';
import AppLayout from '@/components/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { apiRequest } from '@/lib/api';
import { DocumentFolder, CompanyDocument } from '@/types/document';
import Cookies from 'js-cookie';
import {
  Folder,
  FolderPlus,
  FileUp,
  FileText,
  Download,
  Trash2,
  Edit2,
  Search,
  Shield,
  Book,
  Briefcase,
  AlertCircle,
  CheckCircle,
  X,
  PlusCircle,
  Layers,
} from 'lucide-react';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api';

export default function DocumentsPage() {
  const { user, token } = useAuth();
  const isAdminOrSuper = user?.role === 'admin' || user?.role === 'superadmin';

  const [folders, setFolders] = useState<DocumentFolder[]>([]);
  const [documents, setDocuments] = useState<CompanyDocument[]>([]);
  const [selectedFolderId, setSelectedFolderId] = useState<number | 'all'>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  // Modal States
  const [isFolderModalOpen, setIsFolderModalOpen] = useState(false);
  const [folderForm, setFolderForm] = useState<{ id?: number; name: string; description: string; icon: string }>({
    name: '',
    description: '',
    icon: 'folder',
  });

  const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
  const [uploadForm, setUploadForm] = useState<{
    id?: number;
    folder_id: number | '';
    title: string;
    description: string;
    version: string;
    is_active: boolean;
    file: File | null;
  }>({
    folder_id: '',
    title: '',
    description: '',
    version: '1.0',
    is_active: true,
    file: null,
  });

  const [isSubmitting, setIsSubmitting] = useState(false);

  // Fetch folders and documents
  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);

      const folderQuery = selectedFolderId !== 'all' ? `folder_id=${selectedFolderId}` : '';
      const textQuery = searchQuery.trim() ? `search=${encodeURIComponent(searchQuery.trim())}` : '';
      const params = [folderQuery, textQuery].filter(Boolean).join('&');
      const docsUrl = `/documents${params ? `?${params}` : ''}`;

      const [foldersRes, docsRes] = await Promise.all([
        apiRequest<{ folders: DocumentFolder[] }>('/documents/folders', {}, token || undefined),
        apiRequest<{ documents: CompanyDocument[] }>(docsUrl, {}, token || undefined),
      ]);

      setFolders(foldersRes.folders || []);
      setDocuments(docsRes.documents || []);
    } catch (err: any) {
      console.error('Failed to load documents data', err);
      setError(err?.message || 'Failed to load documents and policies.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [selectedFolderId]);

  // Handle Search trigger
  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    loadData();
  };

  // Helper icon renderer
  const renderFolderIcon = (iconName?: string, className: string = 'w-5 h-5') => {
    switch (iconName) {
      case 'shield':
        return <Shield className={className} />;
      case 'book':
        return <Book className={className} />;
      case 'file-text':
        return <FileText className={className} />;
      case 'briefcase':
        return <Briefcase className={className} />;
      default:
        return <Folder className={className} />;
    }
  };

  // Helper file format badge
  const renderFileBadge = (fileType?: string | null) => {
    const ext = fileType?.toLowerCase() || 'file';
    let colorClass = 'bg-slate-100 text-slate-700 border-slate-200';
    if (ext === 'pdf') {
      colorClass = 'bg-red-50 text-red-700 border-red-200';
    } else if (['doc', 'docx'].includes(ext)) {
      colorClass = 'bg-blue-50 text-blue-700 border-blue-200';
    } else if (['xls', 'xlsx'].includes(ext)) {
      colorClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
    }

    return (
      <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase tracking-wider border ${colorClass}`}>
        {ext}
      </span>
    );
  };

  // Folder Actions
  const handleOpenFolderModal = (folder?: DocumentFolder) => {
    if (folder) {
      setFolderForm({
        id: folder.id,
        name: folder.name,
        description: folder.description || '',
        icon: folder.icon || 'folder',
      });
    } else {
      setFolderForm({
        name: '',
        description: '',
        icon: 'folder',
      });
    }
    setIsFolderModalOpen(true);
  };

  const handleSaveFolder = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!folderForm.name.trim()) return;

    try {
      setIsSubmitting(true);
      if (folderForm.id) {
        await apiRequest(`/documents/folders/${folderForm.id}`, {
          method: 'PUT',
          body: JSON.stringify(folderForm),
        }, token || undefined);
        setSuccessMsg('Folder updated successfully.');
      } else {
        await apiRequest('/documents/folders', {
          method: 'POST',
          body: JSON.stringify(folderForm),
        }, token || undefined);
        setSuccessMsg('New category folder created.');
      }
      setIsFolderModalOpen(false);
      loadData();
    } catch (err: any) {
      alert(err?.message || 'Error saving folder');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDeleteFolder = async (folder: DocumentFolder) => {
    if (!confirm(`Are you sure you want to delete "${folder.name}" and all documents inside it?`)) return;

    try {
      await apiRequest(`/documents/folders/${folder.id}`, {
        method: 'DELETE',
      }, token || undefined);
      setSuccessMsg('Folder removed.');
      if (selectedFolderId === folder.id) {
        setSelectedFolderId('all');
      } else {
        loadData();
      }
    } catch (err: any) {
      alert(err?.message || 'Failed to delete folder.');
    }
  };

  // Document Upload / Edit Actions
  const handleOpenUploadModal = (doc?: CompanyDocument) => {
    if (doc) {
      setUploadForm({
        id: doc.id,
        folder_id: doc.folder_id,
        title: doc.title,
        description: doc.description || '',
        version: doc.version || '1.0',
        is_active: doc.is_active,
        file: null,
      });
    } else {
      setUploadForm({
        folder_id: selectedFolderId !== 'all' ? selectedFolderId : (folders[0]?.id || ''),
        title: '',
        description: '',
        version: '1.0',
        is_active: true,
        file: null,
      });
    }
    setIsUploadModalOpen(true);
  };

  const handleSaveDocument = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!uploadForm.folder_id || !uploadForm.title.trim()) {
      alert('Please specify title and folder category.');
      return;
    }

    if (!uploadForm.id && !uploadForm.file) {
      alert('Please select a file to upload.');
      return;
    }

    try {
      setIsSubmitting(true);
      const formData = new FormData();
      formData.append('folder_id', String(uploadForm.folder_id));
      formData.append('title', uploadForm.title);
      formData.append('description', uploadForm.description || '');
      formData.append('version', uploadForm.version || '1.0');
      formData.append('is_active', uploadForm.is_active ? '1' : '0');

      if (uploadForm.file) {
        formData.append('file', uploadForm.file);
      }

      const authToken = token || Cookies.get('hrms_token');
      const url = uploadForm.id
        ? `${API_BASE_URL}/documents/${uploadForm.id}/update`
        : `${API_BASE_URL}/documents/upload`;

      const response = await fetch(url, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
        },
        body: formData,
      });

      const resData = await response.json();
      if (!response.ok) {
        throw new Error(resData.message || 'Failed to upload document.');
      }

      setSuccessMsg(uploadForm.id ? 'Document updated.' : 'Document uploaded and published.');
      setIsUploadModalOpen(false);
      loadData();
    } catch (err: any) {
      alert(err?.message || 'Error uploading document.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDeleteDocument = async (doc: CompanyDocument) => {
    if (!confirm(`Are you sure you want to delete "${doc.title}"?`)) return;

    try {
      await apiRequest(`/documents/${doc.id}`, {
        method: 'DELETE',
      }, token || undefined);
      setSuccessMsg('Document removed.');
      loadData();
    } catch (err: any) {
      alert(err?.message || 'Failed to delete document.');
    }
  };

  const handleDownload = async (doc: CompanyDocument) => {
    try {
      const authToken = token || Cookies.get('hrms_token');
      const downloadUrl = `${API_BASE_URL}/documents/${doc.id}/download`;

      const res = await fetch(downloadUrl, {
        headers: {
          ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
        },
      });

      if (!res.ok) {
        throw new Error('Failed to download file from server.');
      }

      const blob = await res.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', doc.file_name);
      document.body.appendChild(link);
      link.click();
      link.parentNode?.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (err) {
      console.error('Download error', err);
      if (doc.file_url) {
        window.open(doc.file_url, '_blank');
      } else {
        alert('Could not download file. Please check server connection.');
      }
    }
  };

  const selectedFolderName = selectedFolderId === 'all'
    ? 'All Statutory & Compliance Policies'
    : folders.find((f) => f.id === selectedFolderId)?.name || 'Selected Folder';

  return (
    <AppLayout
      title="Statutory & Compliance Center"
      subtitle="Corporate guidelines, statutory labour compliances, IT policies, and organizational handbooks"
      action={
        isAdminOrSuper ? (
          <div className="flex items-center space-x-3">
            <button
              onClick={() => handleOpenFolderModal()}
              className="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-sm"
            >
              <FolderPlus className="w-4 h-4 mr-1.5 text-blue-600" />
              New Folder
            </button>
            <button
              onClick={() => handleOpenUploadModal()}
              className="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm"
            >
              <FileUp className="w-4 h-4 mr-1.5" />
              Upload Document
            </button>
          </div>
        ) : null
      }
    >
      <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
        {/* Alerts */}
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center justify-between text-sm">
            <div className="flex items-center space-x-2">
              <AlertCircle className="w-5 h-5 flex-shrink-0 text-red-500" />
              <span>{error}</span>
            </div>
            <button onClick={() => setError(null)} className="text-red-400 hover:text-red-600">
              <X className="w-4 h-4" />
            </button>
          </div>
        )}

        {successMsg && (
          <div className="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between text-sm">
            <div className="flex items-center space-x-2">
              <CheckCircle className="w-5 h-5 flex-shrink-0 text-emerald-500" />
              <span>{successMsg}</span>
            </div>
            <button onClick={() => setSuccessMsg(null)} className="text-emerald-400 hover:text-emerald-600">
              <X className="w-4 h-4" />
            </button>
          </div>
        )}

        {/* Hero Notice Banner */}
        <div className="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl p-6 text-white shadow-xl flex flex-col md:flex-row items-start md:center justify-between gap-4">
          <div className="space-y-1">
            <div className="flex items-center space-x-2">
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-300 border border-blue-400/30">
                Official Company Documentation
              </span>
              <span className="text-xs text-slate-400">All Employees & Management</span>
            </div>
            <h2 className="text-lg font-bold tracking-tight">Statutory Rules, Work Guidelines & Human Resource Policies</h2>
            <p className="text-xs text-slate-300 max-w-2xl leading-relaxed">
              Transparent, up-to-date compliance repository for employment terms, code of conduct, occupational safety, IT data policies, and regulatory declarations.
            </p>
          </div>
          <div className="flex items-center gap-3">
            <div className="text-right hidden sm:block">
              <div className="text-2xl font-bold">{documents.length}</div>
              <div className="text-xs text-blue-200">Active Policies</div>
            </div>
            <div className="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center border border-white/20">
              <Shield className="w-6 h-6 text-blue-300" />
            </div>
          </div>
        </div>

        {/* Main Layout: Left Folder List, Right Documents Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
          {/* FOLDERS SIDEBAR */}
          <div className="lg:col-span-1 space-y-4">
            <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Document Folders</span>
                {isAdminOrSuper && (
                  <button
                    onClick={() => handleOpenFolderModal()}
                    className="text-xs text-blue-600 hover:text-blue-700 font-semibold flex items-center"
                    title="Add Folder"
                  >
                    <PlusCircle className="w-3.5 h-3.5 mr-1" /> Add
                  </button>
                )}
              </div>

              <div className="space-y-1">
                {/* All Documents Button */}
                <button
                  onClick={() => setSelectedFolderId('all')}
                  className={`w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-medium transition text-left ${
                    selectedFolderId === 'all'
                      ? 'bg-blue-50 text-blue-700 font-semibold shadow-xs'
                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                  }`}
                >
                  <div className="flex items-center space-x-2.5 min-w-0">
                    <Layers className={`w-4 h-4 ${selectedFolderId === 'all' ? 'text-blue-600' : 'text-slate-400'}`} />
                    <span className="truncate">All Folders</span>
                  </div>
                  <span className="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-bold">
                    {folders.reduce((acc, f) => acc + (f.documents_count || 0), 0)}
                  </span>
                </button>

                {/* Individual Folders */}
                {folders.map((folder) => {
                  const isSelected = selectedFolderId === folder.id;
                  return (
                    <div
                      key={folder.id}
                      className={`group flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-medium transition ${
                        isSelected
                          ? 'bg-blue-50 text-blue-700 font-semibold shadow-xs'
                          : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                      }`}
                    >
                      <button
                        onClick={() => setSelectedFolderId(folder.id)}
                        className="flex items-center space-x-2.5 min-w-0 flex-1 text-left"
                      >
                        <span className={isSelected ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-600'}>
                          {renderFolderIcon(folder.icon, 'w-4 h-4')}
                        </span>
                        <span className="truncate">{folder.name}</span>
                      </button>

                      <div className="flex items-center space-x-1.5 flex-shrink-0 ml-2">
                        <span className="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-bold">
                          {folder.documents_count || 0}
                        </span>

                        {isAdminOrSuper && (
                          <div className="opacity-0 group-hover:opacity-100 flex items-center space-x-1 transition-opacity">
                            <button
                              onClick={(e) => {
                                e.stopPropagation();
                                handleOpenFolderModal(folder);
                              }}
                              className="p-1 hover:text-blue-600 rounded text-slate-400"
                              title="Edit Folder"
                            >
                              <Edit2 className="w-3 h-3" />
                            </button>
                            <button
                              onClick={(e) => {
                                e.stopPropagation();
                                handleDeleteFolder(folder);
                              }}
                              className="p-1 hover:text-red-600 rounded text-slate-400"
                              title="Delete Folder"
                            >
                              <Trash2 className="w-3 h-3" />
                            </button>
                          </div>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Folder Quick Details Card */}
            {selectedFolderId !== 'all' && (
              <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs text-slate-600 space-y-2">
                <span className="font-semibold text-slate-800 block">About this Section</span>
                <p className="text-slate-500 leading-relaxed">
                  {folders.find((f) => f.id === selectedFolderId)?.description || 'No section notes provided.'}
                </p>
              </div>
            )}
          </div>

          {/* DOCUMENTS MAIN SECTION */}
          <div className="lg:col-span-3 space-y-4">
            {/* Filter & Search Bar */}
            <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
              <div>
                <h3 className="text-sm font-bold text-slate-800">{selectedFolderName}</h3>
                <p className="text-xs text-slate-500">
                  Showing {documents.length} verified statutory & compliance document{documents.length !== 1 ? 's' : ''}
                </p>
              </div>

              <form onSubmit={handleSearchSubmit} className="relative w-full sm:w-72">
                <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
                <input
                  type="text"
                  placeholder="Search policy name, keyword..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white text-slate-800 transition"
                />
              </form>
            </div>

            {/* Document Cards List */}
            {loading ? (
              <div className="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-500">
                <div className="animate-spin w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full mx-auto mb-3" />
                <p className="text-xs font-medium">Loading documents archive...</p>
              </div>
            ) : documents.length === 0 ? (
              <div className="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-500">
                <FileText className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                <h4 className="text-sm font-bold text-slate-700">No documents found</h4>
                <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                  {searchQuery
                    ? `No policy documents matched your search "${searchQuery}".`
                    : 'There are currently no documents uploaded in this folder category.'}
                </p>
                {isAdminOrSuper && (
                  <button
                    onClick={() => handleOpenUploadModal()}
                    className="mt-4 inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700 transition"
                  >
                    <FileUp className="w-3.5 h-3.5 mr-1.5" /> Upload First Document
                  </button>
                )}
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {documents.map((doc) => (
                  <div
                    key={doc.id}
                    className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs hover:shadow-md transition flex flex-col justify-between group"
                  >
                    <div>
                      {/* Card Header: Type Badge & Folder Name */}
                      <div className="flex items-center justify-between mb-2.5">
                        <div className="flex items-center space-x-2">
                          {renderFileBadge(doc.file_type)}
                          <span className="text-[11px] font-semibold text-slate-500 flex items-center">
                            <span className="w-1.5 h-1.5 rounded-full bg-slate-300 mr-1.5" />
                            {doc.folder?.name || 'General Policy'}
                          </span>
                        </div>
                        <span className="text-[11px] font-medium text-slate-400 bg-slate-50 px-2 py-0.5 rounded border border-slate-100">
                          v{doc.version || '1.0'}
                        </span>
                      </div>

                      {/* Title & Description */}
                      <h4 className="text-sm font-bold text-slate-800 line-clamp-2 group-hover:text-blue-600 transition">
                        {doc.title}
                      </h4>
                      <p className="text-xs text-slate-500 line-clamp-2 mt-1 leading-relaxed">
                        {doc.description || 'No description provided.'}
                      </p>
                    </div>

                    {/* Metadata & Actions */}
                    <div className="pt-4 mt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                      <div>
                        <div className="font-medium text-slate-700">{doc.formatted_file_size}</div>
                        <div className="text-[10px] text-slate-400">
                          Updated {new Date(doc.updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                        </div>
                      </div>

                      <div className="flex items-center space-x-1.5">
                        {/* Download Button */}
                        <button
                          onClick={() => handleDownload(doc)}
                          className="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 transition"
                          title="Download Document"
                        >
                          <Download className="w-3.5 h-3.5 mr-1" /> Download
                        </button>

                        {/* Admin Action Controls */}
                        {isAdminOrSuper && (
                          <div className="flex items-center space-x-1 pl-1 border-l border-slate-200">
                            <button
                              onClick={() => handleOpenUploadModal(doc)}
                              className="p-1 text-slate-400 hover:text-blue-600 rounded transition"
                              title="Edit Metadata / Replace File"
                            >
                              <Edit2 className="w-3.5 h-3.5" />
                            </button>
                            <button
                              onClick={() => handleDeleteDocument(doc)}
                              className="p-1 text-slate-400 hover:text-red-600 rounded transition"
                              title="Delete Document"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </button>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* MODAL: CREATE / EDIT FOLDER */}
      {isFolderModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <h3 className="text-sm font-bold text-slate-800">
                {folderForm.id ? 'Edit Category Folder' : 'Create Category Folder'}
              </h3>
              <button
                onClick={() => setIsFolderModalOpen(false)}
                className="text-slate-400 hover:text-slate-600"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleSaveFolder} className="space-y-4 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Folder Name *</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. IT Security & Code of Conduct"
                  value={folderForm.name}
                  onChange={(e) => setFolderForm({ ...folderForm, name: e.target.value })}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Folder Icon</label>
                <select
                  value={folderForm.icon}
                  onChange={(e) => setFolderForm({ ...folderForm, icon: e.target.value })}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white"
                >
                  <option value="folder">Folder (General)</option>
                  <option value="book">Book (HR & Policies)</option>
                  <option value="shield">Shield (Security & Compliance)</option>
                  <option value="file-text">File Text (Statutory Labor Laws)</option>
                  <option value="briefcase">Briefcase (Benefits & Handbook)</option>
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Description / Guidelines</label>
                <textarea
                  rows={3}
                  placeholder="Brief summary of policies stored in this folder..."
                  value={folderForm.description}
                  onChange={(e) => setFolderForm({ ...folderForm, description: e.target.value })}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div className="flex items-center justify-end space-x-2 pt-2">
                <button
                  type="button"
                  onClick={() => setIsFolderModalOpen(false)}
                  className="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg font-medium"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="px-4 py-1.5 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50"
                >
                  {isSubmitting ? 'Saving...' : folderForm.id ? 'Save Changes' : 'Create Folder'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: UPLOAD / EDIT DOCUMENT */}
      {isUploadModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <h3 className="text-sm font-bold text-slate-800">
                {uploadForm.id ? 'Edit Compliance Document' : 'Upload Statutory Document'}
              </h3>
              <button
                onClick={() => setIsUploadModalOpen(false)}
                className="text-slate-400 hover:text-slate-600"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleSaveDocument} className="space-y-4 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Target Folder *</label>
                  <select
                    required
                    value={uploadForm.folder_id}
                    onChange={(e) => setUploadForm({ ...uploadForm, folder_id: Number(e.target.value) })}
                    className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white"
                  >
                    <option value="" disabled>Select category folder</option>
                    {folders.map((f) => (
                      <option key={f.id} value={f.id}>
                        {f.name}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Document Version</label>
                  <input
                    type="text"
                    placeholder="e.g. 1.0 or 2026.1"
                    value={uploadForm.version}
                    onChange={(e) => setUploadForm({ ...uploadForm, version: e.target.value })}
                    className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Document Title *</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. Employee Code of Conduct & Ethics Manual"
                  value={uploadForm.title}
                  onChange={(e) => setUploadForm({ ...uploadForm, title: e.target.value })}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Description / Summary</label>
                <textarea
                  rows={2}
                  placeholder="Brief note on what this statutory compliance doc covers..."
                  value={uploadForm.description}
                  onChange={(e) => setUploadForm({ ...uploadForm, description: e.target.value })}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">
                  {uploadForm.id ? 'Replace Document File (Optional)' : 'Select Document File (PDF, DOCX, XLSX, etc.) *'}
                </label>
                <div className="border-2 border-dashed border-slate-200 rounded-xl p-4 text-center hover:border-blue-400 transition bg-slate-50">
                  <input
                    type="file"
                    id="docFileInput"
                    onChange={(e) => {
                      if (e.target.files && e.target.files[0]) {
                        setUploadForm({ ...uploadForm, file: e.target.files[0] });
                      }
                    }}
                    className="hidden"
                  />
                  <label htmlFor="docFileInput" className="cursor-pointer block">
                    <FileUp className="w-6 h-6 text-slate-400 mx-auto mb-1.5" />
                    {uploadForm.file ? (
                      <span className="font-semibold text-blue-600 block truncate">
                        {uploadForm.file.name} ({(uploadForm.file.size / 1024).toFixed(1)} KB)
                      </span>
                    ) : (
                      <>
                        <span className="text-slate-600 font-medium">Click to choose document file</span>
                        <span className="text-[10px] text-slate-400 block mt-0.5">Maximum file size: 30MB</span>
                      </>
                    )}
                  </label>
                </div>
              </div>

              <div className="flex items-center space-x-2 pt-1">
                <input
                  type="checkbox"
                  id="isActiveToggle"
                  checked={uploadForm.is_active}
                  onChange={(e) => setUploadForm({ ...uploadForm, is_active: e.target.checked })}
                  className="rounded text-blue-600 focus:ring-blue-500 w-4 h-4"
                />
                <label htmlFor="isActiveToggle" className="font-medium text-slate-700 select-none">
                  Make actively visible to all employees immediately
                </label>
              </div>

              <div className="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setIsUploadModalOpen(false)}
                  className="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg font-medium"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="px-4 py-1.5 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50"
                >
                  {isSubmitting ? 'Uploading...' : uploadForm.id ? 'Save Updates' : 'Publish Document'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
