'use client';

import React, { useState, useEffect } from 'react';
import ProtectedRoute from '@/components/ProtectedRoute';
import AppLayout from '@/components/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { apiRequest } from '@/lib/api';
import {
  LeaveType,
  LeaveBalance,
  LeaveApplication,
  Holiday,
  MyLeaveOverviewResponse,
  AdminLeaveStats,
} from '@/types/leave';
import {
  Calendar,
  Clock,
  CheckCircle2,
  XCircle,
  AlertCircle,
  Plus,
  Send,
  Shield,
  User,
  Users,
  Search,
  Check,
  X,
  FileText,
  CalendarDays,
  Sparkles,
  HelpCircle,
  ArrowRight,
  ChevronRight,
  Filter,
  Layers,
} from 'lucide-react';

export default function LeavesPage() {
  const { user, token } = useAuth();
  const isSuperadmin = user?.role === 'superadmin';
  const isAdminOrSuperadmin = user?.role === 'admin' || user?.role === 'superadmin';

  // Active Tab
  const [activeTab, setActiveTab] = useState<'my_leaves' | 'admin_approvals' | 'leave_balances' | 'holidays_ledger'>(
    'my_leaves'
  );

  // Data States
  const [overview, setOverview] = useState<MyLeaveOverviewResponse | null>(null);
  const [loadingOverview, setLoadingOverview] = useState(true);

  // Admin applications
  const [adminApplications, setAdminApplications] = useState<LeaveApplication[]>([]);
  const [adminStats, setAdminStats] = useState<AdminLeaveStats | null>(null);
  const [loadingAdminApps, setLoadingAdminApps] = useState(false);
  const [adminFilterStatus, setAdminFilterStatus] = useState<string>('pending');

  // Balances Ledger
  const [balanceEmployees, setBalanceEmployees] = useState<any[]>([]);
  const [leaveTypes, setLeaveTypes] = useState<LeaveType[]>([]);
  const [loadingBalances, setLoadingBalances] = useState(false);
  const [globalBalanceModal, setGlobalBalanceModal] = useState<{
    isOpen: boolean;
    leaveTypeId: number | string;
    allocatedDays: number | string;
    year: number;
  }>({
    isOpen: false,
    leaveTypeId: '',
    allocatedDays: 12,
    year: 2026,
  });
  const [submittingGlobalBalance, setSubmittingGlobalBalance] = useState(false);

  const [balanceModal, setBalanceModal] = useState<{
    isOpen: boolean;
    employee: any | null;
    leaveTypeId: number | string;
    allocatedDays: number | string;
    year: number;
  }>({
    isOpen: false,
    employee: null,
    leaveTypeId: '',
    allocatedDays: 12,
    year: 2026,
  });

  // Holidays
  const [holidays, setHolidays] = useState<Holiday[]>([]);
  const [loadingHolidays, setLoadingHolidays] = useState(false);
  const [holidayModal, setHolidayModal] = useState<{
    isOpen: boolean;
    title: string;
    date: string;
    type: 'gazetted' | 'restricted' | 'optional' | 'company';
    description: string;
  }>({
    isOpen: false,
    title: '',
    date: '',
    type: 'gazetted',
    description: '',
  });

  // Apply Leave Modal
  const [applyModalOpen, setApplyModalOpen] = useState(false);
  const [applyForm, setApplyForm] = useState({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    is_half_day: false,
    half_day_type: 'first_half',
    reason: '',
  });
  const [submittingApply, setSubmittingApply] = useState(false);

  // Review / Decision Modal (Approve / Reject)
  const [decisionModal, setDecisionModal] = useState<{
    isOpen: boolean;
    app: LeaveApplication | null;
    targetStatus: 'approved' | 'rejected' | null;
    remarks: string;
  }>({
    isOpen: false,
    app: null,
    targetStatus: null,
    remarks: '',
  });
  const [submittingDecision, setSubmittingDecision] = useState(false);

  // Global Toast
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Auto clear toast
  useEffect(() => {
    if (toast) {
      const timer = setTimeout(() => setToast(null), 4000);
      return () => clearTimeout(timer);
    }
  }, [toast]);

  // Load My Leave Overview
  const fetchOverview = async () => {
    try {
      setLoadingOverview(true);
      const res = await apiRequest<MyLeaveOverviewResponse>('/leaves/overview', {}, token || undefined);
      setOverview(res);
      if (res?.balances && res.balances.length > 0 && !applyForm.leave_type_id) {
        setApplyForm((prev) => ({ ...prev, leave_type_id: res.balances[0].leave_type_id.toString() }));
      }
    } catch (err: any) {
      setToast({ type: 'error', message: err.message || 'Failed to load leave overview' });
    } finally {
      setLoadingOverview(false);
    }
  };

  // Load Admin Applications
  const fetchAdminApplications = async () => {
    try {
      setLoadingAdminApps(true);
      const res = await apiRequest<{ stats: AdminLeaveStats; applications: { data: LeaveApplication[] } }>(
        `/leaves/admin/applications?status=${adminFilterStatus}`,
        {},
        token || undefined
      );
      setAdminApplications(res.applications?.data || []);
      setAdminStats(res.stats || null);
    } catch (err: any) {
      setToast({ type: 'error', message: err.message || 'Failed to load leave applications roster' });
    } finally {
      setLoadingAdminApps(false);
    }
  };

  // Load Balances Ledger (Superadmin / Admin)
  const fetchBalancesLedger = async () => {
    try {
      setLoadingBalances(true);
      const year = balanceModal.year || 2026;
      const res = await apiRequest<{ leave_types: LeaveType[]; employees: { data: any[] } }>(
        `/leaves/admin/balances?year=${year}`,
        {},
        token || undefined
      );
      setLeaveTypes(res.leave_types || []);
      setBalanceEmployees(res.employees?.data || []);
      if (res.leave_types?.length > 0 && !balanceModal.leaveTypeId) {
        setBalanceModal((prev) => ({ ...prev, leaveTypeId: res.leave_types[0].id }));
      }
    } catch (err: any) {
      setToast({ type: 'error', message: err.message || 'Failed to load balances ledger' });
    } finally {
      setLoadingBalances(false);
    }
  };

  // Load Holidays
  const fetchHolidays = async () => {
    try {
      setLoadingHolidays(true);
      const year = balanceModal.year || 2026;
      const res = await apiRequest<{ holidays: Holiday[] }>(
        `/holidays?year=${year}`,
        {},
        token || undefined
      );
      setHolidays(res.holidays || []);
    } catch (err: any) {
      setToast({ type: 'error', message: err.message || 'Failed to load holidays calendar' });
    } finally {
      setLoadingHolidays(false);
    }
  };

  // Initial Load
  useEffect(() => {
    const todayStr = new Date().toISOString().split('T')[0];
    const currentYear = new Date().getFullYear();
    setApplyForm((prev) => ({
      ...prev,
      start_date: prev.start_date || todayStr,
      end_date: prev.end_date || todayStr,
    }));
    setHolidayModal((prev) => ({
      ...prev,
      date: prev.date || todayStr,
    }));
    setBalanceModal((prev) => ({
      ...prev,
      year: currentYear,
    }));
    fetchOverview();
  }, [token]);

  // Tab change triggers
  useEffect(() => {
    if (activeTab === 'admin_approvals') {
      fetchAdminApplications();
    } else if (activeTab === 'leave_balances') {
      fetchBalancesLedger();
    } else if (activeTab === 'holidays_ledger') {
      fetchHolidays();
    }
  }, [activeTab, adminFilterStatus]);

  // Submit Leave Application
  const handleApplyLeave = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSubmittingApply(true);
      const res = await apiRequest<{ message: string }>('/leaves/apply', {
        method: 'POST',
        body: JSON.stringify(applyForm),
      }, token || undefined);

      setToast({ type: 'success', message: res.message });
      setApplyModalOpen(false);
      setApplyForm({
        leave_type_id: overview?.balances?.[0]?.leave_type_id?.toString() || '',
        start_date: new Date().toISOString().split('T')[0],
        end_date: new Date().toISOString().split('T')[0],
        is_half_day: false,
        half_day_type: 'first_half',
        reason: '',
      });
      fetchOverview();
    } catch (err: any) {
      setToast({ type: 'error', message: err.message || 'Failed to submit leave application' });
    } finally {
      setSubmittingApply(false);
    }
  };

  // Submit Decision (Approve / Reject)
  const handleDecisionSubmit = async () => {
    if (!decisionModal.app || !decisionModal.targetStatus) return;
    try {
      setSubmittingDecision(true);
      const res = await apiRequest<{ message: string }>(
        `/leaves/admin/applications/${decisionModal.app.id}/status`,
        {
          method: 'PUT',
          body: JSON.stringify({
            status: decisionModal.targetStatus,
            admin_remarks: decisionModal.remarks,
          }),
        },
        token || undefined
      );

      setToast({ type: 'success', message: res.message });
      setDecisionModal({ isOpen: false, app: null, targetStatus: null, remarks: '' });
      fetchAdminApplications();
      fetchOverview();
    } catch (err: any) {
      setToast({ type: 'error', message: err.message || 'Failed to update leave status' });
    } finally {
      setSubmittingDecision(false);
    }
  };

  // Save Global Leave Balance Across All Employees
  const handleSaveGlobalBalance = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!globalBalanceModal.leaveTypeId) return;
    try {
      setSubmittingGlobalBalance(true);
      const res = await apiRequest<{ message: string }>('/leaves/admin/balances/global', {
        method: 'POST',
        body: JSON.stringify({
          leave_type_id: Number(globalBalanceModal.leaveTypeId),
          year: globalBalanceModal.year,
          allocated_days: Number(globalBalanceModal.allocatedDays),
        }),
      }, token || undefined);

      setToast({ type: 'success', message: res.message });
      setGlobalBalanceModal((prev) => ({ ...prev, isOpen: false }));
      fetchBalancesLedger();
      fetchOverview();
    } catch (err: any) {
      setToast({ type: 'error', message: err.message || 'Failed to update global leave balance' });
    } finally {
      setSubmittingGlobalBalance(false);
    }
  };

  // Save Leave Balance (Individual override)
  const handleSaveBalance = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!balanceModal.employee) return;
    try {
      const res = await apiRequest<{ message: string }>('/leaves/admin/balances', {
        method: 'POST',
        body: JSON.stringify({
          employee_id: balanceModal.employee.id,
          leave_type_id: Number(balanceModal.leaveTypeId),
          year: balanceModal.year,
          allocated_days: Number(balanceModal.allocatedDays),
        }),
      }, token || undefined);

      setToast({ type: 'success', message: res.message });
      setBalanceModal((prev) => ({ ...prev, isOpen: false }));
      fetchBalancesLedger();
    } catch (err: any) {
      setToast({ type: 'error', message: err.message || 'Failed to update leave balance' });
    }
  };

  // Save Holiday
  const handleSaveHoliday = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await apiRequest<{ message: string }>('/holidays', {
        method: 'POST',
        body: JSON.stringify(holidayModal),
      }, token || undefined);

      setToast({ type: 'success', message: res.message });
      setHolidayModal({
        isOpen: false,
        title: '',
        date: new Date().toISOString().split('T')[0],
        type: 'gazetted',
        description: '',
      });
      fetchHolidays();
    } catch (err: any) {
      setToast({ type: 'error', message: err.message || 'Failed to save holiday' });
    }
  };

  // Delete Holiday
  const handleDeleteHoliday = async (id: number) => {
    if (!confirm('Are you sure you want to remove this holiday from the corporate calendar?')) return;
    try {
      const res = await apiRequest<{ message: string }>(`/holidays/${id}`, { method: 'DELETE' }, token || undefined);
      setToast({ type: 'success', message: res.message });
      fetchHolidays();
    } catch (err: any) {
      setToast({ type: 'error', message: err.message || 'Failed to delete holiday' });
    }
  };

  return (
    <ProtectedRoute>
      <AppLayout
        title="Leaves & Holiday Suite"
        subtitle="Request time off, track annual quota, manage approvals, and observe company holidays"
        action={
          <div className="flex items-center gap-2">
            <button
              onClick={() => setApplyModalOpen(true)}
              className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition cursor-pointer"
            >
              <Plus className="w-4 h-4" />
              Apply Leave
            </button>
            {isAdminOrSuperadmin && (
              <button
                onClick={() => setHolidayModal((prev) => ({ ...prev, isOpen: true }))}
                className="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs sm:text-sm font-semibold rounded-lg shadow-xs transition cursor-pointer"
              >
                <CalendarDays className="w-4 h-4 text-slate-500" />
                + Add Holiday
              </button>
            )}
          </div>
        }
      >
        {/* Toast Notification */}
        {toast && (
          <div className="fixed top-4 right-4 z-50 animate-bounce">
            <div
              className={`px-4 py-3 rounded-xl shadow-lg text-xs sm:text-sm font-medium flex items-center gap-3 text-white ${
                toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'
              }`}
            >
              {toast.type === 'success' ? <CheckCircle2 className="w-4 h-4" /> : <AlertCircle className="w-4 h-4" />}
              <span>{toast.message}</span>
              <button onClick={() => setToast(null)} className="text-white/80 hover:text-white font-bold ml-2">
                ×
              </button>
            </div>
          </div>
        )}

        <div className="p-4 sm:p-6 lg:p-8 space-y-6">
          {/* Main Navigation Tabs */}
          <div className="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto">
            <button
              onClick={() => setActiveTab('my_leaves')}
              className={`px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer flex items-center gap-2 whitespace-nowrap ${
                activeTab === 'my_leaves'
                  ? 'bg-blue-600 text-white shadow-xs'
                  : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
              }`}
            >
              <User className="w-4 h-4" />
              My Leaves & Quota
            </button>

            {isAdminOrSuperadmin && (
              <button
                onClick={() => setActiveTab('admin_approvals')}
                className={`px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer flex items-center gap-2 whitespace-nowrap ${
                  activeTab === 'admin_approvals'
                    ? 'bg-blue-600 text-white shadow-xs'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                }`}
              >
                <Shield className="w-4 h-4" />
                Team Approvals
                {adminStats && adminStats.pending_count > 0 && (
                  <span className="bg-amber-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold">
                    {adminStats.pending_count}
                  </span>
                )}
              </button>
            )}

            {isSuperadmin && (
              <button
                onClick={() => setActiveTab('leave_balances')}
                className={`px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer flex items-center gap-2 whitespace-nowrap ${
                  activeTab === 'leave_balances'
                    ? 'bg-blue-600 text-white shadow-xs'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                }`}
              >
                <Layers className="w-4 h-4" />
                Employee Balances Setup
              </button>
            )}

            <button
              onClick={() => setActiveTab('holidays_ledger')}
              className={`px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer flex items-center gap-2 whitespace-nowrap ${
                activeTab === 'holidays_ledger'
                  ? 'bg-blue-600 text-white shadow-xs'
                  : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
              }`}
            >
              <CalendarDays className="w-4 h-4" />
              Holidays Calendar
            </button>
          </div>

          {/* TAB 1: MY LEAVES & QUOTA */}
          {activeTab === 'my_leaves' && (
            <div className="space-y-6">
              {/* Employee Summary Card */}
              {loadingOverview ? (
                <div className="p-8 text-center bg-white rounded-xl border border-slate-200">
                  <div className="w-7 h-7 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
                  <p className="text-xs text-slate-500">Loading leave entitlements and quotas...</p>
                </div>
              ) : (
                <>
                  {/* Balance Cards Grid */}
                  <div>
                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">
                      Leave Entitlement & Remaining Balances ({overview?.current_year})
                    </h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                      {overview?.balances?.map((b) => (
                        <div
                          key={b.id}
                          className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs relative overflow-hidden flex flex-col justify-between"
                        >
                          <div className="flex items-start justify-between">
                            <div>
                              <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {b.leave_type?.code || 'LEAVE'}
                              </span>
                              <h4 className="text-sm font-bold text-slate-800 mt-0.5">{b.leave_type?.name}</h4>
                            </div>
                            <span className="p-2 rounded-lg bg-blue-50 text-blue-600">
                              <Calendar className="w-4 h-4" />
                            </span>
                          </div>

                          <div className="mt-4 flex items-baseline justify-between border-t border-slate-100 pt-3">
                            <div>
                              <div className="text-2xl font-bold text-slate-900">{b.remaining_days}</div>
                              <div className="text-[11px] text-slate-500">Remaining Days</div>
                            </div>
                            <div className="text-right text-[11px] text-slate-500 space-y-0.5">
                              <div>Quota: <span className="font-semibold text-slate-700">{b.allocated_days}</span></div>
                              <div>Used: <span className="font-semibold text-slate-700">{b.used_days}</span></div>
                              {b.pending_days > 0 && (
                                <div className="text-amber-600 font-semibold">Pending: {b.pending_days}</div>
                              )}
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Split Row: Recent Applications + Upcoming Holidays */}
                  <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left: Applications Table */}
                    <div className="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                      <div className="p-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                          <h3 className="text-sm font-bold text-slate-900">Recent Leave Requests</h3>
                          <p className="text-xs text-slate-500">Track approvals and remarks from reporting authority</p>
                        </div>
                        <button
                          onClick={() => setApplyModalOpen(true)}
                          className="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                        >
                          + New Application
                        </button>
                      </div>

                      {overview?.recent_applications?.length === 0 ? (
                        <div className="p-8 text-center text-slate-400 text-xs">
                          No leave applications submitted yet for this cycle.
                        </div>
                      ) : (
                        <div className="overflow-x-auto">
                          <table className="w-full text-left text-xs border-collapse">
                            <thead>
                              <tr className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100 uppercase tracking-wider">
                                <th className="px-4 py-3">Leave Type</th>
                                <th className="px-4 py-3">Duration & Dates</th>
                                <th className="px-4 py-3">Days</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Review Remarks</th>
                              </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                              {overview?.recent_applications?.map((app) => (
                                <tr key={app.id} className="hover:bg-slate-50/50">
                                  <td className="px-4 py-3 font-semibold text-slate-800">
                                    {app.leave_type?.name}
                                  </td>
                                  <td className="px-4 py-3 text-slate-600">
                                    <div>{app.start_date} {app.start_date !== app.end_date && `to ${app.end_date}`}</div>
                                    <div className="text-[11px] text-slate-400 truncate max-w-xs">{app.reason}</div>
                                  </td>
                                  <td className="px-4 py-3">
                                    <span className="font-bold text-slate-800">{app.total_days}</span> day(s)
                                    {app.is_half_day && <span className="block text-[10px] text-blue-600">Half Day ({app.half_day_type})</span>}
                                  </td>
                                  <td className="px-4 py-3">
                                    <span
                                      className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                                        app.status === 'approved'
                                          ? 'bg-emerald-100 text-emerald-800'
                                          : app.status === 'rejected'
                                          ? 'bg-red-100 text-red-800'
                                          : 'bg-amber-100 text-amber-800'
                                      }`}
                                    >
                                      {app.status}
                                    </span>
                                  </td>
                                  <td className="px-4 py-3 text-slate-500 text-[11px]">
                                    {app.admin_remarks || '—'}
                                  </td>
                                </tr>
                              ))}
                            </tbody>
                          </table>
                        </div>
                      )}
                    </div>

                    {/* Right: Upcoming Holidays Card */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-4">
                      <div>
                        <h3 className="text-sm font-bold text-slate-900">Upcoming Holidays</h3>
                        <p className="text-xs text-slate-500">Official company declared holidays</p>
                      </div>

                      <div className="space-y-3">
                        {overview?.upcoming_holidays?.map((h) => (
                          <div
                            key={h.id}
                            className="p-3 rounded-lg border border-slate-100 bg-slate-50 flex items-center justify-between"
                          >
                            <div>
                              <div className="font-semibold text-xs text-slate-800">{h.title}</div>
                              <div className="text-[11px] text-slate-500">
                                {h.date} • {h.day_name}
                              </div>
                            </div>
                            <span className="px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-blue-50 text-blue-700">
                              {h.type}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </>
              )}
            </div>
          )}

          {/* TAB 2: TEAM APPROVALS (Admin / Superadmin) */}
          {activeTab === 'admin_approvals' && (
            <div className="space-y-6">
              {/* Metrics Header */}
              {adminStats && (
                <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
                  <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
                    <div className="text-xs text-slate-500 font-semibold uppercase">Pending Reviews</div>
                    <div className="text-2xl font-bold text-amber-600 mt-1">{adminStats.pending_count}</div>
                  </div>
                  <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
                    <div className="text-xs text-slate-500 font-semibold uppercase">Approved This Month</div>
                    <div className="text-2xl font-bold text-emerald-600 mt-1">{adminStats.approved_this_month}</div>
                  </div>
                  <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
                    <div className="text-xs text-slate-500 font-semibold uppercase">Rejected Total</div>
                    <div className="text-2xl font-bold text-red-600 mt-1">{adminStats.rejected_count}</div>
                  </div>
                  <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
                    <div className="text-xs text-slate-500 font-semibold uppercase">Workforce Roster</div>
                    <div className="text-2xl font-bold text-slate-800 mt-1">{adminStats.active_employees}</div>
                  </div>
                </div>
              )}

              {/* Filter Bar */}
              <div className="flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
                <div className="flex items-center gap-2">
                  <Filter className="w-4 h-4 text-slate-400" />
                  <span className="text-xs font-semibold text-slate-700">Filter By Status:</span>
                  <select
                    value={adminFilterStatus}
                    onChange={(e) => setAdminFilterStatus(e.target.value)}
                    className="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-600"
                  >
                    <option value="">All Statuses</option>
                    <option value="pending">Pending Review Only</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                  </select>
                </div>

                <button
                  onClick={fetchAdminApplications}
                  className="text-xs text-blue-600 hover:underline font-semibold cursor-pointer"
                >
                  Refresh Applications
                </button>
              </div>

              {/* Applications Ledger Table */}
              <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                {loadingAdminApps ? (
                  <div className="p-8 text-center">
                    <div className="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
                    <p className="text-xs text-slate-500">Fetching applications roster...</p>
                  </div>
                ) : adminApplications.length === 0 ? (
                  <div className="p-12 text-center text-xs text-slate-400">
                    No applications match the current filter.
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs border-collapse">
                      <thead>
                        <tr className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                          <th className="px-5 py-3.5">Employee</th>
                          <th className="px-5 py-3.5">Leave Type</th>
                          <th className="px-5 py-3.5">Duration</th>
                          <th className="px-5 py-3.5">Days</th>
                          <th className="px-5 py-3.5">Reason & Purpose</th>
                          <th className="px-5 py-3.5">Status</th>
                          <th className="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {adminApplications.map((app) => (
                          <tr key={app.id} className="hover:bg-slate-50/70 transition">
                            <td className="px-5 py-3.5">
                              <div className="font-bold text-slate-900">
                                {app.employee?.first_name} {app.employee?.last_name || ''}
                              </div>
                              <div className="text-[11px] text-slate-500">
                                {app.employee?.employee_id} • {app.employee?.designation?.name || 'Staff'}
                              </div>
                            </td>
                            <td className="px-5 py-3.5 font-semibold text-slate-800">
                              {app.leave_type?.name}
                            </td>
                            <td className="px-5 py-3.5 text-slate-600">
                              <div>{app.start_date} {app.start_date !== app.end_date && `to ${app.end_date}`}</div>
                            </td>
                            <td className="px-5 py-3.5 font-bold text-slate-800">
                              {app.total_days} {app.is_half_day && '(Half)'}
                            </td>
                            <td className="px-5 py-3.5 text-slate-600 max-w-xs truncate">
                              {app.reason}
                            </td>
                            <td className="px-5 py-3.5">
                              <span
                                className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                                  app.status === 'approved'
                                    ? 'bg-emerald-100 text-emerald-800'
                                    : app.status === 'rejected'
                                    ? 'bg-red-100 text-red-800'
                                    : 'bg-amber-100 text-amber-800 animate-pulse'
                                }`}
                              >
                                {app.status}
                              </span>
                            </td>
                            <td className="px-5 py-3.5 text-right">
                              {app.status === 'pending' ? (
                                <div className="flex items-center justify-end gap-1.5">
                                  <button
                                    onClick={() =>
                                      setDecisionModal({
                                        isOpen: true,
                                        app,
                                        targetStatus: 'approved',
                                        remarks: 'Approved as per company leave policy.',
                                      })
                                    }
                                    className="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-md text-[11px] cursor-pointer"
                                  >
                                    Approve
                                  </button>
                                  <button
                                    onClick={() =>
                                      setDecisionModal({
                                        isOpen: true,
                                        app,
                                        targetStatus: 'rejected',
                                        remarks: '',
                                      })
                                    }
                                    className="px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-md text-[11px] cursor-pointer"
                                  >
                                    Reject
                                  </button>
                                </div>
                              ) : (
                                <span className="text-[11px] text-slate-400 italic">
                                  Actioned by {app.action_by_user?.name || 'Admin'}
                                </span>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* TAB 3: LEAVE BALANCES SETUP (Superadmin) */}
          {activeTab === 'leave_balances' && isSuperadmin && (
            <div className="space-y-6">
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <div>
                  <div className="flex items-center gap-2">
                    <h3 className="text-base font-bold text-slate-900">Global Leave Quota Setup</h3>
                    <span className="bg-blue-100 text-blue-700 font-bold text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider">
                      Company-Wide
                    </span>
                  </div>
                  <p className="text-xs text-slate-500 mt-1 max-w-2xl">
                    Update leave quotas globally across all employees in one click. Changes immediately recalculate annual allocations and remaining balances for the entire organization.
                  </p>
                </div>
                <button
                  onClick={() =>
                    setGlobalBalanceModal({
                      isOpen: true,
                      leaveTypeId: leaveTypes[0]?.id || '',
                      allocatedDays: leaveTypes[0]?.default_days_per_year || 12,
                      year: balanceModal.year || 2026,
                    })
                  }
                  className="inline-flex items-center gap-1.5 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold rounded-xl shadow-xs transition cursor-pointer self-start sm:self-auto shrink-0"
                >
                  <Sparkles className="w-4 h-4 text-blue-200" />
                  Update Global Balance
                </button>
              </div>

              {/* Master Leave Types Global Quota Summary Cards */}
              <div>
                <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">
                  Current Global Defaults ({balanceModal.year || 2026})
                </h4>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                  {Array.isArray(leaveTypes) && leaveTypes.map((t) => (
                    <div
                      key={t.id}
                      className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between"
                    >
                      <div className="flex items-start justify-between">
                        <div>
                          <span className="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                            {t.code}
                          </span>
                          <div className="font-bold text-sm text-slate-800 mt-2">{t.name}</div>
                        </div>
                        <span className="p-2 bg-blue-50 text-blue-600 rounded-lg">
                          <Layers className="w-4 h-4" />
                        </span>
                      </div>

                      <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <div>
                          <div className="text-xl font-bold text-slate-900">{t.default_days_per_year} Days</div>
                          <div className="text-[11px] text-slate-500">Default Allocation</div>
                        </div>
                        <button
                          onClick={() =>
                            setGlobalBalanceModal({
                              isOpen: true,
                              leaveTypeId: t.id,
                              allocatedDays: t.default_days_per_year,
                              year: balanceModal.year || 2026,
                            })
                          }
                          className="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline cursor-pointer"
                        >
                          Modify All
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div className="p-4 border-b border-slate-100 flex items-center justify-between">
                  <div>
                    <h4 className="text-sm font-bold text-slate-900">Workforce Balance Ledger</h4>
                    <p className="text-xs text-slate-500">Live breakdown showing each employee's remaining and allocated quota</p>
                  </div>
                  <span className="text-xs font-semibold text-slate-500">
                    Total Staff: <strong className="text-slate-800">{balanceEmployees.length}</strong>
                  </span>
                </div>
                {loadingBalances ? (
                  <div className="p-8 text-center text-xs text-slate-500">Loading quota allocations...</div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs border-collapse">
                      <thead>
                        <tr className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                          <th className="px-5 py-3.5">Employee</th>
                          <th className="px-5 py-3.5">Department</th>
                          <th className="px-5 py-3.5">Leave Balances Breakdown</th>
                          <th className="px-5 py-3.5 text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {balanceEmployees.map((emp) => (
                          <tr key={emp.id} className="hover:bg-slate-50/70">
                            <td className="px-5 py-3.5">
                              <div className="font-bold text-slate-900">
                                {emp.first_name} {emp.last_name || ''}
                              </div>
                              <div className="text-[11px] text-slate-500">{emp.employee_id}</div>
                            </td>
                            <td className="px-5 py-3.5 text-slate-600">
                              {emp.department?.name || 'General'}
                            </td>
                            <td className="px-5 py-3.5">
                              <div className="flex flex-wrap gap-2">
                                {emp.leave_balances?.map((lb: any) => (
                                  <span
                                    key={lb.id}
                                    className="px-2 py-1 rounded-md bg-slate-100 border border-slate-200 text-slate-700 font-mono text-[11px]"
                                  >
                                    <strong className="text-blue-700">{lb.leave_type?.code}:</strong> {lb.remaining_days}/{lb.allocated_days}d
                                  </span>
                                ))}
                                {(!emp.leave_balances || emp.leave_balances.length === 0) && (
                                  <span className="text-slate-400 italic text-[11px]">No balances credited</span>
                                )}
                              </div>
                            </td>
                            <td className="px-5 py-3.5 text-right">
                              <button
                                onClick={() =>
                                  setBalanceModal({
                                    isOpen: true,
                                    employee: emp,
                                    leaveTypeId: leaveTypes[0]?.id || '',
                                    allocatedDays: 12,
                                    year: balanceModal.year || 2026,
                                  })
                                }
                                className="px-3 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold rounded-md text-[11px] cursor-pointer"
                              >
                                Edit / Credit Balance
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* TAB 4: HOLIDAYS CALENDAR LEDGER */}
          {activeTab === 'holidays_ledger' && (
            <div className="space-y-6">
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="text-sm font-bold text-slate-900">Corporate & Public Holiday Roster</h3>
                  <p className="text-xs text-slate-500">Days observed with paid closure across all operations</p>
                </div>
                {isAdminOrSuperadmin && (
                  <button
                    onClick={() => setHolidayModal((prev) => ({ ...prev, isOpen: true }))}
                    className="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-xs flex items-center gap-1 cursor-pointer"
                  >
                    <Plus className="w-4 h-4" />
                    New Holiday
                  </button>
                )}
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {holidays.map((h) => (
                  <div
                    key={h.id}
                    className="p-4 bg-white rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-blue-200 transition"
                  >
                    <div className="flex items-start justify-between">
                      <div>
                        <span className="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-blue-50 text-blue-700">
                          {h.type}
                        </span>
                        <h4 className="text-sm font-bold text-slate-900 mt-2">{h.title}</h4>
                        {h.description && (
                          <p className="text-xs text-slate-500 mt-1">{h.description}</p>
                        )}
                      </div>
                      {isAdminOrSuperadmin && (
                        <button
                          onClick={() => handleDeleteHoliday(h.id)}
                          className="text-slate-400 hover:text-red-600 p-1 cursor-pointer"
                          title="Delete Holiday"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      )}
                    </div>

                    <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                      <div className="flex items-center gap-1.5 font-medium">
                        <Calendar className="w-3.5 h-3.5 text-blue-600" />
                        {h.date}
                      </div>
                      <span className="text-slate-400">{h.day_name}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* MODAL 1: APPLY LEAVE */}
        {applyModalOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
            <div className="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden">
              <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <Calendar className="w-4 h-4" />
                  </div>
                  <h3 className="font-bold text-sm text-slate-900">Submit Leave Application</h3>
                </div>
                <button
                  onClick={() => setApplyModalOpen(false)}
                  className="text-slate-400 hover:text-slate-600 cursor-pointer"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <form onSubmit={handleApplyLeave} className="p-5 space-y-4">
                <div>
                  <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                    Leave Category *
                  </label>
                  <select
                    required
                    value={applyForm.leave_type_id}
                    onChange={(e) => setApplyForm((prev) => ({ ...prev, leave_type_id: e.target.value }))}
                    className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600"
                  >
                    <option value="">Select Category</option>
                    {overview?.balances?.map((b) => (
                      <option key={b.leave_type_id} value={b.leave_type_id}>
                        {b.leave_type?.name} ({b.remaining_days} days remaining)
                      </option>
                    ))}
                  </select>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      Start Date *
                    </label>
                    <input
                      type="date"
                      required
                      value={applyForm.start_date}
                      onChange={(e) => setApplyForm((prev) => ({ ...prev, start_date: e.target.value }))}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      End Date *
                    </label>
                    <input
                      type="date"
                      required
                      disabled={applyForm.is_half_day}
                      value={applyForm.is_half_day ? applyForm.start_date : applyForm.end_date}
                      onChange={(e) => setApplyForm((prev) => ({ ...prev, end_date: e.target.value }))}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 disabled:opacity-50"
                    />
                  </div>
                </div>

                <div className="flex items-center gap-3 pt-1">
                  <label className="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700">
                    <input
                      type="checkbox"
                      checked={applyForm.is_half_day}
                      onChange={(e) => setApplyForm((prev) => ({ ...prev, is_half_day: e.target.checked }))}
                      className="rounded text-blue-600"
                    />
                    Half-Day Leave (0.5 day)
                  </label>

                  {applyForm.is_half_day && (
                    <select
                      value={applyForm.half_day_type}
                      onChange={(e) => setApplyForm((prev) => ({ ...prev, half_day_type: e.target.value }))}
                      className="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2 py-1"
                    >
                      <option value="first_half">First Half</option>
                      <option value="second_half">Second Half</option>
                    </select>
                  )}
                </div>

                <div>
                  <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                    Reason / Purpose *
                  </label>
                  <textarea
                    required
                    rows={3}
                    placeholder="Briefly explain the reason for taking leave..."
                    value={applyForm.reason}
                    onChange={(e) => setApplyForm((prev) => ({ ...prev, reason: e.target.value }))}
                    className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl p-3 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />
                </div>

                <div className="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setApplyModalOpen(false)}
                    className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={submittingApply}
                    className="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                  >
                    {submittingApply ? 'Submitting...' : 'Submit Application'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* MODAL 2: ADMIN APPROVE / REJECT DECISION */}
        {decisionModal.isOpen && decisionModal.app && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
            <div className="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden">
              <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 className="font-bold text-sm text-slate-900">
                  {decisionModal.targetStatus === 'approved' ? 'Approve' : 'Reject'} Leave Request
                </h3>
                <button
                  onClick={() => setDecisionModal({ isOpen: false, app: null, targetStatus: null, remarks: '' })}
                  className="text-slate-400 hover:text-slate-600"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <div className="p-5 space-y-4">
                <div className="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs space-y-1">
                  <div><strong>Employee:</strong> {decisionModal.app.employee?.first_name} {decisionModal.app.employee?.last_name || ''}</div>
                  <div><strong>Type:</strong> {decisionModal.app.leave_type?.name} ({decisionModal.app.total_days} days)</div>
                  <div><strong>Dates:</strong> {decisionModal.app.start_date} to {decisionModal.app.end_date}</div>
                  <div><strong>Reason:</strong> {decisionModal.app.reason}</div>
                </div>

                <div>
                  <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                    Decision Remarks / Feedback
                  </label>
                  <textarea
                    rows={3}
                    placeholder="Provide any context or notes for the employee..."
                    value={decisionModal.remarks}
                    onChange={(e) => setDecisionModal((prev) => ({ ...prev, remarks: e.target.value }))}
                    className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl p-3 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />
                </div>

                <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                  <button
                    onClick={() => setDecisionModal({ isOpen: false, app: null, targetStatus: null, remarks: '' })}
                    className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                  >
                    Cancel
                  </button>
                  <button
                    onClick={handleDecisionSubmit}
                    disabled={submittingDecision}
                    className={`px-4 py-2 text-xs font-semibold text-white rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50 ${
                      decisionModal.targetStatus === 'approved'
                        ? 'bg-emerald-600 hover:bg-emerald-700'
                        : 'bg-red-600 hover:bg-red-700'
                    }`}
                  >
                    {submittingDecision ? 'Processing...' : `Confirm ${decisionModal.targetStatus === 'approved' ? 'Approval' : 'Rejection'}`}
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* MODAL 3A: SUPERADMIN SET GLOBAL LEAVE BALANCE (ACROSS ALL EMPLOYEES) */}
        {globalBalanceModal.isOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
            <div className="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden">
              <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <Sparkles className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="font-bold text-sm text-slate-900">Update Global Leave Quota</h3>
                    <p className="text-[11px] text-slate-500">Applies across all current and future employees</p>
                  </div>
                </div>
                <button
                  onClick={() => setGlobalBalanceModal((prev) => ({ ...prev, isOpen: false }))}
                  className="text-slate-400 hover:text-slate-600 cursor-pointer"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <form onSubmit={handleSaveGlobalBalance} className="p-5 space-y-4">
                <div className="p-3 bg-blue-50/70 border border-blue-100 rounded-xl text-xs text-blue-800">
                  <strong>Notice:</strong> Changing this value will set this quota for <strong>all {balanceEmployees.length} active employee records</strong> for year {globalBalanceModal.year} and adjust their remaining balances accordingly.
                </div>

                <div>
                  <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                    Leave Category *
                  </label>
                  <select
                    required
                    value={globalBalanceModal.leaveTypeId}
                    onChange={(e) => {
                      const selectedId = e.target.value;
                      const selectedType = leaveTypes.find((t) => String(t.id) === String(selectedId));
                      setGlobalBalanceModal((prev) => ({
                        ...prev,
                        leaveTypeId: selectedId,
                        allocatedDays: selectedType?.default_days_per_year || prev.allocatedDays,
                      }));
                    }}
                    className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none"
                  >
                    <option value="">Select Category</option>
                    {Array.isArray(leaveTypes) && leaveTypes.map((t) => (
                      <option key={t.id} value={t.id}>
                        {t.name} ({t.code}) – Currently {t.default_days_per_year}d
                      </option>
                    ))}
                  </select>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      Calendar Year *
                    </label>
                    <input
                      type="number"
                      required
                      value={globalBalanceModal.year}
                      onChange={(e) => setGlobalBalanceModal((prev) => ({ ...prev, year: Number(e.target.value) }))}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      New Annual Quota (Days) *
                    </label>
                    <input
                      type="number"
                      step="0.5"
                      min="0"
                      required
                      value={globalBalanceModal.allocatedDays}
                      onChange={(e) => setGlobalBalanceModal((prev) => ({ ...prev, allocatedDays: e.target.value }))}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900"
                    />
                  </div>
                </div>

                <div className="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setGlobalBalanceModal((prev) => ({ ...prev, isOpen: false }))}
                    className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={submittingGlobalBalance}
                    className="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50"
                  >
                    {submittingGlobalBalance ? 'Applying Globally...' : 'Apply Globally to All Staff'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* MODAL 3: SUPERADMIN SET LEAVE BALANCE */}
        {balanceModal.isOpen && balanceModal.employee && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
            <div className="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden">
              <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 className="font-bold text-sm text-slate-900">
                  Allocate Balance: {balanceModal.employee.first_name} {balanceModal.employee.last_name || ''}
                </h3>
                <button
                  onClick={() => setBalanceModal((prev) => ({ ...prev, isOpen: false }))}
                  className="text-slate-400 hover:text-slate-600"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <form onSubmit={handleSaveBalance} className="p-5 space-y-4">
                <div>
                  <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                    Leave Category
                  </label>
                  <select
                    value={balanceModal.leaveTypeId}
                    onChange={(e) => setBalanceModal((prev) => ({ ...prev, leaveTypeId: e.target.value }))}
                    className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none"
                  >
                    {Array.isArray(leaveTypes) && leaveTypes.map((t) => (
                      <option key={t.id} value={t.id}>
                        {t.name} ({t.code})
                      </option>
                    ))}
                  </select>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      Calendar Year
                    </label>
                    <input
                      type="number"
                      value={balanceModal.year}
                      onChange={(e) => setBalanceModal((prev) => ({ ...prev, year: Number(e.target.value) }))}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      Total Allocated Days
                    </label>
                    <input
                      type="number"
                      step="0.5"
                      min="0"
                      value={balanceModal.allocatedDays}
                      onChange={(e) => setBalanceModal((prev) => ({ ...prev, allocatedDays: e.target.value }))}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-800"
                    />
                  </div>
                </div>

                <div className="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setBalanceModal((prev) => ({ ...prev, isOpen: false }))}
                    className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs cursor-pointer"
                  >
                    Save Allocation
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* MODAL 4: ADD HOLIDAY */}
        {holidayModal.isOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
            <div className="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden">
              <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 className="font-bold text-sm text-slate-900">Add Official Holiday</h3>
                <button
                  onClick={() => setHolidayModal((prev) => ({ ...prev, isOpen: false }))}
                  className="text-slate-400 hover:text-slate-600"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <form onSubmit={handleSaveHoliday} className="p-5 space-y-4">
                <div>
                  <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                    Holiday Title *
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="e.g. Diwali, Independence Day, Christmas"
                    value={holidayModal.title}
                    onChange={(e) => setHolidayModal((prev) => ({ ...prev, title: e.target.value }))}
                    className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      Date *
                    </label>
                    <input
                      type="date"
                      required
                      value={holidayModal.date}
                      onChange={(e) => setHolidayModal((prev) => ({ ...prev, date: e.target.value }))}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      Holiday Type
                    </label>
                    <select
                      value={holidayModal.type}
                      onChange={(e) => setHolidayModal((prev) => ({ ...prev, type: e.target.value as any }))}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2"
                    >
                      <option value="gazetted">Gazetted (Mandatory)</option>
                      <option value="restricted">Restricted</option>
                      <option value="company">Company Specific</option>
                      <option value="optional">Optional</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                    Description / Notes
                  </label>
                  <textarea
                    rows={2}
                    placeholder="Optional notes or details..."
                    value={holidayModal.description}
                    onChange={(e) => setHolidayModal((prev) => ({ ...prev, description: e.target.value }))}
                    className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl p-3 focus:bg-white focus:outline-none"
                  />
                </div>

                <div className="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setHolidayModal((prev) => ({ ...prev, isOpen: false }))}
                    className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs cursor-pointer"
                  >
                    Save Holiday
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </AppLayout>
    </ProtectedRoute>
  );
}
