'use client';

import React, { useState, useEffect } from 'react';
import AppLayout from '@/components/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { apiRequest } from '@/lib/api';
import { ItTicket, TicketStats, TicketsIndexResponse } from '@/types/ticket';
import Cookies from 'js-cookie';
import {
  LifeBuoy,
  PlusCircle,
  Search,
  Filter,
  CheckCircle2,
  Clock,
  AlertCircle,
  Send,
  MessageSquare,
  FileText,
  User,
  Shield,
  X,
  ExternalLink,
  Paperclip,
  Check,
  ChevronRight,
  Layers,
} from 'lucide-react';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api';

export default function TicketsPage() {
  const { user, token } = useAuth();
  const isAdminOrSuper = user?.role === 'admin' || user?.role === 'superadmin';

  const [tickets, setTickets] = useState<ItTicket[]>([]);
  const [stats, setStats] = useState<TicketStats>({ open: 0, in_progress: 0, resolved: 0, total: 0 });
  const [loading, setLoading] = useState(true);
  const [selectedTicket, setSelectedTicket] = useState<ItTicket | null>(null);

  // Filters
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [filterPriority, setFilterPriority] = useState<string>('all');
  const [filterCategory, setFilterCategory] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Modals & Forms
  const [isNewTicketOpen, setIsNewTicketOpen] = useState(false);
  const [newTicketForm, setNewTicketForm] = useState<{
    category: string;
    priority: string;
    subject: string;
    description: string;
    attachment: File | null;
  }>({
    category: 'hardware',
    priority: 'medium',
    subject: '',
    description: '',
    attachment: null,
  });

  const [replyMessage, setReplyMessage] = useState('');
  const [replyAttachment, setReplyAttachment] = useState<File | null>(null);
  const [submittingReply, setSubmittingReply] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Admin Manage Modal
  const [manageForm, setManageForm] = useState<{
    status: string;
    priority: string;
    resolution_notes: string;
  }>({
    status: 'open',
    priority: 'medium',
    resolution_notes: '',
  });

  const showToast = (type: 'success' | 'error', message: string) => {
    setToast({ type, message });
    setTimeout(() => setToast(null), 4000);
  };

  const loadTickets = async () => {
    if (!token) return;
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (filterStatus !== 'all') params.append('status', filterStatus);
      if (filterPriority !== 'all') params.append('priority', filterPriority);
      if (filterCategory !== 'all') params.append('category', filterCategory);
      if (searchQuery.trim()) params.append('search', searchQuery.trim());

      const url = `/tickets${params.toString() ? `?${params.toString()}` : ''}`;
      const res = await apiRequest<TicketsIndexResponse>(url, {}, token || undefined);

      setTickets(res.tickets || []);
      setStats(res.stats || { open: 0, in_progress: 0, resolved: 0, total: 0 });

      // If a ticket is currently selected, refresh its details
      if (selectedTicket) {
        const refreshed = (res.tickets || []).find((t) => t.id === selectedTicket.id);
        if (refreshed) {
          loadTicketDetail(refreshed.id);
        }
      }
    } catch (err: any) {
      console.error('Failed to load IT tickets', err);
      showToast('error', err?.message || 'Failed to load tickets.');
    } finally {
      setLoading(false);
    }
  };

  const loadTicketDetail = async (id: number) => {
    if (!token) return;
    try {
      const res = await apiRequest<{ ticket: ItTicket }>(`/tickets/${id}`, {}, token || undefined);
      setSelectedTicket(res.ticket);
      setManageForm({
        status: res.ticket.status,
        priority: res.ticket.priority,
        resolution_notes: res.ticket.resolution_notes || '',
      });
    } catch (err: any) {
      console.error('Failed to load ticket detail', err);
    }
  };

  useEffect(() => {
    loadTickets();
  }, [filterStatus, filterPriority, filterCategory, token]);

  const handleCreateTicket = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newTicketForm.subject.trim() || !newTicketForm.description.trim()) {
      alert('Please fill in subject and description.');
      return;
    }

    try {
      setIsSubmitting(true);
      const formData = new FormData();
      formData.append('category', newTicketForm.category);
      formData.append('priority', newTicketForm.priority);
      formData.append('subject', newTicketForm.subject);
      formData.append('description', newTicketForm.description);
      if (newTicketForm.attachment) {
        formData.append('attachment', newTicketForm.attachment);
      }

      const authToken = token || Cookies.get('hrms_token');
      const response = await fetch(`${API_BASE_URL}/tickets`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
        },
        body: formData,
      });

      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || 'Failed to submit ticket');
      }

      showToast('success', 'IT Support Ticket raised successfully.');
      setIsNewTicketOpen(false);
      setNewTicketForm({
        category: 'hardware',
        priority: 'medium',
        subject: '',
        description: '',
        attachment: null,
      });
      loadTickets();
    } catch (err: any) {
      showToast('error', err?.message || 'Error submitting ticket.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleSendReply = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTicket || !replyMessage.trim()) return;

    try {
      setSubmittingReply(true);
      const formData = new FormData();
      formData.append('message', replyMessage);
      if (replyAttachment) {
        formData.append('attachment', replyAttachment);
      }

      const authToken = token || Cookies.get('hrms_token');
      const res = await fetch(`${API_BASE_URL}/tickets/${selectedTicket.id}/replies`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
        },
        body: formData,
      });

      if (!res.ok) {
        const errorData = await res.json();
        throw new Error(errorData.message || 'Failed to send reply');
      }

      setReplyMessage('');
      setReplyAttachment(null);
      loadTicketDetail(selectedTicket.id);
    } catch (err: any) {
      showToast('error', err?.message || 'Error posting reply.');
    } finally {
      setSubmittingReply(false);
    }
  };

  const handleUpdateTicketStatus = async () => {
    if (!selectedTicket) return;
    try {
      await apiRequest(`/tickets/${selectedTicket.id}`, {
        method: 'PUT',
        body: JSON.stringify(manageForm),
      }, token || undefined);

      showToast('success', 'Ticket status updated.');
      loadTicketDetail(selectedTicket.id);
      loadTickets();
    } catch (err: any) {
      showToast('error', err?.message || 'Failed to update ticket.');
    }
  };

  // Status & Priority Badges
  const renderStatusBadge = (status: string) => {
    switch (status) {
      case 'open':
        return <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">OPEN</span>;
      case 'in_progress':
        return <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">IN PROGRESS</span>;
      case 'resolved':
        return <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">RESOLVED</span>;
      case 'closed':
        return <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">CLOSED</span>;
      default:
        return <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">{status}</span>;
    }
  };

  const renderPriorityBadge = (priority: string) => {
    switch (priority) {
      case 'critical':
        return <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">CRITICAL</span>;
      case 'high':
        return <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">HIGH</span>;
      case 'medium':
        return <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">MEDIUM</span>;
      case 'low':
        return <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">LOW</span>;
      default:
        return null;
    }
  };

  return (
    <AppLayout
      title="IT Helpdesk & Support Desk"
      subtitle="Corporate hardware, software, network access, and workstation issue tracking"
      action={
        <button
          onClick={() => setIsNewTicketOpen(true)}
          className="inline-flex items-center px-4 py-2 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition shadow-xs"
        >
          <PlusCircle className="w-4 h-4 mr-1.5" />
          Raise IT Ticket
        </button>
      }
    >
      <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
        {/* Toast Alert */}
        {toast && (
          <div
            className={`p-4 rounded-xl border flex items-center justify-between text-xs font-semibold shadow-xs transition ${
              toast.type === 'success'
                ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                : 'bg-red-50 text-red-800 border-red-200'
            }`}
          >
            <span>{toast.message}</span>
            <button onClick={() => setToast(null)} className="text-slate-400 hover:text-slate-600">
              <X className="w-4 h-4" />
            </button>
          </div>
        )}

        {/* Stats Row */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div className="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-2xs">
            <div className="flex items-center justify-between text-slate-500 mb-2">
              <span className="text-xs font-bold uppercase tracking-wider">Open Tickets</span>
              <AlertCircle className="w-4 h-4 text-amber-500" />
            </div>
            <div className="text-2xl font-bold text-slate-900">{stats.open}</div>
            <div className="text-[11px] text-amber-600 mt-1 font-medium">Awaiting IT action</div>
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-2xs">
            <div className="flex items-center justify-between text-slate-500 mb-2">
              <span className="text-xs font-bold uppercase tracking-wider">In Progress</span>
              <Clock className="w-4 h-4 text-blue-500" />
            </div>
            <div className="text-2xl font-bold text-slate-900">{stats.in_progress}</div>
            <div className="text-[11px] text-blue-600 mt-1 font-medium">Under active troubleshooting</div>
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-2xs">
            <div className="flex items-center justify-between text-slate-500 mb-2">
              <span className="text-xs font-bold uppercase tracking-wider">Resolved</span>
              <CheckCircle2 className="w-4 h-4 text-emerald-500" />
            </div>
            <div className="text-2xl font-bold text-slate-900">{stats.resolved}</div>
            <div className="text-[11px] text-emerald-600 mt-1 font-medium">Completed tickets</div>
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-2xs">
            <div className="flex items-center justify-between text-slate-500 mb-2">
              <span className="text-xs font-bold uppercase tracking-wider">Total Filed</span>
              <LifeBuoy className="w-4 h-4 text-indigo-500" />
            </div>
            <div className="text-2xl font-bold text-slate-900">{stats.total}</div>
            <div className="text-[11px] text-slate-500 mt-1 font-medium">Lifetime requests</div>
          </div>
        </div>

        {/* Filters and Search Bar */}
        <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3">
          <div className="flex flex-wrap items-center gap-2 w-full md:w-auto text-xs">
            <div className="flex items-center space-x-1.5 text-slate-500 font-semibold mr-1">
              <Filter className="w-3.5 h-3.5" />
              <span>Filter:</span>
            </div>

            {/* Status Select */}
            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
              className="px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="all">All Statuses</option>
              <option value="open">Open</option>
              <option value="in_progress">In Progress</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>

            {/* Priority Select */}
            <select
              value={filterPriority}
              onChange={(e) => setFilterPriority(e.target.value)}
              className="px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="all">All Priorities</option>
              <option value="critical">Critical</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>

            {/* Category Select */}
            <select
              value={filterCategory}
              onChange={(e) => setFilterCategory(e.target.value)}
              className="px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="all">All Categories</option>
              <option value="hardware">Hardware / Laptop / Desktop</option>
              <option value="software">Software / OS / License</option>
              <option value="network">Network / Wi-Fi / VPN</option>
              <option value="access_credentials">Account & Credentials</option>
              <option value="email">Email & Communication</option>
              <option value="printer">Printer & Peripherals</option>
              <option value="other">Other Technical Issue</option>
            </select>
          </div>

          {/* Search Box */}
          <form
            onSubmit={(e) => {
              e.preventDefault();
              loadTickets();
            }}
            className="relative w-full md:w-64"
          >
            <Search className="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400" />
            <input
              type="text"
              placeholder="Search by ticket #, subject..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-800"
            />
          </form>
        </div>

        {/* Tickets Master-Detail Split Layout */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
          {/* Tickets List View */}
          <div className="lg:col-span-5 bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs divide-y divide-slate-100">
            <div className="px-4 py-3 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
              <span className="text-xs font-bold text-slate-700 uppercase tracking-wider">
                Support Tickets ({tickets.length})
              </span>
              <button
                onClick={loadTickets}
                className="text-xs font-semibold text-blue-600 hover:text-blue-700"
              >
                Refresh
              </button>
            </div>

            {loading ? (
              <div className="p-8 text-center text-slate-400 text-xs">
                <div className="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
                Loading support tickets...
              </div>
            ) : tickets.length === 0 ? (
              <div className="p-10 text-center text-slate-500">
                <LifeBuoy className="w-10 h-10 text-slate-300 mx-auto mb-2" />
                <h4 className="text-xs font-bold text-slate-700">No tickets found</h4>
                <p className="text-[11px] text-slate-400 mt-0.5">
                  No support tickets match the current filter criteria.
                </p>
              </div>
            ) : (
              <div className="divide-y divide-slate-100 max-h-[620px] overflow-y-auto">
                {tickets.map((t) => {
                  const isSelected = selectedTicket?.id === t.id;
                  return (
                    <div
                      key={t.id}
                      onClick={() => loadTicketDetail(t.id)}
                      className={`p-4 transition cursor-pointer flex flex-col justify-between gap-2.5 ${
                        isSelected ? 'bg-blue-50/60 border-l-4 border-l-blue-600' : 'hover:bg-slate-50'
                      }`}
                    >
                      <div className="flex items-center justify-between gap-2">
                        <div className="flex items-center space-x-2">
                          <span className="text-xs font-bold text-blue-700">{t.ticket_number}</span>
                          {renderPriorityBadge(t.priority)}
                        </div>
                        {renderStatusBadge(t.status)}
                      </div>

                      <div>
                        <h4 className="text-xs font-bold text-slate-900 line-clamp-1">{t.subject}</h4>
                        <p className="text-[11px] text-slate-500 line-clamp-2 mt-0.5 leading-relaxed">
                          {t.description}
                        </p>
                      </div>

                      <div className="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-slate-100/60">
                        <span className="font-medium text-slate-600">
                          {t.employee?.name || 'Staff Member'}
                        </span>
                        <div className="flex items-center space-x-2">
                          {t.replies_count ? (
                            <span className="flex items-center gap-1 font-semibold text-slate-600">
                              <MessageSquare className="w-3 h-3 text-slate-400" />
                              {t.replies_count}
                            </span>
                          ) : null}
                          <span>{new Date(t.created_at).toLocaleDateString()}</span>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          {/* Ticket Detail / Conversation Thread View */}
          <div className="lg:col-span-7 bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
            {selectedTicket ? (
              <div className="flex flex-col h-full">
                {/* Detail Header */}
                <div className="p-5 border-b border-slate-100 bg-slate-50/50 space-y-3">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div className="flex items-center space-x-2">
                      <span className="text-sm font-bold text-blue-600">{selectedTicket.ticket_number}</span>
                      {renderPriorityBadge(selectedTicket.priority)}
                      {renderStatusBadge(selectedTicket.status)}
                    </div>
                    <span className="text-xs text-slate-400">
                      Filed on {new Date(selectedTicket.created_at).toLocaleString()}
                    </span>
                  </div>

                  <div>
                    <h3 className="text-base font-bold text-slate-900">{selectedTicket.subject}</h3>
                    <div className="flex items-center gap-3 text-xs text-slate-500 mt-1">
                      <span className="flex items-center gap-1">
                        <User className="w-3.5 h-3.5 text-slate-400" />
                        {selectedTicket.employee?.name} ({selectedTicket.employee?.employee_code})
                      </span>
                      <span>&bull;</span>
                      <span className="capitalize">{selectedTicket.category.replace('_', ' ')}</span>
                      {selectedTicket.employee?.department && (
                        <>
                          <span>&bull;</span>
                          <span>{selectedTicket.employee.department.name}</span>
                        </>
                      )}
                    </div>
                  </div>

                  {/* Description Box */}
                  <div className="bg-white rounded-xl p-3.5 border border-slate-200 text-xs text-slate-700 leading-relaxed space-y-2">
                    <p className="whitespace-pre-wrap">{selectedTicket.description}</p>
                    {selectedTicket.attachment_url && (
                      <div className="pt-2 border-t border-slate-100">
                        <a
                          href={selectedTicket.attachment_url}
                          target="_blank"
                          rel="noreferrer"
                          className="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-700 gap-1.5"
                        >
                          <Paperclip className="w-3.5 h-3.5" />
                          View Uploaded Diagnostic Attachment
                        </a>
                      </div>
                    )}
                  </div>

                  {/* Admin Resolution & Controls */}
                  {isAdminOrSuper && (
                    <div className="bg-blue-50/60 rounded-xl p-3.5 border border-blue-100 text-xs space-y-3">
                      <div className="flex items-center justify-between">
                        <span className="font-bold text-blue-900 flex items-center gap-1.5">
                          <Shield className="w-4 h-4 text-blue-600" />
                          Administrator Ticket Management
                        </span>
                        <button
                          onClick={handleUpdateTicketStatus}
                          className="px-3 py-1 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition"
                        >
                          Save Management Status
                        </button>
                      </div>

                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="block text-slate-600 font-semibold mb-1">Update Status</label>
                          <select
                            value={manageForm.status}
                            onChange={(e) => setManageForm({ ...manageForm, status: e.target.value })}
                            className="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                          >
                            <option value="open">Open (Unresolved)</option>
                            <option value="in_progress">In Progress (Troubleshooting)</option>
                            <option value="resolved">Resolved (Completed)</option>
                            <option value="closed">Closed</option>
                          </select>
                        </div>
                        <div>
                          <label className="block text-slate-600 font-semibold mb-1">Update Priority</label>
                          <select
                            value={manageForm.priority}
                            onChange={(e) => setManageForm({ ...manageForm, priority: e.target.value })}
                            className="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                          >
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                          </select>
                        </div>
                      </div>

                      <div>
                        <label className="block text-slate-600 font-semibold mb-1">
                          Resolution Summary / Root Cause Notes
                        </label>
                        <input
                          type="text"
                          placeholder="e.g. Swapped network cable; assigned static IP; replaced RAM stick."
                          value={manageForm.resolution_notes}
                          onChange={(e) => setManageForm({ ...manageForm, resolution_notes: e.target.value })}
                          className="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                      </div>
                    </div>
                  )}

                  {/* Display Resolution Note to Staff if Resolved */}
                  {!isAdminOrSuper && selectedTicket.resolution_notes && (
                    <div className="bg-emerald-50 rounded-xl p-3 border border-emerald-200 text-xs text-emerald-800">
                      <span className="font-bold block mb-0.5">IT Team Resolution Summary:</span>
                      <p>{selectedTicket.resolution_notes}</p>
                    </div>
                  )}
                </div>

                {/* Conversation Thread */}
                <div className="p-5 flex-1 space-y-4 max-h-[350px] overflow-y-auto bg-slate-50/30">
                  <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">
                    Support Conversation Thread ({selectedTicket.replies?.length || 0})
                  </span>

                  {(!selectedTicket.replies || selectedTicket.replies.length === 0) ? (
                    <div className="text-center py-6 text-xs text-slate-400">
                      No replies on this ticket yet. Add an update below.
                    </div>
                  ) : (
                    selectedTicket.replies.map((reply) => {
                      const isStaffUser = reply.user_id === user?.id;
                      return (
                        <div
                          key={reply.id}
                          className={`flex flex-col ${isStaffUser ? 'items-end' : 'items-start'}`}
                        >
                          <div
                            className={`max-w-[85%] rounded-2xl p-3.5 text-xs space-y-1 shadow-2xs ${
                              isStaffUser
                                ? 'bg-blue-600 text-white rounded-br-xs'
                                : 'bg-white border border-slate-200 text-slate-800 rounded-bl-xs'
                            }`}
                          >
                            <div className="flex items-center justify-between gap-3 text-[10px] opacity-80 pb-1 border-b border-current/10">
                              <span className="font-semibold">
                                {reply.user?.name} {reply.user?.role !== 'employee' ? `(${reply.user?.role})` : ''}
                              </span>
                              <span>{new Date(reply.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                            </div>
                            <p className="whitespace-pre-wrap leading-relaxed pt-1">{reply.message}</p>
                            {reply.attachment_url && (
                              <div className="pt-1.5 border-t border-current/10 mt-1.5">
                                <a
                                  href={reply.attachment_url}
                                  target="_blank"
                                  rel="noreferrer"
                                  className={`inline-flex items-center gap-1 font-semibold underline text-[11px] ${
                                    isStaffUser ? 'text-white' : 'text-blue-600'
                                  }`}
                                >
                                  <Paperclip className="w-3 h-3" />
                                  Attachment
                                </a>
                              </div>
                            )}
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>

                {/* Reply Input Box */}
                <form onSubmit={handleSendReply} className="p-4 border-t border-slate-100 bg-white space-y-2">
                  <div className="flex gap-2">
                    <input
                      type="text"
                      placeholder="Type a message or troubleshoot note..."
                      value={replyMessage}
                      onChange={(e) => setReplyMessage(e.target.value)}
                      className="flex-1 px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none focus:bg-white"
                    />
                    <label
                      htmlFor="replyFileInput"
                      className="px-3 py-2 border border-slate-200 rounded-xl bg-slate-50 text-slate-600 hover:bg-slate-100 cursor-pointer flex items-center justify-center"
                      title="Attach file / screenshot"
                    >
                      <Paperclip className="w-4 h-4" />
                      <input
                        type="file"
                        id="replyFileInput"
                        className="hidden"
                        onChange={(e) => {
                          if (e.target.files && e.target.files[0]) {
                            setReplyAttachment(e.target.files[0]);
                          }
                        }}
                      />
                    </label>
                    <button
                      type="submit"
                      disabled={submittingReply || !replyMessage.trim()}
                      className="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-semibold hover:bg-blue-700 disabled:opacity-50 flex items-center gap-1.5"
                    >
                      <Send className="w-3.5 h-3.5" />
                      <span>Send</span>
                    </button>
                  </div>
                  {replyAttachment && (
                    <div className="flex items-center justify-between text-[11px] bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg">
                      <span className="truncate">Attached: {replyAttachment.name}</span>
                      <button
                        type="button"
                        onClick={() => setReplyAttachment(null)}
                        className="text-slate-400 hover:text-slate-600 ml-2"
                      >
                        <X className="w-3 h-3" />
                      </button>
                    </div>
                  )}
                </form>
              </div>
            ) : (
              <div className="p-16 text-center text-slate-400">
                <LifeBuoy className="w-12 h-12 text-slate-200 mx-auto mb-3" />
                <h4 className="text-sm font-bold text-slate-700">Select a Support Ticket</h4>
                <p className="text-xs text-slate-400 max-w-xs mx-auto mt-1">
                  Choose a ticket from the left panel to review full issue diagnostics, conversation updates, and resolution status.
                </p>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* MODAL: RAISE NEW IT TICKET */}
      {isNewTicketOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center space-x-2">
                <LifeBuoy className="w-5 h-5 text-blue-600" />
                <h3 className="text-sm font-bold text-slate-800">Raise IT Support Ticket</h3>
              </div>
              <button
                onClick={() => setIsNewTicketOpen(false)}
                className="text-slate-400 hover:text-slate-600"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleCreateTicket} className="space-y-4 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Issue Category *</label>
                  <select
                    value={newTicketForm.category}
                    onChange={(e) => setNewTicketForm({ ...newTicketForm, category: e.target.value })}
                    className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white"
                  >
                    <option value="hardware">Hardware / Laptop / PC</option>
                    <option value="software">Software / OS / Applications</option>
                    <option value="network">Network / Wi-Fi / VPN</option>
                    <option value="access_credentials">Account / Password / SSO</option>
                    <option value="email">Email & Outlook</option>
                    <option value="printer">Printer & Peripherals</option>
                    <option value="other">Other Technical Issue</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Priority Level *</label>
                  <select
                    value={newTicketForm.priority}
                    onChange={(e) => setNewTicketForm({ ...newTicketForm, priority: e.target.value })}
                    className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white"
                  >
                    <option value="low">Low (General Query)</option>
                    <option value="medium">Medium (Standard Issue)</option>
                    <option value="high">High (Impacting Daily Work)</option>
                    <option value="critical">Critical (Workstation Down / Blocker)</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Subject / Summary *</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. Laptop monitor flickering and shutting down"
                  value={newTicketForm.subject}
                  onChange={(e) => setNewTicketForm({ ...newTicketForm, subject: e.target.value })}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Detailed Description *</label>
                <textarea
                  rows={4}
                  required
                  placeholder="Explain the technical issue, error codes, steps to reproduce, or required software installations..."
                  value={newTicketForm.description}
                  onChange={(e) => setNewTicketForm({ ...newTicketForm, description: e.target.value })}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">
                  Screenshot or Diagnostic File (Optional)
                </label>
                <input
                  type="file"
                  onChange={(e) => {
                    if (e.target.files && e.target.files[0]) {
                      setNewTicketForm({ ...newTicketForm, attachment: e.target.files[0] });
                    }
                  }}
                  className="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                />
                <span className="text-[10px] text-slate-400 mt-1 block">Maximum file size: 15MB</span>
              </div>

              <div className="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setIsNewTicketOpen(false)}
                  className="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg font-medium"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="px-4 py-1.5 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50"
                >
                  {isSubmitting ? 'Submitting...' : 'Submit Support Ticket'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </AppLayout>
  );
}

