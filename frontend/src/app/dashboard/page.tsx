'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import ProtectedRoute from '@/components/ProtectedRoute';
import AppLayout from '@/components/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { apiRequest } from '@/lib/api';
import { DashboardResponse, AdminDashboardStats, EmployeeDashboardStats } from '@/types/dashboard';
import {
  Users,
  CalendarCheck,
  CreditCard,
  FileText,
  LifeBuoy,
  Clock,
  CheckCircle2,
  AlertTriangle,
  Shield,
  Briefcase,
  TrendingUp,
  UserCheck,
  RefreshCw,
  ArrowRight,
  ExternalLink,
  ChevronRight,
  Calendar,
  Building2,
  HelpCircle,
  BarChart3,
  Contact,
  DollarSign,
  AlertCircle,
  FileSpreadsheet,
  ArrowUpRight,
  Sparkles,
  MapPin,
  Check,
  Megaphone,
  Pin,
  Plus,
  X,
} from 'lucide-react';

export default function DashboardPage() {
  const { user, token } = useAuth();
  const isAdminOrSuper = user?.role === 'admin' || user?.role === 'superadmin';

  const [dashboardData, setDashboardData] = useState<DashboardResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [punching, setPunching] = useState(false);
  const [punchMsg, setPunchMsg] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  // Notice Board modal & state
  const [showNoticeModal, setShowNoticeModal] = useState(false);
  const [noticeForm, setNoticeForm] = useState({
    title: '',
    content: '',
    priority: 'normal' as 'normal' | 'important' | 'urgent',
    target_audience: 'all' as 'all' | 'department',
    is_pinned: false,
  });
  const [publishingNotice, setPublishingNotice] = useState(false);

  const handlePublishNotice = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return;
    try {
      setPublishingNotice(true);
      await apiRequest('/notices', {
        method: 'POST',
        body: JSON.stringify(noticeForm),
      }, token || undefined);
      setShowNoticeModal(false);
      setNoticeForm({
        title: '',
        content: '',
        priority: 'normal',
        target_audience: 'all',
        is_pinned: false,
      });
      fetchDashboardStats();
    } catch (err: any) {
      alert(err.message || 'Failed to publish notice');
    } finally {
      setPublishingNotice(false);
    }
  };

  const fetchDashboardStats = async () => {
    if (!token) return;
    try {
      setLoading(true);
      setError(null);
      const res = await apiRequest<DashboardResponse>('/dashboard/stats', {}, token || undefined);
      setDashboardData(res);
    } catch (err: any) {
      console.error('Failed to load dashboard metrics', err);
      setError(err?.message || 'Failed to load dashboard metrics');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardStats();
  }, [token]);

  // Quick Punch Handler for Employee
  const handleQuickPunch = async (action: 'punch-in' | 'punch-out') => {
    if (!token) return;
    try {
      setPunching(true);
      setPunchMsg(null);

      // Attempt browser GPS if permitted
      let latitude = 26.1696;
      let longitude = 91.7607;
      if (navigator.geolocation) {
        try {
          const pos: any = await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 3000 });
          });
          latitude = pos.coords.latitude;
          longitude = pos.coords.longitude;
        } catch {
          // fallback
        }
      }

      const res = await apiRequest<{ success: boolean; message: string }>(`/attendance/${action}`, {
        method: 'POST',
        body: JSON.stringify({ latitude, longitude }),
      }, token || undefined);

      setPunchMsg({ type: 'success', text: res.message || `${action === 'punch-in' ? 'Punched in' : 'Punched out'} recorded.` });
      fetchDashboardStats();
    } catch (err: any) {
      setPunchMsg({ type: 'error', text: err?.message || `Failed to ${action}.` });
    } finally {
      setPunching(false);
    }
  };

  const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      maximumFractionDigits: 0,
    }).format(val);
  };

  // Render Employee View
  const renderEmployeeDashboard = (stats: EmployeeDashboardStats) => {
    const today = stats.today_attendance;
    const hasPunchedIn = Boolean(today?.punch_in_time);
    const hasPunchedOut = Boolean(today?.punch_out_time);

    return (
      <div className="space-y-6">
        {/* Punch Notification Alert */}
        {punchMsg && (
          <div
            className={`p-4 rounded-xl border flex items-center justify-between text-xs font-semibold shadow-xs transition ${
              punchMsg.type === 'success'
                ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                : 'bg-red-50 text-red-800 border-red-200'
            }`}
          >
            <span>{punchMsg.text}</span>
            <button onClick={() => setPunchMsg(null)} className="text-slate-400 hover:text-slate-600">
              &times;
            </button>
          </div>
        )}

        {/* Welcome & Quick Digital Punch Card */}
        <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 rounded-2xl p-6 text-white shadow-xl flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
          <div className="space-y-2">
            <div className="flex items-center space-x-2">
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                Staff Self-Service Workspace
              </span>
              <span className="text-xs text-slate-400">
                ID: {stats.employee?.employee_code || user?.email}
              </span>
            </div>
            <h2 className="text-xl font-bold tracking-tight">
              Hello, {stats.employee?.first_name || user?.name}!
            </h2>
            <p className="text-xs text-slate-300 max-w-xl leading-relaxed">
              Assigned Shift: <span className="font-semibold text-white">{stats.employee?.shift?.name || 'Standard 09:30 - 18:30'}</span> &bull; Campus: <span className="font-semibold text-white">{stats.employee?.location?.name || 'Plystory HO'}</span>
            </p>
          </div>

          {/* Real-time Punch Controller */}
          <div className="bg-white/10 border border-white/20 rounded-2xl p-4.5 backdrop-blur-md flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto">
            <div className="text-center sm:text-left">
              <span className="text-[10px] font-bold uppercase tracking-wider text-blue-200 block">
                Today&apos;s Punch Status
              </span>
              <div className="text-sm font-bold text-white mt-0.5">
                {hasPunchedIn ? (
                  <span className="text-emerald-300">
                    In: {today?.punch_in_time} {today?.punch_out_time ? `&bull; Out: ${today.punch_out_time}` : '(In Shift)'}
                  </span>
                ) : (
                  <span className="text-amber-300">Not Checked In Yet</span>
                )}
              </div>
            </div>

            <div className="flex items-center gap-2 w-full sm:w-auto">
              {!hasPunchedIn ? (
                <button
                  onClick={() => handleQuickPunch('punch-in')}
                  disabled={punching}
                  className="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md transition disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                >
                  <Clock className="w-4 h-4" />
                  <span>{punching ? 'Recording...' : 'Punch In Now'}</span>
                </button>
              ) : (
                <button
                  onClick={() => handleQuickPunch('punch-out')}
                  disabled={punching}
                  className="w-full sm:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                >
                  <Clock className="w-4 h-4" />
                  <span>{punching ? 'Updating...' : hasPunchedOut ? 'Update Punch Out' : 'Punch Out Now'}</span>
                </button>
              )}
            </div>
          </div>
        </div>

        {/* 3 Personal Metric Cards (Leave balance KPI removed per request) */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          {/* 1. Month-to-Date Present */}
          <Link
            href="/attendance"
            className="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-2xs hover:shadow-sm transition flex flex-col justify-between group"
          >
            <div className="flex items-center justify-between text-slate-500 mb-2">
              <span className="text-xs font-bold uppercase tracking-wider">Days Present</span>
              <CalendarCheck className="w-4 h-4 text-emerald-600" />
            </div>
            <div className="text-2xl font-bold text-slate-900">
              {stats.month_stats.present_days}
            </div>
            <div className="text-[11px] text-slate-500 mt-1">
              {stats.month_stats.total_working_hours} Total Working Hours ({stats.month_stats.month_name})
            </div>
          </Link>

          {/* 2. Late Marks */}
          <Link
            href="/attendance"
            className="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-2xs hover:shadow-sm transition flex flex-col justify-between group"
          >
            <div className="flex items-center justify-between text-slate-500 mb-2">
              <span className="text-xs font-bold uppercase tracking-wider">Late Arrivals</span>
              <Clock className="w-4 h-4 text-amber-500" />
            </div>
            <div className="text-2xl font-bold text-slate-900">
              {stats.month_stats.late_days}
            </div>
            <div className="text-[11px] text-amber-600 mt-1 font-medium">
              {stats.month_stats.late_days > 5 ? 'Late penalty active' : 'Within 5-day grace window'}
            </div>
          </Link>

          {/* 3. IT Support Desk */}
          <Link
            href="/tickets"
            className="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-2xs hover:shadow-sm transition flex flex-col justify-between group"
          >
            <div className="flex items-center justify-between text-slate-500 mb-2">
              <span className="text-xs font-bold uppercase tracking-wider">My IT Tickets</span>
              <LifeBuoy className="w-4 h-4 text-purple-600" />
            </div>
            <div className="text-2xl font-bold text-slate-900">
              {stats.my_tickets_stats.open + stats.my_tickets_stats.in_progress} Active
            </div>
            <div className="text-[11px] text-purple-600 mt-1 font-medium">
              {stats.my_tickets_stats.resolved} resolved tickets
            </div>
          </Link>
        </div>

        {/* Two-Column Middle Section: Official Notice Board & IT Support Tickets */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          {/* Left 7 Cols: Official Notice Board & Circulars */}
          <div className="lg:col-span-7 bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div className="flex items-center space-x-2">
                  <div className="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <Megaphone className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                      Official Notice Board
                    </h3>
                    <p className="text-[11px] text-slate-400">Important company announcements & circulars</p>
                  </div>
                </div>
                {isAdminOrSuper && (
                  <button
                    onClick={() => setShowNoticeModal(true)}
                    className="px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-xs"
                  >
                    <Plus className="w-3.5 h-3.5" />
                    Publish Notice
                  </button>
                )}
              </div>

              {(!stats.notices || stats.notices.length === 0) ? (
                <div className="py-12 text-center text-slate-400 text-xs space-y-1">
                  <Megaphone className="w-8 h-8 text-slate-300 mx-auto mb-2 opacity-60" />
                  <p className="font-semibold text-slate-600">No active notices</p>
                  <p className="text-[11px] text-slate-400">Official company circulars and bulletins will appear here.</p>
                </div>
              ) : (
                <div className="space-y-3">
                  {stats.notices.map((n) => (
                    <div
                      key={n.id}
                      className={`p-3.5 rounded-xl border transition ${
                        n.priority === 'urgent'
                          ? 'bg-red-50/50 border-red-200'
                          : n.priority === 'important'
                          ? 'bg-amber-50/50 border-amber-200'
                          : 'bg-slate-50/75 border-slate-200/80'
                      }`}
                    >
                      <div className="flex items-start justify-between gap-2">
                        <div className="flex items-center gap-2">
                          {n.is_pinned && (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">
                              <Pin className="w-2.5 h-2.5" /> Pinned
                            </span>
                          )}
                          <span
                            className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                              n.priority === 'urgent'
                                ? 'bg-red-100 text-red-700'
                                : n.priority === 'important'
                                ? 'bg-amber-100 text-amber-800'
                                : 'bg-slate-200 text-slate-700'
                            }`}
                          >
                            {n.priority}
                          </span>
                          <span className="text-[11px] text-slate-400">
                            {n.publish_date || new Date(n.created_at).toLocaleDateString()}
                          </span>
                        </div>
                        <span className="text-[10px] text-slate-400 font-medium">
                          By {n.creator?.name || 'HR Management'}
                        </span>
                      </div>
                      <h4 className="font-bold text-slate-900 text-xs sm:text-sm mt-2">
                        {n.title}
                      </h4>
                      <p className="text-xs text-slate-600 mt-1 leading-relaxed whitespace-pre-line">
                        {n.content}
                      </p>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between text-xs text-slate-500">
              <span>Have queries regarding corporate guidelines?</span>
              <Link href="/documents" className="font-semibold text-blue-600 hover:underline">
                View Policies & Compliance
              </Link>
            </div>
          </div>

          {/* Right 5 Cols: IT Support Tickets Status & Payslip Access */}
          <div className="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div className="flex items-center space-x-2">
                  <LifeBuoy className="w-4 h-4 text-purple-600" />
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    My IT Support Tickets
                  </h3>
                </div>
                <Link href="/tickets" className="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                  Raise Ticket <ArrowRight className="w-3 h-3" />
                </Link>
              </div>

              {stats.my_tickets.length === 0 ? (
                <div className="py-8 text-center text-slate-400 text-xs">
                  <LifeBuoy className="w-8 h-8 text-slate-200 mx-auto mb-2" />
                  No open IT tickets. Your hardware & software status is clear!
                </div>
              ) : (
                <div className="divide-y divide-slate-100">
                  {stats.my_tickets.map((t) => (
                    <Link
                      key={t.id}
                      href="/tickets"
                      className="py-3 flex items-center justify-between text-xs hover:bg-slate-50 rounded-lg px-2 -mx-2 transition"
                    >
                      <div className="min-w-0 mr-3">
                        <div className="flex items-center space-x-2 mb-0.5">
                          <span className="font-bold text-blue-600">{t.ticket_number}</span>
                          <span className="text-[10px] text-slate-400 uppercase">{t.category}</span>
                        </div>
                        <p className="font-medium text-slate-800 truncate">{t.subject}</p>
                      </div>

                      <div className="shrink-0 text-right">
                        <span
                          className={`text-[10px] font-bold px-2 py-0.5 rounded border uppercase ${
                            t.status === 'open'
                              ? 'bg-amber-50 text-amber-700 border-amber-200'
                              : t.status === 'in_progress'
                              ? 'bg-blue-50 text-blue-700 border-blue-200'
                              : 'bg-emerald-50 text-emerald-700 border-emerald-200'
                          }`}
                        >
                          {t.status.replace('_', ' ')}
                        </span>
                      </div>
                    </Link>
                  ))}
                </div>
              )}
            </div>

            {/* Quick Link to Payslips */}
            <div className="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between text-xs">
              <span className="text-slate-500">Need your monthly salary payslip?</span>
              <Link
                href="/payroll"
                className="font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1"
              >
                <CreditCard className="w-3.5 h-3.5" />
                Download Payslip
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  };

  // Render Admin View
  const renderAdminDashboard = (stats: AdminDashboardStats) => {
    return (
      <div className="space-y-6">
        {/* Executive Welcome Banner */}
        <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 rounded-2xl p-6 text-white shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div className="space-y-1">
            <div className="flex items-center space-x-2">
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-300 border border-blue-400/30">
                {user?.role === 'superadmin' ? 'Super Administrator Suite' : 'Operations Management'}
              </span>
              <span className="text-xs text-slate-400">Live Enterprise Production</span>
            </div>
            <h2 className="text-lg sm:text-xl font-bold tracking-tight">
              Welcome back, {user?.name || 'Administrator'}
            </h2>
            <p className="text-xs text-slate-300 max-w-2xl leading-relaxed">
              Here is your organization&apos;s operational pulse for today. Review live biometric check-ins, action pending employee leaves, and monitor departmental performance.
            </p>
          </div>

          <div className="flex items-center gap-3">
            <div className="text-right hidden sm:block">
              <div className="text-2xl font-bold">{stats.attendance_today.attendance_rate || 0}%</div>
              <div className="text-xs text-blue-200">Today&apos;s Attendance</div>
            </div>
            <div className="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center border border-white/20">
              <TrendingUp className="w-6 h-6 text-emerald-300" />
            </div>
          </div>
        </div>

        {/* KPI Stat Cards (4 Core Pillars) */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {/* 1. Total Workforce */}
          <Link
            href="/employees"
            className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-md transition flex flex-col justify-between group cursor-pointer"
          >
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs font-bold uppercase tracking-wider text-slate-500">
                Total Workforce
              </span>
              <div className="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100 group-hover:scale-105 transition-transform">
                <Users className="w-4 h-4" />
              </div>
            </div>
            <div>
              <div className="text-2xl font-extrabold text-slate-900">
                {stats.workforce.total_employees || 0}
              </div>
              <div className="mt-1 flex items-center justify-between text-xs">
                <span className="text-emerald-600 font-semibold flex items-center gap-1">
                  <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                  {stats.workforce.active_employees || 0} Active Staff
                </span>
                <span className="text-slate-400 group-hover:text-blue-600 transition flex items-center gap-0.5">
                  View <ChevronRight className="w-3 h-3" />
                </span>
              </div>
            </div>
          </Link>

          {/* 2. Today's Attendance */}
          <Link
            href="/attendance"
            className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-md transition flex flex-col justify-between group cursor-pointer"
          >
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs font-bold uppercase tracking-wider text-slate-500">
                Attendance Today
              </span>
              <div className="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 group-hover:scale-105 transition-transform">
                <CalendarCheck className="w-4 h-4" />
              </div>
            </div>
            <div>
              <div className="text-2xl font-extrabold text-slate-900 flex items-baseline gap-2">
                <span>{stats.attendance_today.present || 0}</span>
                <span className="text-xs font-medium text-slate-400">
                  / {stats.workforce.active_employees || 0} roster
                </span>
              </div>
              <div className="mt-1 flex items-center justify-between text-xs">
                <span className="text-amber-600 font-medium">
                  {stats.attendance_today.late || 0} Late &bull; {stats.attendance_today.evaluating || 0} in shift
                </span>
                <span className="text-slate-400 group-hover:text-emerald-600 transition flex items-center gap-0.5">
                  Roster <ChevronRight className="w-3 h-3" />
                </span>
              </div>
            </div>
          </Link>

          {/* 3. Leave Approvals */}
          <Link
            href="/leaves"
            className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-md transition flex flex-col justify-between group cursor-pointer"
          >
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs font-bold uppercase tracking-wider text-slate-500">
                Pending Leaves
              </span>
              <div className="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 group-hover:scale-105 transition-transform">
                <Calendar className="w-4 h-4" />
              </div>
            </div>
            <div>
              <div className="text-2xl font-extrabold text-slate-900">
                {stats.leaves.pending_leaves || 0}
              </div>
              <div className="mt-1 flex items-center justify-between text-xs">
                <span className="text-slate-500 font-medium">
                  {stats.leaves.approved_this_month || 0} approved this month
                </span>
                <span className="text-slate-400 group-hover:text-amber-600 transition flex items-center gap-0.5">
                  Review <ChevronRight className="w-3 h-3" />
                </span>
              </div>
            </div>
          </Link>

          {/* 4. IT Support Desk */}
          <Link
            href="/tickets"
            className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-md transition flex flex-col justify-between group cursor-pointer"
          >
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs font-bold uppercase tracking-wider text-slate-500">
                IT Support Desk
              </span>
              <div className="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100 group-hover:scale-105 transition-transform">
                <LifeBuoy className="w-4 h-4" />
              </div>
            </div>
            <div>
              <div className="text-2xl font-extrabold text-slate-900 flex items-baseline gap-2">
                <span>{stats.it_helpdesk.open || 0} Open</span>
                {stats.it_helpdesk.critical ? (
                  <span className="text-xs font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded border border-red-200">
                    {stats.it_helpdesk.critical} Critical
                  </span>
                ) : null}
              </div>
              <div className="mt-1 flex items-center justify-between text-xs">
                <span className="text-slate-500 font-medium">
                  {stats.it_helpdesk.in_progress || 0} in progress
                </span>
                <span className="text-slate-400 group-hover:text-purple-600 transition flex items-center gap-0.5">
                  Tickets <ChevronRight className="w-3 h-3" />
                </span>
              </div>
            </div>
          </Link>
        </div>

        {/* Quick Actions Bar */}
        <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
          <div className="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div className="flex items-center space-x-2">
              <Shield className="w-4 h-4 text-blue-600" />
              <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                Operational Quick Actions
              </h3>
            </div>
            <span className="text-[11px] text-slate-400">One-click enterprise shortcuts</span>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 text-xs">
            <button
              onClick={() => setShowNoticeModal(true)}
              className="p-3 rounded-xl bg-blue-50 hover:bg-blue-100 hover:text-blue-800 transition flex flex-col items-center text-center gap-2 border border-blue-200 group cursor-pointer"
            >
              <div className="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shadow-xs">
                <Megaphone className="w-4 h-4" />
              </div>
              <span className="font-bold text-blue-800">Post Notice</span>
            </button>

            <Link
              href="/attendance"
              className="p-3 rounded-xl bg-slate-50 hover:bg-blue-50 hover:text-blue-700 transition flex flex-col items-center text-center gap-2 border border-slate-100 group"
            >
              <div className="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 group-hover:text-blue-600 shadow-2xs">
                <Clock className="w-4 h-4" />
              </div>
              <span className="font-semibold text-slate-700 group-hover:text-blue-700">Punch Clock</span>
            </Link>

            <Link
              href="/payroll"
              className="p-3 rounded-xl bg-slate-50 hover:bg-purple-50 hover:text-purple-700 transition flex flex-col items-center text-center gap-2 border border-slate-100 group"
            >
              <div className="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 group-hover:text-purple-600 shadow-2xs">
                <CreditCard className="w-4 h-4" />
              </div>
              <span className="font-semibold text-slate-700 group-hover:text-purple-700">Run Payroll</span>
            </Link>

            <Link
              href="/leaves"
              className="p-3 rounded-xl bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 transition flex flex-col items-center text-center gap-2 border border-slate-100 group"
            >
              <div className="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 group-hover:text-emerald-600 shadow-2xs">
                <Calendar className="w-4 h-4" />
              </div>
              <span className="font-semibold text-slate-700 group-hover:text-emerald-700">Leave Approvals</span>
            </Link>

            <Link
              href="/directory"
              className="p-3 rounded-xl bg-slate-50 hover:bg-indigo-50 hover:text-indigo-700 transition flex flex-col items-center text-center gap-2 border border-slate-100 group"
            >
              <div className="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 group-hover:text-indigo-600 shadow-2xs">
                <Contact className="w-4 h-4" />
              </div>
              <span className="font-semibold text-slate-700 group-hover:text-indigo-700">Directory</span>
            </Link>

            <Link
              href="/documents"
              className="p-3 rounded-xl bg-slate-50 hover:bg-rose-50 hover:text-rose-700 transition flex flex-col items-center text-center gap-2 border border-slate-100 group"
            >
              <div className="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 group-hover:text-rose-600 shadow-2xs">
                <FileText className="w-4 h-4" />
              </div>
              <span className="font-semibold text-slate-700 group-hover:text-rose-700">Policies & IT</span>
            </Link>

            <Link
              href="/reports"
              className="p-3 rounded-xl bg-slate-50 hover:bg-amber-50 hover:text-amber-700 transition flex flex-col items-center text-center gap-2 border border-slate-100 group"
            >
              <div className="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 group-hover:text-amber-600 shadow-2xs">
                <FileSpreadsheet className="w-4 h-4" />
              </div>
              <span className="font-semibold text-slate-700 group-hover:text-amber-700">Export Reports</span>
            </Link>
          </div>
        </div>

        {/* Live Attendance Stream & 7-Day Trend */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <div className="lg:col-span-7 bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div className="flex items-center space-x-2">
                  <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Live Attendance Stream (Today)
                  </h3>
                </div>
                <Link href="/attendance" className="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                  Full Roster <ArrowRight className="w-3 h-3" />
                </Link>
              </div>

              {(!stats.recent_attendance || stats.recent_attendance.length === 0) ? (
                <div className="py-12 text-center text-slate-400 text-xs">
                  <Clock className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                  No punch records recorded yet today.
                </div>
              ) : (
                <div className="divide-y divide-slate-100">
                  {stats.recent_attendance.map((rec) => (
                    <div key={rec.id} className="py-3 flex items-center justify-between text-xs">
                      <div className="flex items-center space-x-3 min-w-0">
                        <div className="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 font-bold flex items-center justify-center shrink-0 text-xs">
                          {rec.employee?.name?.[0] || 'E'}
                        </div>
                        <div className="min-w-0">
                          <p className="font-bold text-slate-900 truncate">{rec.employee?.name || 'Staff'}</p>
                          <p className="text-[11px] text-slate-400 truncate">
                            {rec.employee?.employee_code} &bull; {rec.employee?.department?.name || 'General'}
                          </p>
                        </div>
                      </div>

                      <div className="text-right shrink-0">
                        <div className="font-semibold text-slate-800">
                          {rec.punch_in_time || '—'}
                        </div>
                        <div className="flex items-center justify-end gap-1.5 mt-0.5">
                          {rec.is_late ? (
                            <span className="text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                              LATE
                            </span>
                          ) : null}
                          <span className="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">
                            {rec.status}
                          </span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
              <span>Shift grace period: 15 minutes</span>
              <span>Fixed Geofence Radius enforced</span>
            </div>
          </div>

          <div className="lg:col-span-5 space-y-6">
            <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                <div className="flex items-center space-x-2">
                  <Megaphone className="w-4 h-4 text-blue-600" />
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Company Notice Board
                  </h3>
                </div>
                <button
                  onClick={() => setShowNoticeModal(true)}
                  className="text-xs text-blue-600 hover:text-blue-700 font-bold flex items-center gap-1 cursor-pointer"
                >
                  <Plus className="w-3.5 h-3.5" /> Post Notice
                </button>
              </div>

              {(!stats.notices || stats.notices.length === 0) ? (
                <p className="text-xs text-slate-400 py-3 text-center">No active notice board circulars.</p>
              ) : (
                <div className="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                  {stats.notices.map((n) => (
                    <div key={n.id} className="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-1">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-1.5">
                          {n.is_pinned && (
                            <span className="text-[10px] font-bold text-blue-700 bg-blue-100 px-1.5 py-0.2 rounded flex items-center gap-0.5">
                              <Pin className="w-2.5 h-2.5" /> Pin
                            </span>
                          )}
                          <span className={`text-[10px] font-bold uppercase px-1.5 py-0.2 rounded ${
                            n.priority === 'urgent' ? 'bg-red-100 text-red-700' : n.priority === 'important' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700'
                          }`}>
                            {n.priority}
                          </span>
                        </div>
                        <span className="text-[10px] text-slate-400">{n.publish_date || new Date(n.created_at).toLocaleDateString()}</span>
                      </div>
                      <h4 className="font-bold text-slate-900 truncate">{n.title}</h4>
                      <p className="text-[11px] text-slate-600 line-clamp-2">{n.content}</p>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                  7-Day Attendance Trend
                </h3>
                <span className="text-[11px] text-slate-400">Daily present punches</span>
              </div>

              <div className="flex items-end justify-between h-36 pt-4 gap-2">
                {stats.weekly_trend.map((day, idx) => {
                  const maxVal = Math.max(...(stats.weekly_trend.map((d) => d.present) || [1]), 1);
                  const heightPct = Math.max(15, Math.round((day.present / maxVal) * 100));

                  return (
                    <div key={idx} className="flex-1 flex flex-col items-center justify-end h-full group">
                      <span className="text-[10px] font-bold text-blue-600 mb-1 opacity-0 group-hover:opacity-100 transition">
                        {day.present}
                      </span>
                      <div
                        style={{ height: `${heightPct}%` }}
                        className="w-full bg-blue-100 group-hover:bg-blue-600 rounded-t-lg transition-all"
                      />
                      <span className="text-[10px] font-medium text-slate-500 mt-2">{day.day}</span>
                    </div>
                  );
                })}
              </div>
            </div>

            <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                  Department Breakdown
                </h3>
                <Link href="/settings?tab=department" className="text-xs text-blue-600 hover:text-blue-700 font-semibold">
                  Manage
                </Link>
              </div>

              <div className="space-y-2.5">
                {(!stats.department_distribution || stats.department_distribution.length === 0) ? (
                  <p className="text-xs text-slate-400 py-3 text-center">No departments created yet.</p>
                ) : (
                  stats.department_distribution.map((dept) => {
                    const total = stats.workforce.total_employees || 1;
                    const pct = Math.round((dept.employees_count / total) * 100);

                    return (
                      <div key={dept.id} className="space-y-1">
                        <div className="flex items-center justify-between text-xs">
                          <span className="font-semibold text-slate-700">{dept.name}</span>
                          <span className="text-slate-500">{dept.employees_count} staff ({pct}%)</span>
                        </div>
                        <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                          <div style={{ width: `${pct}%` }} className="bg-indigo-600 h-full rounded-full" />
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  };

  return (
    <ProtectedRoute>
      <AppLayout
        title={isAdminOrSuper ? "Executive Command Center" : "My Workplace Dashboard"}
        subtitle={
          isAdminOrSuper
            ? "Real-time biometric attendance, workforce roster, pending approvals, and operational health"
            : "Daily attendance check-in, official notice board, IT tickets, and payslip access"
        }
        action={
          <div className="flex items-center gap-2">
            <button
              onClick={fetchDashboardStats}
              disabled={loading}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-xl shadow-2xs transition cursor-pointer"
            >
              <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin text-blue-600' : ''}`} />
              <span className="hidden sm:inline">Refresh Data</span>
            </button>
            {isAdminOrSuper ? (
              <>
                <button
                  onClick={() => setShowNoticeModal(true)}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-200 hover:bg-blue-100 text-blue-700 text-xs font-semibold rounded-xl shadow-2xs transition cursor-pointer"
                >
                  <Megaphone className="w-3.5 h-3.5" />
                  <span>+ Post Notice</span>
                </button>
                <Link
                  href="/employees/onboard"
                  className="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-xs transition"
                >
                  <span>+ Onboard Staff</span>
                </Link>
              </>
            ) : (
              <Link
                href="/leaves"
                className="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-xs transition"
              >
                <span>Apply Leave</span>
              </Link>
            )}
          </div>
        }
      >
        <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
          {loading && !dashboardData ? (
            <div className="bg-white rounded-2xl border border-slate-200 p-16 text-center text-slate-400 text-xs">
              <div className="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
              Loading your dashboard workspace...
            </div>
          ) : error && !dashboardData ? (
            <div className="bg-white rounded-2xl border border-red-200 p-12 text-center text-xs space-y-3">
              <div className="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto">
                <AlertCircle className="w-6 h-6" />
              </div>
              <h3 className="text-sm font-bold text-slate-800">Unable to load dashboard data</h3>
              <p className="text-slate-500 max-w-sm mx-auto">{error}</p>
              <button
                onClick={fetchDashboardStats}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold cursor-pointer shadow-xs"
              >
                Retry
              </button>
            </div>
          ) : dashboardData?.role === 'employee' ? (
            renderEmployeeDashboard(dashboardData as EmployeeDashboardStats)
          ) : dashboardData ? (
            renderAdminDashboard(dashboardData as AdminDashboardStats)
          ) : null}

          {/* Modal to Publish Official Notice (Admin/Superadmin) */}
          {showNoticeModal && (
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
              <div className="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <div className="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
                  <div className="flex items-center gap-2">
                    <div className="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
                      <Megaphone className="w-4 h-4" />
                    </div>
                    <div>
                      <h3 className="text-sm font-bold text-slate-900">Publish Notice Board Circular</h3>
                      <p className="text-[11px] text-slate-500">Circulate update to employees and departments</p>
                    </div>
                  </div>
                  <button
                    onClick={() => setShowNoticeModal(false)}
                    className="text-slate-400 hover:text-slate-600 p-1 rounded-lg"
                  >
                    <X className="w-4 h-4" />
                  </button>
                </div>

                <form onSubmit={handlePublishNotice} className="p-5 space-y-4">
                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      Notice Title *
                    </label>
                    <input
                      type="text"
                      required
                      placeholder="e.g. Annual Company Offsite / Office Closed for Diwali"
                      value={noticeForm.title}
                      onChange={(e) => setNoticeForm({ ...noticeForm, title: e.target.value })}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none"
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                        Priority Level
                      </label>
                      <select
                        value={noticeForm.priority}
                        onChange={(e) => setNoticeForm({ ...noticeForm, priority: e.target.value as any })}
                        className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none"
                      >
                        <option value="normal">Normal Priority</option>
                        <option value="important">Important Priority</option>
                        <option value="urgent">Urgent Announcement</option>
                      </select>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                        Target Audience
                      </label>
                      <select
                        value={noticeForm.target_audience}
                        onChange={(e) => setNoticeForm({ ...noticeForm, target_audience: e.target.value as any })}
                        className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none"
                      >
                        <option value="all">All Employees</option>
                        <option value="department">Single Department</option>
                      </select>
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      Notice Description & Details *
                    </label>
                    <textarea
                      required
                      rows={4}
                      placeholder="Type the announcement details, effective dates, or directives..."
                      value={noticeForm.content}
                      onChange={(e) => setNoticeForm({ ...noticeForm, content: e.target.value })}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none resize-none"
                    />
                  </div>

                  <div className="flex items-center gap-2 pt-1">
                    <input
                      type="checkbox"
                      id="pinNotice"
                      checked={noticeForm.is_pinned}
                      onChange={(e) => setNoticeForm({ ...noticeForm, is_pinned: e.target.checked })}
                      className="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    />
                    <label htmlFor="pinNotice" className="text-xs text-slate-700 font-medium cursor-pointer">
                      Pin notice to the top of the employee bulletin
                    </label>
                  </div>

                  <div className="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button
                      type="button"
                      onClick={() => setShowNoticeModal(false)}
                      className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      disabled={publishingNotice}
                      className="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50"
                    >
                      {publishingNotice ? 'Publishing...' : 'Circulate Notice'}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}
        </div>
      </AppLayout>
    </ProtectedRoute>
  );
}
