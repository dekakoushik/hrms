'use client';

import React, { useState, useEffect } from 'react';
import ProtectedRoute from '@/components/ProtectedRoute';
import AppLayout from '@/components/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { apiRequest } from '@/lib/api';
import { PayrollCalculateResponse, EmployeePayrollItem } from '@/types/payroll';
import {
  CreditCard,
  Calendar,
  AlertTriangle,
  CheckCircle2,
  Download,
  FileText,
  DollarSign,
  TrendingDown,
  UserCheck,
  RefreshCw,
  Clock,
  Layers,
  ChevronDown,
  X,
  Search,
  Filter,
  Printer,
  ShieldCheck,
} from 'lucide-react';

interface EmployeePayslipRecord {
  id: number;
  payslip_number: string;
  year: number;
  month: number;
  period_start: string;
  period_end: string;
  total_month_days: number;
  sundays_count: number;
  holidays_count: number;
  present_days: number;
  total_payable_days: number;
  earned_basic: number;
  earned_gross: number;
  employee_pf: number;
  employee_esic: number;
  monthly_tax: number;
  total_deductions: number;
  net_salary: number;
  status: string;
  created_at: string;
  employee?: {
    id: number;
    name: string;
    employee_code: string;
    department?: { name: string };
    designation?: { title: string };
    company?: { name: string };
  };
}

export default function PayrollPage() {
  const { user, token } = useAuth();
  const isAdminOrSuper = user?.role === 'superadmin' || user?.role === 'admin';
  const isSuperadmin = user?.role === 'superadmin';

  const today = new Date();
  const [selectedYear, setSelectedYear] = useState(today.getFullYear());
  const [selectedMonth, setSelectedMonth] = useState(today.getMonth() + 1); // 1-12
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [payrollData, setPayrollData] = useState<PayrollCalculateResponse | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedItem, setSelectedItem] = useState<EmployeePayrollItem | null>(null);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Employee personal payslips state
  const [myPayslips, setMyPayslips] = useState<EmployeePayslipRecord[]>([]);
  const [loadingMyPayslips, setLoadingMyPayslips] = useState(false);

  const showToast = (type: 'success' | 'error', message: string) => {
    setToast({ type, message });
    setTimeout(() => setToast(null), 5000);
  };

  const getMonthName = (m: number) => {
    return new Date(2026, m - 1, 1).toLocaleString('default', { month: 'long' });
  };

  // 1. Admin/Superadmin batch calculation
  const loadAdminPayrollPreview = async (saveBatch = false) => {
    if (!token || !isAdminOrSuper) return;
    try {
      if (saveBatch) {
        setSaving(true);
      } else {
        setLoading(true);
      }

      const endpoint = saveBatch ? '/payroll/process' : `/payroll/calculate?year=${selectedYear}&month=${selectedMonth}`;
      const res = await apiRequest<PayrollCalculateResponse>(
        endpoint,
        saveBatch
          ? {
              method: 'POST',
              body: JSON.stringify({ year: selectedYear, month: selectedMonth, save: true }),
            }
          : {},
        token
      );

      setPayrollData(res);
      if (saveBatch) {
        showToast('success', `Monthly Payroll for ${getMonthName(selectedMonth)} ${selectedYear} processed and saved successfully.`);
      }
    } catch (err: any) {
      showToast('error', err.message || 'Failed to calculate monthly payroll.');
    } finally {
      setLoading(false);
      setSaving(false);
    }
  };

  // 2. Employee personal payslips fetching
  const loadEmployeePayslips = async () => {
    if (!token) return;
    try {
      setLoadingMyPayslips(true);
      const res = await apiRequest<{ success: boolean; data: { data: EmployeePayslipRecord[] } }>(
        `/payroll/my-payslips?year=${selectedYear}&month=${selectedMonth}`,
        {},
        token
      );
      setMyPayslips(res.data?.data || []);
    } catch (err: any) {
      console.error('Failed to load personal payslips', err);
    } finally {
      setLoadingMyPayslips(false);
    }
  };

  useEffect(() => {
    if (isAdminOrSuper) {
      loadAdminPayrollPreview(false);
    } else {
      loadEmployeePayslips();
    }
  }, [selectedYear, selectedMonth, token, isAdminOrSuper]);

  const filteredItems = (payrollData?.data || []).filter((item) => {
    if (!searchTerm) return true;
    const term = searchTerm.toLowerCase();
    return (
      item.employee_name.toLowerCase().includes(term) ||
      item.employee_code.toLowerCase().includes(term) ||
      item.department.toLowerCase().includes(term)
    );
  });

  // Action for downloading/printing payslip
  const handleDownloadPayslip = async (payslipId: number) => {
    if (!token) return;
    try {
      const url = `${process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'}/payroll/payslips/${payslipId}/download`;
      // Open in clean printable window
      const newWin = window.open('', '_blank');
      if (newWin) {
        newWin.document.write('Loading official payslip document...');
        const response = await fetch(url, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'text/html',
          },
        });
        const html = await response.text();
        newWin.document.open();
        newWin.document.write(html);
        newWin.document.close();
      }
    } catch (err: any) {
      showToast('error', 'Failed to retrieve payslip document.');
    }
  };

  // =========================================================================
  // EMPLOYEE VIEW: Clean, Simple Payslip Download Portal
  // =========================================================================
  if (!isAdminOrSuper) {
    return (
      <ProtectedRoute>
        <AppLayout
          title="My Salary Payslips"
          subtitle="View and download your monthly compensation statements"
          action={
            <button
              onClick={loadEmployeePayslips}
              disabled={loadingMyPayslips}
              className="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
            >
              <RefreshCw className={`w-3.5 h-3.5 ${loadingMyPayslips ? 'animate-spin text-blue-600' : ''}`} />
              Refresh
            </button>
          }
        >
          <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-5xl mx-auto pb-12">
            {/* Year & Month Selection Bar */}
            <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
              <div className="flex flex-wrap items-center gap-4">
                <div className="flex items-center gap-2">
                  <label className="text-xs font-bold text-slate-500 uppercase tracking-wider">Select Month:</label>
                  <select
                    value={selectedMonth}
                    onChange={(e) => setSelectedMonth(Number(e.target.value))}
                    className="bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-800 rounded-xl px-3 py-2 focus:outline-none"
                  >
                    {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
                      <option key={m} value={m}>
                        {getMonthName(m)}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="flex items-center gap-2">
                  <label className="text-xs font-bold text-slate-500 uppercase tracking-wider">Select Year:</label>
                  <select
                    value={selectedYear}
                    onChange={(e) => setSelectedYear(Number(e.target.value))}
                    className="bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-800 rounded-xl px-3 py-2 focus:outline-none"
                  >
                    {[2024, 2025, 2026, 2027].map((y) => (
                      <option key={y} value={y}>
                        {y}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="text-xs text-slate-500 flex items-center gap-1.5">
                <ShieldCheck className="w-4 h-4 text-emerald-600" />
                <span>Verified Official Salary Disbursals</span>
              </div>
            </div>

            {/* Employee's Payslips Ledger */}
            <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
              <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                  <h3 className="font-bold text-sm text-slate-900">
                    Salary Payslip for {getMonthName(selectedMonth)} {selectedYear}
                  </h3>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Official payslip document generated with attendance, EPF, and tax breakdown.
                  </p>
                </div>
              </div>

              {loadingMyPayslips ? (
                <div className="p-16 text-center text-slate-400 text-xs">
                  <RefreshCw className="h-6 w-6 animate-spin mx-auto mb-2 text-blue-600" />
                  Loading your payslip record...
                </div>
              ) : myPayslips.length === 0 ? (
                <div className="p-16 text-center text-slate-400 space-y-2 max-w-md mx-auto">
                  <div className="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                    <FileText className="w-6 h-6" />
                  </div>
                  <h4 className="text-sm font-bold text-slate-700">No Payslip Available for this Month</h4>
                  <p className="text-xs text-slate-500 leading-relaxed">
                    The monthly payroll for <strong>{getMonthName(selectedMonth)} {selectedYear}</strong> has not been finalized by the finance team yet. Please select another month or check back once the cycle completes.
                  </p>
                </div>
              ) : (
                <div className="divide-y divide-slate-100">
                  {myPayslips.map((ps) => (
                    <div key={ps.id} className="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 hover:bg-slate-50/50 transition">
                      <div className="space-y-2">
                        <div className="flex items-center gap-2">
                          <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 font-mono">
                            {ps.payslip_number}
                          </span>
                          <span className="text-xs font-semibold text-slate-500">
                            Period: {ps.period_start} to {ps.period_end}
                          </span>
                        </div>

                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-1 text-xs">
                          <div>
                            <span className="text-slate-400 block text-[11px]">Payable Days:</span>
                            <span className="font-bold text-slate-800">{ps.total_payable_days} of {ps.total_month_days} Days</span>
                          </div>
                          <div>
                            <span className="text-slate-400 block text-[11px]">Earned Gross:</span>
                            <span className="font-bold text-emerald-600 font-mono">₹{ps.earned_gross.toFixed(2)}</span>
                          </div>
                          <div>
                            <span className="text-slate-400 block text-[11px]">Total Deductions:</span>
                            <span className="font-bold text-red-600 font-mono">₹{ps.total_deductions.toFixed(2)}</span>
                          </div>
                          <div>
                            <span className="text-slate-400 block text-[11px]">Net Take-Home:</span>
                            <span className="font-bold text-blue-700 font-mono text-sm">₹{ps.net_salary.toFixed(2)}</span>
                          </div>
                        </div>
                      </div>

                      <div className="shrink-0 flex items-center gap-3">
                        <button
                          onClick={() => handleDownloadPayslip(ps.id)}
                          className="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer"
                        >
                          <Download className="w-4 h-4" />
                          <span>Download Payslip</span>
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </AppLayout>
      </ProtectedRoute>
    );
  }

  // =========================================================================
  // ADMIN & SUPERADMIN VIEW: Full Batch Calculation, Formula & Roster Ledger
  // =========================================================================
  return (
    <ProtectedRoute>
      <AppLayout
        title="Payroll & Compensation Suite"
        subtitle="Month-end salary calculations with automated Sunday, Holiday, and Late Penalty deductions"
        action={
          <div className="flex items-center gap-2">
            <button
              onClick={() => loadAdminPayrollPreview(false)}
              disabled={loading || saving}
              className="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
            >
              <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin text-blue-600' : ''}`} />
              Recalculate
            </button>
            {isSuperadmin && (
              <button
                onClick={() => loadAdminPayrollPreview(true)}
                disabled={loading || saving}
                className="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60"
              >
                <CheckCircle2 className="w-3.5 h-3.5" />
                {saving ? 'Processing...' : 'Finalize & Save Payroll'}
              </button>
            )}
          </div>
        }
      >
        <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto pb-12">
          {/* Toast Notification */}
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

          {/* Period Selector Bar */}
          <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-2">
                <label className="text-xs font-bold text-slate-500 uppercase tracking-wider">Payroll Month:</label>
                <select
                  value={selectedMonth}
                  onChange={(e) => setSelectedMonth(Number(e.target.value))}
                  className="bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-800 rounded-xl px-3 py-2 focus:outline-none"
                >
                  {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
                    <option key={m} value={m}>
                      {getMonthName(m)}
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex items-center gap-2">
                <label className="text-xs font-bold text-slate-500 uppercase tracking-wider">Year:</label>
                <select
                  value={selectedYear}
                  onChange={(e) => setSelectedYear(Number(e.target.value))}
                  className="bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-800 rounded-xl px-3 py-2 focus:outline-none"
                >
                  {[2024, 2025, 2026, 2027].map((y) => (
                    <option key={y} value={y}>
                      {y}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="flex items-center gap-3 text-xs text-slate-600">
              <span className="px-3 py-1.5 bg-blue-50 text-blue-700 font-semibold rounded-lg border border-blue-100 flex items-center gap-1.5">
                <Calendar className="w-3.5 h-3.5" />
                Period: Day 1 to End of Month ({payrollData?.period?.period_start} to {payrollData?.period?.period_end})
              </span>
              <span className="px-3 py-1.5 bg-slate-100 text-slate-700 font-semibold rounded-lg border border-slate-200">
                Mode: Month-to-Date (MTD Completed Shifts)
              </span>
            </div>
          </div>

          {/* Payroll Accounting Rules Banner */}
          <div className="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white p-5 rounded-2xl shadow-sm space-y-3">
            <div className="flex items-center justify-between">
              <h3 className="text-sm font-bold flex items-center gap-2">
                <CreditCard className="w-4 h-4 text-blue-400" />
                Payroll Accounting & Attendance Policy ({getMonthName(selectedMonth)} {selectedYear})
              </h3>
              <span className="text-[11px] bg-white/10 px-2.5 py-0.5 rounded-full font-mono text-blue-200">
                {payrollData?.period?.calendar_days || 30} Total Calendar Days
              </span>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs text-slate-300 pt-2 border-t border-white/10">
              <div className="bg-white/5 p-3 rounded-xl border border-white/10">
                <span className="font-bold text-white block mb-0.5">1. Sundays & Official Holidays</span>
                <p className="text-[11px] leading-relaxed text-slate-300">
                  Sundays and Official Holidays that fall within the completed days of the month are paid ({payrollData?.period?.sundays_count || 0} Sundays + {payrollData?.period?.holidays_count || 0} Holidays elapsed so far). Future dates are never credited prematurely.
                </p>
              </div>

              <div className="bg-white/5 p-3 rounded-xl border border-white/10">
                <span className="font-bold text-amber-300 block mb-0.5">2. Consecutive Late Penalty</span>
                <p className="text-[11px] leading-relaxed text-slate-300">
                  Late arrivals up to 5 consecutive days are recorded normally. Beyond 5 continuous late days, each late mark is penalized as <strong>0.5 day (Half Day)</strong>.
                </p>
              </div>

              <div className="bg-white/5 p-3 rounded-xl border border-white/10">
                <span className="font-bold text-emerald-300 block mb-0.5">3. Shift Completion & Prorating</span>
                <p className="text-[11px] leading-relaxed text-slate-300">
                  Only completed shifts (with verified punch out or post-shift hours) count toward paid present days. Net Salary is prorated based on completed payable days.
                </p>
              </div>
            </div>
          </div>

          {/* Metric Summary Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
              <div className="flex items-center justify-between text-slate-400 mb-2">
                <span className="text-xs font-bold uppercase tracking-wider">Eligible Staff</span>
                <UserCheck className="w-4 h-4 text-blue-600" />
              </div>
              <div className="text-2xl font-bold text-slate-900">
                {payrollData?.summary?.total_employees || 0}
              </div>
              <div className="text-[11px] text-slate-500 mt-1">Active on monthly roster</div>
            </div>

            <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
              <div className="flex items-center justify-between text-slate-400 mb-2">
                <span className="text-xs font-bold uppercase tracking-wider">Gross Payout</span>
                <DollarSign className="w-4 h-4 text-emerald-600" />
              </div>
              <div className="text-2xl font-bold text-emerald-600">
                ₹{Number(payrollData?.summary?.total_gross_payout || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
              <div className="text-[11px] text-slate-500 mt-1">Total earned gross earnings</div>
            </div>

            <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
              <div className="flex items-center justify-between text-slate-400 mb-2">
                <span className="text-xs font-bold uppercase tracking-wider">Statutory Deductions</span>
                <TrendingDown className="w-4 h-4 text-amber-600" />
              </div>
              <div className="text-2xl font-bold text-amber-600">
                ₹{Number(payrollData?.summary?.total_deductions || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
              <div className="text-[11px] text-slate-500 mt-1">EPF + ESIC + Monthly Tax</div>
            </div>

            <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
              <div className="flex items-center justify-between text-slate-400 mb-2">
                <span className="text-xs font-bold uppercase tracking-wider">Net Payout</span>
                <CreditCard className="w-4 h-4 text-blue-600" />
              </div>
              <div className="text-2xl font-bold text-blue-600">
                ₹{Number(payrollData?.summary?.total_net_payout || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
              <div className="text-[11px] text-slate-500 mt-1">Bank disbursement total</div>
            </div>
          </div>

          {/* Employee Breakdown Table */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div className="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
              <div>
                <h3 className="font-bold text-sm text-slate-900">
                  Monthly Employee Payroll Breakdown ({getMonthName(selectedMonth)} {selectedYear})
                </h3>
                <p className="text-xs text-slate-500">
                  Includes Sundays, Gazetted Holidays, Present punches, and Late Penalty half-day calculations.
                </p>
              </div>

              <div className="relative min-w-[240px]">
                <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                <input
                  type="text"
                  placeholder="Filter by name, ID or department..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 focus:bg-white focus:outline-none"
                />
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead>
                  <tr className="border-b border-slate-200 bg-slate-50/75 text-slate-500 uppercase font-semibold text-[11px]">
                    <th className="py-3 px-4">Employee</th>
                    <th className="py-3 px-4">Department</th>
                    <th className="py-3 px-4 text-center">Month Days</th>
                    <th className="py-3 px-4 text-center">Present / Paid Off</th>
                    <th className="py-3 px-4 text-center">Sundays + Holidays</th>
                    <th className="py-3 px-4 text-center">Late Days / Penalty</th>
                    <th className="py-3 px-4 text-center">Payable Days</th>
                    <th className="py-3 px-4 text-right">Earned Gross</th>
                    <th className="py-3 px-4 text-right">Deductions</th>
                    <th className="py-3 px-4 text-right">Net Salary</th>
                    <th className="py-3 px-4 text-center">Action</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {loading ? (
                    <tr>
                      <td colSpan={11} className="py-12 text-center text-slate-400">
                        <RefreshCw className="h-6 w-6 animate-spin mx-auto mb-2 text-blue-600" />
                        Calculating monthly payroll ledger...
                      </td>
                    </tr>
                  ) : filteredItems.length === 0 ? (
                    <tr>
                      <td colSpan={11} className="py-12 text-center text-slate-400">
                        No active employee records found for this period.
                      </td>
                    </tr>
                  ) : (
                    filteredItems.map((emp) => (
                      <tr key={emp.employee_id} className="hover:bg-slate-50/70 transition">
                        <td className="py-3 px-4">
                          <div>
                            <p className="font-bold text-slate-900">{emp.employee_name}</p>
                            <p className="text-[10px] text-slate-500 font-mono">{emp.employee_code}</p>
                          </div>
                        </td>
                        <td className="py-3 px-4">
                          <span className="text-slate-700">{emp.department}</span>
                          <span className="block text-[10px] text-slate-400">{emp.designation}</span>
                        </td>
                        <td className="py-3 px-4 text-center font-mono font-medium text-slate-700">
                          {emp.calendar_days}d
                        </td>
                        <td className="py-3 px-4 text-center font-mono text-emerald-700">
                          {emp.present_days}d
                        </td>
                        <td className="py-3 px-4 text-center font-mono text-blue-700">
                          {emp.sundays_count} + {emp.holidays_count}
                        </td>
                        <td className="py-3 px-4 text-center font-mono">
                          {emp.late_penalty_half_days > 0 ? (
                            <span className="text-red-600 font-bold">
                              {emp.late_days_count}d ({emp.late_penalty_half_days} pen)
                            </span>
                          ) : (
                            <span className="text-slate-500">{emp.late_days_count}d (0 pen)</span>
                          )}
                        </td>
                        <td className="py-3 px-4 text-center font-mono font-bold text-slate-900 bg-slate-50">
                          {emp.total_payable_days}d
                        </td>
                        <td className="py-3 px-4 text-right font-mono font-bold text-emerald-700">
                          ₹{emp.earned_gross.toFixed(2)}
                        </td>
                        <td className="py-3 px-4 text-right font-mono text-red-600">
                          ₹{emp.total_deductions.toFixed(2)}
                        </td>
                        <td className="py-3 px-4 text-right font-mono font-extrabold text-blue-700">
                          ₹{emp.net_salary.toFixed(2)}
                        </td>
                        <td className="py-3 px-4 text-center">
                          <button
                            onClick={() => setSelectedItem(emp)}
                            className="px-2.5 py-1 text-[11px] font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition cursor-pointer"
                          >
                            Details
                          </button>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>

          {/* Employee Breakdown Drawer / Modal */}
          {selectedItem && (
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
              <div className="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-xl w-full overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <div className="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                  <div>
                    <h3 className="font-bold text-slate-900 text-sm">
                      {selectedItem.employee_name} ({selectedItem.employee_code})
                    </h3>
                    <p className="text-xs text-slate-500">
                      {selectedItem.department} &bull; {selectedItem.designation}
                    </p>
                  </div>
                  <button
                    onClick={() => setSelectedItem(null)}
                    className="text-slate-400 hover:text-slate-600 p-1 rounded-lg"
                  >
                    <X className="w-4 h-4" />
                  </button>
                </div>

                <div className="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
                  {/* Attendance & Day Accounting */}
                  <div className="p-4 bg-slate-50 rounded-xl space-y-2 border border-slate-100 text-xs">
                    <span className="font-bold uppercase tracking-wider text-slate-600 block text-[10px]">
                      Days Accounting ({getMonthName(selectedMonth)} {selectedYear})
                    </span>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center pt-1 font-mono">
                      <div className="p-2 bg-white rounded-lg border border-slate-200">
                        <span className="text-[10px] text-slate-400 block">Calendar</span>
                        <span className="font-bold text-slate-800">{selectedItem.calendar_days}d</span>
                      </div>
                      <div className="p-2 bg-white rounded-lg border border-slate-200">
                        <span className="text-[10px] text-slate-400 block">Present</span>
                        <span className="font-bold text-emerald-700">{selectedItem.present_days}d</span>
                      </div>
                      <div className="p-2 bg-white rounded-lg border border-slate-200">
                        <span className="text-[10px] text-slate-400 block">Sun + Hol</span>
                        <span className="font-bold text-blue-700">
                          {selectedItem.sundays_count + selectedItem.holidays_count}d
                        </span>
                      </div>
                      <div className="p-2 bg-white rounded-lg border border-slate-200">
                        <span className="text-[10px] text-slate-400 block">Payable</span>
                        <span className="font-bold text-indigo-700">{selectedItem.total_payable_days}d</span>
                      </div>
                    </div>
                  </div>

                  {/* Earnings vs Deductions Breakdown */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {/* Earnings */}
                    <div className="space-y-2">
                      <h4 className="text-xs font-bold uppercase text-emerald-800 tracking-wider">
                        Earned Components
                      </h4>
                      <div className="p-3 bg-emerald-50/40 border border-emerald-200 rounded-xl space-y-2 text-xs">
                        <div className="flex justify-between">
                          <span className="text-slate-600">Basic Salary</span>
                          <span className="font-mono font-semibold">₹{selectedItem.earned_basic.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-slate-600">Dearness Allowance (20%)</span>
                          <span className="font-mono">₹{selectedItem.da_amount.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-slate-600">HRA (60%)</span>
                          <span className="font-mono">₹{selectedItem.hra_amount.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-slate-600">Conveyance (20%)</span>
                          <span className="font-mono">₹{selectedItem.ca_amount.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-slate-600">Medical / Other (8%)</span>
                          <span className="font-mono">₹{selectedItem.ea_amount.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-slate-600">Special Allowance (20%)</span>
                          <span className="font-mono">₹{selectedItem.sa_amount.toFixed(2)}</span>
                        </div>
                        <div className="pt-2 border-t border-emerald-200 flex justify-between font-bold text-slate-900">
                          <span>Earned Gross Payout</span>
                          <span className="font-mono text-emerald-700">₹{selectedItem.earned_gross.toFixed(2)}</span>
                        </div>
                      </div>
                    </div>

                    {/* Deductions */}
                    <div className="space-y-2">
                      <h4 className="text-xs font-bold uppercase text-red-800 tracking-wider">
                        Statutory Deductions
                      </h4>
                      <div className="p-3 bg-red-50/40 border border-red-200 rounded-xl space-y-2 text-xs">
                        <div className="flex justify-between">
                          <span className="text-slate-600">Employee PF (12%)</span>
                          <span className="font-mono">₹{selectedItem.employee_pf.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-slate-600">ESIC (0.75%)</span>
                          <span className="font-mono">₹{selectedItem.employee_esic.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-slate-600">TDS / Monthly Tax</span>
                          <span className="font-mono">₹{selectedItem.monthly_tax.toFixed(2)}</span>
                        </div>
                        <div className="pt-8 border-t border-red-200 flex justify-between font-bold text-slate-900">
                          <span>Total Deductions</span>
                          <span className="font-mono text-red-700">₹{selectedItem.total_deductions.toFixed(2)}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Net Take-Home */}
                  <div className="p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between">
                    <div>
                      <span className="text-xs font-bold uppercase text-blue-900 tracking-wider block">
                        Net Take-Home Salary
                      </span>
                      <span className="text-[11px] text-blue-700">
                        Disbursed to registered bank account
                      </span>
                    </div>
                    <div className="text-2xl font-bold font-mono text-blue-700">
                      ₹{selectedItem.net_salary.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                    </div>
                  </div>
                </div>

                <div className="p-4 border-t border-slate-100 flex items-center justify-end bg-slate-50/50">
                  <button
                    onClick={() => setSelectedItem(null)}
                    className="px-4 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl cursor-pointer"
                  >
                    Close
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </AppLayout>
    </ProtectedRoute>
  );
}
