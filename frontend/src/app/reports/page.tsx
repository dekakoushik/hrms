'use client';

import React, { useState } from 'react';
import AppLayout from '@/components/AppLayout';
import { useAuth } from '@/context/AuthContext';
import Cookies from 'js-cookie';
import {
  BarChart3,
  Download,
  Users,
  CalendarCheck,
  Calendar,
  CreditCard,
  LifeBuoy,
  FileSpreadsheet,
  Filter,
  CheckCircle2,
  Clock,
  Shield,
  FileText,
  AlertCircle,
  HelpCircle,
  ArrowDownToLine,
  Building,
} from 'lucide-react';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api';

export default function ReportsPage() {
  const { user, token } = useAuth();
  const isAdminOrSuper = user?.role === 'admin' || user?.role === 'superadmin';

  const today = new Date();
  const currentYear = today.getFullYear();
  const currentMonth = today.getMonth() + 1;
  const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
  const todayStr = today.toISOString().split('T')[0];

  // Filter States
  const [employeeStatus, setEmployeeStatus] = useState('all');
  
  const [attStartDate, setAttStartDate] = useState(firstDayOfMonth);
  const [attEndDate, setAttEndDate] = useState(todayStr);
  const [attStatus, setAttStatus] = useState('all');

  const [leaveStartDate, setLeaveStartDate] = useState(`${currentYear}-01-01`);
  const [leaveEndDate, setLeaveEndDate] = useState(todayStr);
  const [leaveStatus, setLeaveStatus] = useState('all');

  const [payrollYear, setPayrollYear] = useState(currentYear);
  const [payrollMonth, setPayrollMonth] = useState(currentMonth);

  const [ticketStatus, setTicketStatus] = useState('all');
  const [ticketCategory, setTicketCategory] = useState('all');

  const [downloading, setDownloading] = useState<string | null>(null);

  // Generic Download Handler
  const handleDownloadReport = async (endpoint: string, params: Record<string, any>, reportKey: string) => {
    try {
      setDownloading(reportKey);
      const authToken = token || Cookies.get('hrms_token');
      
      const query = new URLSearchParams();
      Object.entries(params).forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== 'all' && v !== '') {
          query.append(k, String(v));
        }
      });

      const url = `${API_BASE_URL}${endpoint}${query.toString() ? `?${query.toString()}` : ''}`;

      const res = await fetch(url, {
        headers: {
          ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
        },
      });

      if (!res.ok) {
        throw new Error('Failed to generate and download report.');
      }

      // Extract filename from header or fallback
      let filename = `${reportKey}_export.csv`;
      const disposition = res.headers.get('content-disposition');
      if (disposition && disposition.indexOf('filename=') !== -1) {
        const matches = /filename="([^"]*)"/.exec(disposition);
        if (matches && matches[1]) filename = matches[1];
      }

      const blob = await res.blob();
      const downloadUrl = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', filename);
      document.body.appendChild(link);
      link.click();
      link.parentNode?.removeChild(link);
      window.URL.revokeObjectURL(downloadUrl);
    } catch (err: any) {
      console.error('Report download error', err);
      alert(err?.message || 'Error downloading report. Please check privileges.');
    } finally {
      setDownloading(null);
    }
  };

  if (!isAdminOrSuper) {
    return (
      <AppLayout title="Reports & Analytics">
        <div className="p-8 max-w-lg mx-auto text-center">
          <Shield className="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h2 className="text-base font-bold text-slate-800">Restricted Access</h2>
          <p className="text-xs text-slate-500 mt-1">
            The Enterprise Reports Center is reserved for Company Administrators and Superadmins.
          </p>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout
      title="Enterprise Reports Center"
      subtitle="Export corporate rosters, biometric attendance logs, leave balances, payroll audits, and IT tickets to CSV/Excel"
    >
      <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
        {/* Banner */}
        <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 rounded-2xl p-6 text-white shadow-xl flex flex-col md:flex-row items-start md:center justify-between gap-4">
          <div className="space-y-1">
            <div className="flex items-center space-x-2">
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                Audit & Compliance Ready
              </span>
              <span className="text-xs text-slate-400">Excel / CSV Supported UTF-8</span>
            </div>
            <h2 className="text-lg font-bold tracking-tight">Standard Executive & Regulatory Reports</h2>
            <p className="text-xs text-slate-300 max-w-2xl leading-relaxed">
              Export verified data points for statutory compliance audits, provident fund declarations, tax computations, attendance monitoring, and internal HR reviews.
            </p>
          </div>
          <div className="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center border border-white/20">
            <FileSpreadsheet className="w-6 h-6 text-emerald-300" />
          </div>
        </div>

        {/* Report Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* 1. EMPLOYEE MASTER REPORT */}
          <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-sm transition flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between mb-3">
                <div className="flex items-center space-x-2.5">
                  <div className="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                    <Users className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-slate-900">Employee Master Roster</h3>
                    <p className="text-[11px] text-slate-400">Full demographic, salary, and statutory tax profile</p>
                  </div>
                </div>
                <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600">CSV / EXCEL</span>
              </div>

              <p className="text-xs text-slate-500 leading-relaxed mb-4">
                Exports all active and archived staff with department, shift, PAN, Aadhaar, UAN, ESIC, bank account numbers, IFSC, and gross compensation metrics.
              </p>

              {/* Filters */}
              <div className="bg-slate-50 rounded-xl p-3 border border-slate-100 mb-4 text-xs space-y-2">
                <div>
                  <label className="block text-[11px] font-semibold text-slate-600 mb-1">Employment Status</label>
                  <select
                    value={employeeStatus}
                    onChange={(e) => setEmployeeStatus(e.target.value)}
                    className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="all">All Records (Active, Resigned, On Leave)</option>
                    <option value="active">Active Staff Only</option>
                    <option value="resigned">Resigned / Separated</option>
                  </select>
                </div>
              </div>
            </div>

            <button
              onClick={() => handleDownloadReport('/reports/employee-master', { employment_status: employeeStatus }, 'employee_master')}
              disabled={downloading === 'employee_master'}
              className="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition disabled:opacity-50"
            >
              <ArrowDownToLine className="w-4 h-4" />
              <span>{downloading === 'employee_master' ? 'Generating Export...' : 'Download Employee Master (CSV)'}</span>
            </button>
          </div>

          {/* 2. ATTENDANCE & BIOMETRIC LOGS REPORT */}
          <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-sm transition flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between mb-3">
                <div className="flex items-center space-x-2.5">
                  <div className="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
                    <CalendarCheck className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-slate-900">Attendance & Biometric Register</h3>
                    <p className="text-[11px] text-slate-400">Punch in/out timestamps, hours worked & regularization notes</p>
                  </div>
                </div>
                <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600">CSV / EXCEL</span>
              </div>

              <p className="text-xs text-slate-500 leading-relaxed mb-4">
                Chronological daily log of employee shifts, punch timestamps, working hours, late arrivals, geofence radius deviation, and admin regularizations.
              </p>

              {/* Filters */}
              <div className="bg-slate-50 rounded-xl p-3 border border-slate-100 mb-4 text-xs space-y-2">
                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 mb-1">From Date</label>
                    <input
                      type="date"
                      value={attStartDate}
                      onChange={(e) => setAttStartDate(e.target.value)}
                      className="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-slate-700 focus:outline-none"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 mb-1">To Date</label>
                    <input
                      type="date"
                      value={attEndDate}
                      onChange={(e) => setAttEndDate(e.target.value)}
                      className="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-slate-700 focus:outline-none"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-[11px] font-semibold text-slate-600 mb-1">Status Filter</label>
                  <select
                    value={attStatus}
                    onChange={(e) => setAttStatus(e.target.value)}
                    className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-700 focus:outline-none"
                  >
                    <option value="all">All Statuses (Present, Late, Half-Day, Absent)</option>
                    <option value="present">Present Only</option>
                    <option value="half_day">Half Day Only</option>
                    <option value="late">Late Arrivals Only</option>
                  </select>
                </div>
              </div>
            </div>

            <button
              onClick={() => handleDownloadReport('/reports/attendance', { start_date: attStartDate, end_date: attEndDate, status: attStatus }, 'attendance_register')}
              disabled={downloading === 'attendance_register'}
              className="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold shadow-xs transition disabled:opacity-50"
            >
              <ArrowDownToLine className="w-4 h-4" />
              <span>{downloading === 'attendance_register' ? 'Generating Export...' : 'Download Attendance Register (CSV)'}</span>
            </button>
          </div>

          {/* 3. PAYROLL DISBURSEMENT AUDIT */}
          <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-sm transition flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between mb-3">
                <div className="flex items-center space-x-2.5">
                  <div className="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                    <CreditCard className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-slate-900">Payroll Disbursement Register</h3>
                    <p className="text-[11px] text-slate-400">Earned wages, EPF, ESIC, Tax deductions & Net salary</p>
                  </div>
                </div>
                <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600">CSV / EXCEL</span>
              </div>

              <p className="text-xs text-slate-500 leading-relaxed mb-4">
                Statutory monthly payroll sheet with payable day breakdown (working days, Sundays, holidays, late penalty half-days) and statutory deductions.
              </p>

              {/* Filters */}
              <div className="bg-slate-50 rounded-xl p-3 border border-slate-100 mb-4 text-xs space-y-2">
                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 mb-1">Month</label>
                    <select
                      value={payrollMonth}
                      onChange={(e) => setPayrollMonth(Number(e.target.value))}
                      className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-700 focus:outline-none"
                    >
                      {[
                        'January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'
                      ].map((m, idx) => (
                        <option key={m} value={idx + 1}>{m}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 mb-1">Year</label>
                    <input
                      type="number"
                      value={payrollYear}
                      onChange={(e) => setPayrollYear(Number(e.target.value))}
                      className="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-slate-700 focus:outline-none"
                    />
                  </div>
                </div>
              </div>
            </div>

            <button
              onClick={() => handleDownloadReport('/reports/payroll', { month: payrollMonth, year: payrollYear }, 'payroll_register')}
              disabled={downloading === 'payroll_register'}
              className="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-semibold shadow-xs transition disabled:opacity-50"
            >
              <ArrowDownToLine className="w-4 h-4" />
              <span>{downloading === 'payroll_register' ? 'Generating Export...' : 'Download Payroll Register (CSV)'}</span>
            </button>
          </div>

          {/* 4. LEAVES & TIME OFF REPORT */}
          <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-sm transition flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between mb-3">
                <div className="flex items-center space-x-2.5">
                  <div className="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                    <Calendar className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-slate-900">Leaves & Absence Report</h3>
                    <p className="text-[11px] text-slate-400">Casual, Sick, Privilege leaves and approvals</p>
                  </div>
                </div>
                <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600">CSV / EXCEL</span>
              </div>

              <p className="text-xs text-slate-500 leading-relaxed mb-4">
                Detailed record of all leave applications, duration, half-day tags, approval statuses, employee reasons, and reviewing admin remarks.
              </p>

              {/* Filters */}
              <div className="bg-slate-50 rounded-xl p-3 border border-slate-100 mb-4 text-xs space-y-2">
                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 mb-1">From Date</label>
                    <input
                      type="date"
                      value={leaveStartDate}
                      onChange={(e) => setLeaveStartDate(e.target.value)}
                      className="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-slate-700 focus:outline-none"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 mb-1">To Date</label>
                    <input
                      type="date"
                      value={leaveEndDate}
                      onChange={(e) => setLeaveEndDate(e.target.value)}
                      className="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-slate-700 focus:outline-none"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-[11px] font-semibold text-slate-600 mb-1">Approval Status</label>
                  <select
                    value={leaveStatus}
                    onChange={(e) => setLeaveStatus(e.target.value)}
                    className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-700 focus:outline-none"
                  >
                    <option value="all">All Applications (Approved, Pending, Rejected)</option>
                    <option value="approved">Approved Only</option>
                    <option value="pending">Pending Review</option>
                    <option value="rejected">Rejected</option>
                  </select>
                </div>
              </div>
            </div>

            <button
              onClick={() => handleDownloadReport('/reports/leaves', { start_date: leaveStartDate, end_date: leaveEndDate, status: leaveStatus }, 'leaves_report')}
              disabled={downloading === 'leaves_report'}
              className="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition disabled:opacity-50"
            >
              <ArrowDownToLine className="w-4 h-4" />
              <span>{downloading === 'leaves_report' ? 'Generating Export...' : 'Download Leaves Report (CSV)'}</span>
            </button>
          </div>

          {/* 5. IT SUPPORT & TICKETS AUDIT */}
          <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-sm transition flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between mb-3">
                <div className="flex items-center space-x-2.5">
                  <div className="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                    <LifeBuoy className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-slate-900">IT Helpdesk & Tickets Audit</h3>
                    <p className="text-[11px] text-slate-400">Technical issues, resolution times & support SLA metrics</p>
                  </div>
                </div>
                <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600">CSV / EXCEL</span>
              </div>

              <p className="text-xs text-slate-500 leading-relaxed mb-4">
                Complete history of internal IT support requests, category breakdowns (Hardware, VPN, Software), priority tags, and resolution summaries.
              </p>

              {/* Filters */}
              <div className="bg-slate-50 rounded-xl p-3 border border-slate-100 mb-4 text-xs space-y-2">
                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                    <select
                      value={ticketStatus}
                      onChange={(e) => setTicketStatus(e.target.value)}
                      className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-700 focus:outline-none"
                    >
                      <option value="all">All Statuses</option>
                      <option value="open">Open</option>
                      <option value="in_progress">In Progress</option>
                      <option value="resolved">Resolved</option>
                      <option value="closed">Closed</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 mb-1">Category</label>
                    <select
                      value={ticketCategory}
                      onChange={(e) => setTicketCategory(e.target.value)}
                      className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-700 focus:outline-none"
                    >
                      <option value="all">All Categories</option>
                      <option value="hardware">Hardware</option>
                      <option value="software">Software</option>
                      <option value="network">Network / VPN</option>
                      <option value="access_credentials">Account / Password</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <button
              onClick={() => handleDownloadReport('/reports/it-tickets', { status: ticketStatus, category: ticketCategory }, 'it_tickets_report')}
              disabled={downloading === 'it_tickets_report'}
              className="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs transition disabled:opacity-50"
            >
              <ArrowDownToLine className="w-4 h-4" />
              <span>{downloading === 'it_tickets_report' ? 'Generating Export...' : 'Download IT Tickets Audit (CSV)'}</span>
            </button>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}

