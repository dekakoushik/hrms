'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { apiRequest } from '@/lib/api';
import { Employee, SettingsOverviewData } from '@/types/employee';
import ProtectedRoute from '@/components/ProtectedRoute';
import AppLayout from '@/components/AppLayout';
import { getCachedSettings, fetchSettingsOverview } from '@/lib/settingsCache';

export default function EmployeesListPage() {
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [settings, setSettings] = useState<SettingsOverviewData | null>(() => (getCachedSettings() as any) || null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [filterCompany, setFilterCompany] = useState('');
  const [filterDepartment, setFilterDepartment] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [viewEmployee, setViewEmployee] = useState<Employee | null>(null);
  const [deleteConfirmId, setDeleteConfirmId] = useState<number | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Progressive Independent Loading: Show employees immediately, load dropdown filters asynchronously
  useEffect(() => {
    let isMounted = true;

    // 1. Fetch employee list first (critical path)
    apiRequest<{ data: Employee[] }>('/employees?per_page=100')
      .then((empRes) => {
        if (!isMounted) return;
        setEmployees(empRes.data || []);
      })
      .catch((err: any) => {
        if (!isMounted) return;
        setToast({ type: 'error', message: err.message || 'Failed to load employees directory' });
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    // 2. Fetch master filter options in parallel with deduplication
    fetchSettingsOverview()
      .then((setRes) => {
        if (!isMounted) return;
        setSettings(setRes as any);
      })
      .catch(() => {
        // Silently tolerate if secondary options lag
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleDelete = async (id: number) => {
    try {
      setIsDeleting(true);
      await apiRequest(`/employees/${id}`, { method: 'DELETE' });
      setToast({ type: 'success', message: 'Employee and user credentials deleted successfully.' });
      setEmployees((prev) => prev.filter((e) => e.id !== id));
      setDeleteConfirmId(null);
      if (viewEmployee?.id === id) setViewEmployee(null);
    } catch (err: any) {
      setToast({ type: 'error', message: err.message || 'Failed to delete employee' });
    } finally {
      setIsDeleting(false);
    }
  };

  // Filtered employees
  const filteredEmployees = employees.filter((emp) => {
    const fullName = `${emp.first_name} ${emp.last_name || ''}`.toLowerCase();
    const matchesSearch =
      !search ||
      fullName.includes(search.toLowerCase()) ||
      emp.employee_id.toLowerCase().includes(search.toLowerCase()) ||
      emp.email.toLowerCase().includes(search.toLowerCase()) ||
      (emp.phone && emp.phone.includes(search));

    const matchesCompany = !filterCompany || emp.company_id?.toString() === filterCompany;
    const matchesDept = !filterDepartment || emp.department_id?.toString() === filterDepartment;
    const matchesStatus = !filterStatus || emp.status === filterStatus;

    return matchesSearch && matchesCompany && matchesDept && matchesStatus;
  });

  return (
    <ProtectedRoute>
      <AppLayout
        title="Workforce Directory"
        subtitle="Manage employees, statutory benefits, and organizational assignments"
        action={
          <Link
            href="/employees/onboard"
            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" /></svg>
            Onboard Employee
          </Link>
        }
      >
        {/* Toast */}
        {toast && (
          <div className="fixed top-4 right-4 z-50 animate-bounce">
            <div className={`px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center gap-3 text-white ${toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'}`}>
              <span>{toast.message}</span>
              <button onClick={() => setToast(null)} className="text-white/80 hover:text-white font-bold ml-2 cursor-pointer">×</button>
            </div>
          </div>
        )}

        {/* Content Container */}
        <div className="p-4 sm:p-6 lg:p-8 space-y-6">
          {/* Header Title & Metrics */}
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h2 className="text-xl font-bold text-slate-900 tracking-tight">Active Employee Roster</h2>
              <p className="text-xs sm:text-sm text-slate-500 mt-1">
                Search, view full profiles, monitor statutory allowances, and manage employee records across branches.
              </p>
            </div>
            <div className="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-600 font-medium self-start md:self-auto shadow-xs">
              <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              Total Records: <span className="font-bold text-slate-900">{employees.length}</span>
            </div>
          </div>

          {/* Search & Filter Toolbar */}
          <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div className="relative">
              <input
                type="text"
                placeholder="Search by ID, name, email..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
              />
              <svg className="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>

            <div>
              <select
                value={filterCompany}
                onChange={(e) => setFilterCompany(e.target.value)}
                className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white"
              >
                <option value="">All Companies</option>
                {Array.isArray(settings?.companies) && settings.companies.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </div>

            <div>
              <select
                value={filterDepartment}
                onChange={(e) => setFilterDepartment(e.target.value)}
                className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white"
              >
                <option value="">All Departments</option>
                {Array.isArray(settings?.departments) && settings.departments.map((d) => (
                  <option key={d.id} value={d.id}>{d.name}</option>
                ))}
              </select>
            </div>

            <div>
              <select
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white"
              >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="probation">Probation</option>
                <option value="notice">Notice Period</option>
                <option value="terminated">Terminated</option>
              </select>
            </div>
          </div>

          {/* Directory Table / Content */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            {loading ? (
              <div className="py-20 flex flex-col items-center justify-center space-y-3">
                <div className="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                <p className="text-sm text-slate-500 font-medium">Loading employee records...</p>
              </div>
            ) : filteredEmployees.length === 0 ? (
              <div className="py-16 text-center space-y-3">
                <div className="w-12 h-12 mx-auto bg-slate-100 rounded-full flex items-center justify-center text-slate-400">
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <h3 className="text-base font-semibold text-slate-800">No employees found</h3>
                <p className="text-sm text-slate-500 max-w-sm mx-auto">
                  No records match your search criteria. Add a new team member to populate the roster.
                </p>
                <Link
                  href="/employees/onboard"
                  className="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition"
                >
                  + Onboard New Employee
                </Link>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold tracking-wider uppercase">
                      <th className="px-5 py-3.5">Employee</th>
                      <th className="px-5 py-3.5">Role & Company</th>
                      <th className="px-5 py-3.5">Attendance / Shift</th>
                      <th className="px-5 py-3.5">Gross Pay</th>
                      <th className="px-5 py-3.5">Status</th>
                      <th className="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 text-sm">
                    {filteredEmployees.map((emp) => (
                      <tr key={emp.id} className="hover:bg-slate-50/80 transition">
                        {/* Name & ID */}
                        <td className="px-5 py-4">
                          <div className="flex items-center gap-3">
                            <div className="w-9 h-9 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-xs uppercase shrink-0">
                              {emp.first_name?.[0] || 'E'}{(emp.last_name && emp.last_name[0]) || ''}
                            </div>
                            <div>
                              <div className="font-semibold text-slate-900 hover:text-blue-600 cursor-pointer" onClick={() => setViewEmployee(emp)}>
                                {emp.first_name} {emp.last_name || ''}
                              </div>
                              <div className="text-xs text-slate-500 flex items-center gap-1.5">
                                <span className="font-mono font-medium text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded text-[11px]">{emp.employee_id}</span>
                                <span>•</span>
                                <span>{emp.email}</span>
                              </div>
                            </div>
                          </div>
                        </td>

                        {/* Role & Company */}
                        <td className="px-5 py-4">
                          <div className="font-medium text-slate-800">{emp.designation?.name || '—'}</div>
                          <div className="text-xs text-slate-500">{emp.department?.name || 'No Dept'} • {emp.company?.name || 'Default Co.'}</div>
                        </td>

                        {/* Attendance & Shift */}
                        <td className="px-5 py-4">
                          <div className="flex items-center gap-1.5">
                            <span className={`px-2 py-0.5 text-[11px] font-semibold rounded ${emp.attendance_location_type === 'fixed' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-amber-50 text-amber-700 border border-amber-200'}`}>
                              {emp.attendance_location_type === 'fixed' ? 'Fixed Geofence' : 'Anywhere GPS'}
                            </span>
                          </div>
                          <div className="text-xs text-slate-500 mt-1">
                            {emp.shift?.name || 'Standard Shift'} {emp.location?.name || emp.location?.campus_name ? `(${emp.location.name || emp.location.campus_name})` : ''}
                          </div>
                        </td>

                        {/* Gross Pay */}
                        <td className="px-5 py-4 font-mono font-semibold text-slate-900">
                          ₹{Number(emp.gross_salary || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                          <div className="text-[11px] font-sans font-normal text-slate-500">
                            Net: ₹{Number(emp.net_salary || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                          </div>
                        </td>

                        {/* Status */}
                        <td className="px-5 py-4">
                          <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold capitalize ${
                            emp.status === 'active'
                              ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                              : emp.status === 'probation'
                              ? 'bg-blue-50 text-blue-700 border border-blue-200'
                              : emp.status === 'notice'
                              ? 'bg-amber-50 text-amber-700 border border-amber-200'
                              : 'bg-rose-50 text-rose-700 border border-rose-200'
                          }`}>
                            {emp.status}
                          </span>
                        </td>

                        {/* Actions */}
                        <td className="px-5 py-4 text-right">
                          <div className="flex items-center justify-end gap-1.5">
                            <button
                              onClick={() => setViewEmployee(emp)}
                              className="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition cursor-pointer"
                              title="View Full Profile"
                            >
                              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            </button>

                            <Link
                              href={`/employees/${emp.id}/edit`}
                              className="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition cursor-pointer"
                              title="Edit Employee Master Data"
                            >
                              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </Link>

                            <button
                              onClick={() => setDeleteConfirmId(emp.id)}
                              className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer"
                              title="Delete Employee"
                            >
                              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>

        {/* Delete Confirmation Modal */}
        {deleteConfirmId && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs animate-fade-in">
            <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
              <div className="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
              </div>
              <h3 className="text-lg font-bold text-slate-900 text-center">Delete Employee Record?</h3>
              <p className="text-sm text-slate-500 text-center mt-2">
                This action will permanently delete this employee record and remove their linked system login credentials. This cannot be undone.
              </p>
              <div className="flex items-center justify-end gap-3 mt-6">
                <button
                  type="button"
                  onClick={() => setDeleteConfirmId(null)}
                  className="px-4 py-2 border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-sm font-semibold transition cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => handleDelete(deleteConfirmId)}
                  disabled={isDeleting}
                  className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition disabled:opacity-50 cursor-pointer"
                >
                  {isDeleting ? 'Deleting...' : 'Confirm Delete'}
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Employee Details Drawer / Modal */}
        {viewEmployee && (
          <div className="fixed inset-0 z-50 flex justify-end bg-slate-900/40 backdrop-blur-xs">
            <div className="bg-white w-full max-w-2xl h-full shadow-2xl flex flex-col overflow-y-auto animate-slide-left">
              {/* Drawer Header */}
              <div className="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between z-10">
                <div>
                  <h3 className="text-lg font-bold text-slate-900">
                    {viewEmployee.first_name} {viewEmployee.last_name || ''}
                  </h3>
                  <p className="text-xs text-slate-500 font-mono mt-0.5">
                    ID: {viewEmployee.employee_id} • Type: {viewEmployee.usertype.toUpperCase()}
                  </p>
                </div>
                <button
                  onClick={() => setViewEmployee(null)}
                  className="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 cursor-pointer"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>

              {/* Drawer Body */}
              <div className="p-6 space-y-6 flex-1">
                {/* Quick Status Bar */}
                <div className="grid grid-cols-3 gap-3 p-4 bg-slate-50 rounded-xl border border-slate-200 text-center">
                  <div>
                    <div className="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Status</div>
                    <div className="text-sm font-bold text-slate-800 capitalize mt-0.5">{viewEmployee.status}</div>
                  </div>
                  <div>
                    <div className="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Join Date</div>
                    <div className="text-sm font-bold text-slate-800 mt-0.5">{viewEmployee.date_of_joining}</div>
                  </div>
                  <div>
                    <div className="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Employment</div>
                    <div className="text-sm font-bold text-slate-800 capitalize mt-0.5">{viewEmployee.employment_type}</div>
                  </div>
                </div>

                {/* Organization Assignment */}
                <div className="space-y-3">
                  <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">Organizational Details</h4>
                  <div className="grid grid-cols-2 gap-3 text-xs bg-white p-3.5 rounded-lg border border-slate-200">
                    <div>
                      <span className="text-slate-500">Company:</span>
                      <p className="font-semibold text-slate-800 mt-0.5">{viewEmployee.company?.name || '—'}</p>
                    </div>
                    <div>
                      <span className="text-slate-500">Department:</span>
                      <p className="font-semibold text-slate-800 mt-0.5">{viewEmployee.department?.name || '—'}</p>
                    </div>
                    <div>
                      <span className="text-slate-500">Designation:</span>
                      <p className="font-semibold text-slate-800 mt-0.5">{viewEmployee.designation?.name || '—'}</p>
                    </div>
                    <div>
                      <span className="text-slate-500">Shift & Location:</span>
                      <p className="font-semibold text-slate-800 mt-0.5">
                        {viewEmployee.shift?.name || 'Standard'} ({viewEmployee.attendance_location_type === 'fixed' ? 'Fixed Campus' : 'Random GPS'})
                      </p>
                    </div>
                  </div>
                </div>

                {/* Personal & Emergency */}
                <div className="space-y-3">
                  <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">Personal & Emergency Contact</h4>
                  <div className="grid grid-cols-2 gap-3 text-xs bg-white p-3.5 rounded-lg border border-slate-200">
                    <div>
                      <span className="text-slate-500">Email:</span>
                      <p className="font-semibold text-slate-800 mt-0.5">{viewEmployee.email}</p>
                    </div>
                    <div>
                      <span className="text-slate-500">Phone:</span>
                      <p className="font-semibold text-slate-800 mt-0.5">{viewEmployee.phone || '—'}</p>
                    </div>
                    <div>
                      <span className="text-slate-500">Date of Birth & Gender:</span>
                      <p className="font-semibold text-slate-800 mt-0.5">{viewEmployee.date_of_birth || '—'} ({viewEmployee.gender || '—'})</p>
                    </div>
                    <div>
                      <span className="text-slate-500">Blood Group:</span>
                      <p className="font-semibold text-slate-800 mt-0.5">{viewEmployee.blood_group || '—'}</p>
                    </div>
                    <div className="col-span-2 pt-2 border-t border-slate-100">
                      <span className="text-slate-500">Emergency Contact:</span>
                      <p className="font-semibold text-slate-800 mt-0.5">
                        {viewEmployee.emergency_contact_name || '—'} ({viewEmployee.emergency_contact_relation || '—'}) - {viewEmployee.emergency_contact_phone || '—'}
                      </p>
                    </div>
                  </div>
                </div>

                {/* Statutory & Bank Details */}
                <div className="space-y-3">
                  <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">Statutory & Bank Account</h4>
                  <div className="grid grid-cols-2 gap-3 text-xs bg-white p-3.5 rounded-lg border border-slate-200">
                    <div>
                      <span className="text-slate-500">PAN Number:</span>
                      <p className="font-mono font-semibold text-slate-800 mt-0.5">{viewEmployee.pan_number || '—'}</p>
                    </div>
                    <div>
                      <span className="text-slate-500">Aadhaar Number:</span>
                      <p className="font-mono font-semibold text-slate-800 mt-0.5">{viewEmployee.aadhaar_number || '—'}</p>
                    </div>
                    <div>
                      <span className="text-slate-500">Bank Name:</span>
                      <p className="font-semibold text-slate-800 mt-0.5">{viewEmployee.bank_name || '—'}</p>
                    </div>
                    <div>
                      <span className="text-slate-500">Account No. & IFSC:</span>
                      <p className="font-mono font-semibold text-slate-800 mt-0.5">{viewEmployee.bank_account_number || '—'} ({viewEmployee.bank_ifsc_code || '—'})</p>
                    </div>
                  </div>
                </div>

                {/* Salary Breakdown Structure */}
                <div className="space-y-3">
                  <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">Compensation Breakdown (Monthly)</h4>
                  <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3 text-xs">
                    <div className="flex justify-between py-1 border-b border-slate-200 font-semibold">
                      <span>Basic Salary</span>
                      <span>₹{Number(viewEmployee.basic_salary).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                    </div>
                    <div className="grid grid-cols-2 gap-2 text-slate-600">
                      <div>DA (20%): ₹{Number(viewEmployee.da_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
                      <div>HRA (60%): ₹{Number(viewEmployee.hra_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
                      <div>CA (20%): ₹{Number(viewEmployee.ca_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
                      <div>EA (8%): ₹{Number(viewEmployee.ea_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
                      <div>SA (20%): ₹{Number(viewEmployee.sa_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
                    </div>
                    <div className="flex justify-between py-1.5 border-t border-b border-slate-200 font-bold text-slate-900">
                      <span>Gross Salary</span>
                      <span>₹{Number(viewEmployee.gross_salary).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                    </div>
                    <div className="grid grid-cols-2 gap-2 text-slate-600">
                      <div>PF Deduction: ₹{Number(viewEmployee.employee_pf).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
                      <div>ESIC (Employee): ₹{Number(viewEmployee.employee_esic).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
                      <div>Income Tax (TDS): ₹{Number(viewEmployee.monthly_tax).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
                      <div>Total Deductions: ₹{Number(viewEmployee.total_deductions).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
                    </div>
                    <div className="flex justify-between pt-2 border-t border-slate-200 font-bold text-blue-700 text-sm">
                      <span>Net Take-Home Salary:</span>
                      <span>₹{Number(viewEmployee.net_salary).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                    </div>
                    <div className="flex justify-between text-slate-500 font-medium text-xs">
                      <span>Cost to Company (CTC):</span>
                      <span>₹{Number(viewEmployee.ctc_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })} / mo</span>
                    </div>
                  </div>
                </div>

                {/* Education Qualifications */}
                {Array.isArray(viewEmployee.qualifications) && viewEmployee.qualifications.length > 0 && (
                  <div className="space-y-3">
                    <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">Educational Qualifications</h4>
                    <div className="space-y-2">
                      {viewEmployee.qualifications.map((q, idx) => (
                        <div key={idx} className="p-3 bg-white border border-slate-200 rounded-lg text-xs flex justify-between items-center">
                          <div>
                            <div className="font-semibold text-slate-800">{q.degree}</div>
                            <div className="text-slate-500">{q.institution} ({q.year_of_passing})</div>
                          </div>
                          {q.grade_or_percentage && <span className="font-mono font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded">{q.grade_or_percentage}</span>}
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              {/* Drawer Footer */}
              <div className="sticky bottom-0 bg-slate-50 border-t border-slate-200 p-4 flex items-center justify-between gap-3">
                <Link
                  href={`/employees/${viewEmployee.id}/edit`}
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition flex items-center gap-1.5"
                >
                  <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                  Edit Master Profile
                </Link>

                <button
                  onClick={() => setViewEmployee(null)}
                  className="px-4 py-2 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg shadow-xs transition cursor-pointer"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        )}
      </AppLayout>
    </ProtectedRoute>
  );
}
