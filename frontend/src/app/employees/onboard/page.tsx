'use client';

import React, { useEffect, useState } from 'react';
import ProtectedRoute from '@/components/ProtectedRoute';
import AppLayout from '@/components/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { apiRequest } from '@/lib/api';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import SalaryCalculatorWidget from '@/components/SalaryCalculatorWidget';
import { Qualification } from '@/types/employee';
import { getCachedSettings, fetchSettingsOverview } from '@/lib/settingsCache';
import {
  Users,
  LayoutDashboard,
  CalendarCheck,
  CreditCard,
  Building2,
  Settings,
  LogOut,
  Shield,
  Bell,
  Briefcase,
  Menu,
  X,
  ArrowLeft,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Plus,
  Trash2,
  Building,
  Award,
  Clock,
  MapPin,
  User,
  GraduationCap,
  Landmark,
  FileText,
  PhoneCall,
  Save,
} from 'lucide-react';

export default function EmployeeOnboardPage() {
  const { user, logout, token } = useAuth();
  const router = useRouter();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  // Reference Master Options from Settings (Instant Hydration)
  const [loadingOptions, setLoadingOptions] = useState(false);
  const [companies, setCompanies] = useState<any[]>(() => getCachedSettings()?.companies || []);
  const [departments, setDepartments] = useState<any[]>(() => getCachedSettings()?.departments || []);
  const [designations, setDesignations] = useState<any[]>(() => getCachedSettings()?.designations || []);
  const [shifts, setShifts] = useState<any[]>(() => getCachedSettings()?.shifts || []);
  const [locations, setLocations] = useState<any[]>(() => getCachedSettings()?.locations || []);
  const [existingEmployees, setExistingEmployees] = useState<any[]>([]);

  // Form State
  const [saving, setSaving] = useState(false);
  const [generatingId, setGeneratingId] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const getFreshFormData = () => ({
    // Primary Onboarding & Roles
    company_id: '',
    department_id: '',
    designation_id: '',
    shift_id: '',
    reporting_manager_id: '',
    attendance_location_type: 'fixed',
    location_id: '',
    employee_id: '',
    usertype: 'user', // user or admin
    date_of_joining: '',
    employment_type: 'Full-Time',
    probation_duration: '3 Months',

    // Personal Details
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    date_of_birth: '',
    gender: 'Male',
    marital_status: 'Single',
    blood_group: '',
    current_address: '',
    permanent_address: '',

    // Emergency Contact
    emergency_contact_name: '',
    emergency_contact_relation: '',
    emergency_contact_phone: '',

    // Statutory & Identity Details
    pan_number: '',
    aadhaar_number: '',
    uan_number: '',
    esic_number: '',
    passport_number: '',

    // Bank Account Details
    bank_name: '',
    bank_account_number: '',
    bank_ifsc_code: '',
    bank_branch: '',

    // Compensation
    basic_salary: 0,
    monthly_tax: 0,
    enable_epf: true,

    // Initial Login Password for Employee
    portal_password: 'Welcome@123',
  });

  // Form Fields
  const [formData, setFormData] = useState(getFreshFormData);

  // Dynamic Qualifications
  const [qualifications, setQualifications] = useState<Qualification[]>([
    { degree: '', institution: '', year_of_passing: '', grade_or_percentage: '' },
  ]);

  const navItems = [
    { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { name: 'Employees', href: '/employees', icon: Users, active: true },
    { name: 'Departments', href: '/dashboard', icon: Building2, badge: 'Soon' },
    { name: 'Attendance', href: '/dashboard', icon: CalendarCheck, badge: 'Soon' },
    { name: 'Payroll', href: '/dashboard', icon: CreditCard, badge: 'Soon' },
    { name: 'Settings', href: '/settings', icon: Settings },
  ];

  // Load Settings options & Auto-generate ID without blocking form display
  useEffect(() => {
    let isMounted = true;

    // Reset to clean, fresh state every time user enters the page
    setFormData(getFreshFormData());
    setQualifications([{ degree: '', institution: '', year_of_passing: '', grade_or_percentage: '' }]);
    setErrorMessage(null);
    setSuccessMessage(null);

    // Set today's date client-side
    const todayStr = new Date().toISOString().split('T')[0];
    setFormData((prev) => ({
      ...prev,
      date_of_joining: todayStr,
    }));

    // Auto-generate fresh ID
    apiRequest<{ employee_id: string }>('/employees/generate-id', {}, token || undefined)
      .then((idRes) => {
        if (!isMounted) return;
        if (idRes?.employee_id) {
          setFormData((prev) => ({ ...prev, employee_id: idRes.employee_id }));
        }
      })
      .catch((err) => console.warn('Could not auto-generate employee ID:', err));

    // Fetch existing employee list for Reporting Manager dropdown
    apiRequest<{ data: any[] }>('/employees?per_page=100', {}, token || undefined)
      .then((empRes) => {
        if (!isMounted) return;
        setExistingEmployees(empRes.data || []);
      })
      .catch((err) => console.warn('Could not load manager list:', err));

    // Master settings in background with caching and deduplication
    fetchSettingsOverview()
      .then((overviewRes) => {
        if (!isMounted) return;
        const comps = overviewRes.companies || [];
        setCompanies(comps);
        setDepartments(overviewRes.departments || []);
        setDesignations(overviewRes.designations || []);
        setShifts(overviewRes.shifts || []);
        setLocations(overviewRes.locations || []);

        setFormData((prev) => ({
          ...prev,
          company_id: prev.company_id || comps[0]?.id?.toString() || '',
          location_id: prev.location_id || overviewRes.locations?.[0]?.id?.toString() || '',
          shift_id: prev.shift_id || overviewRes.shifts?.[0]?.id?.toString() || '',
        }));
      })
      .catch((err) => {
        if (err instanceof Error) setErrorMessage(err.message);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleFieldChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleRegenerateId = async () => {
    if (!token) return;
    try {
      setGeneratingId(true);
      const res = await apiRequest<{ employee_id: string }>('/employees/generate-id', {}, token);
      setFormData((prev) => ({ ...prev, employee_id: res.employee_id }));
    } catch (err) {
      console.error(err);
    } finally {
      setGeneratingId(false);
    }
  };

  // Add / Remove Qualifications
  const handleAddQualification = () => {
    setQualifications((prev) => [
      ...prev,
      { degree: '', institution: '', year_of_passing: '', grade_or_percentage: '' },
    ]);
  };

  const handleRemoveQualification = (index: number) => {
    setQualifications((prev) => prev.filter((_, i) => i !== index));
  };

  const handleQualificationChange = (index: number, field: keyof Qualification, val: string) => {
    setQualifications((prev) => {
      const copy = [...prev];
      copy[index] = { ...copy[index], [field]: val };
      return copy;
    });
  };

  // Submit Onboarding
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return;
    setSaving(true);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      const payload = {
        ...formData,
        company_id: Number(formData.company_id),
        department_id: formData.department_id ? Number(formData.department_id) : null,
        designation_id: formData.designation_id ? Number(formData.designation_id) : null,
        shift_id: formData.shift_id ? Number(formData.shift_id) : null,
        reporting_manager_id: formData.reporting_manager_id ? Number(formData.reporting_manager_id) : null,
        location_id:
          formData.attendance_location_type === 'fixed' && formData.location_id
            ? Number(formData.location_id)
            : null,
        basic_salary: Number(formData.basic_salary),
        monthly_tax: Number(formData.monthly_tax),
        enable_epf: Boolean(formData.enable_epf),
        qualifications: qualifications.filter((q) => q.degree.trim() !== ''),
      };

      const res = await apiRequest<{ message: string }>('/employees', {
        method: 'POST',
        body: JSON.stringify(payload),
      }, token);

      setSuccessMessage(res.message);
      setTimeout(() => {
        router.push('/employees');
      }, 1500);
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMessage(err.message);
      } else {
        setErrorMessage('Failed to onboard employee.');
      }
    } finally {
      setSaving(false);
    }
  };

  return (
    <ProtectedRoute>
      <AppLayout
        title={
          <div className="flex items-center gap-2 text-xs font-medium text-slate-500">
            <Link href="/employees" className="hover:text-blue-600 transition flex items-center gap-1">
              <ArrowLeft className="h-3.5 w-3.5" />
              Employees
            </Link>
            <span>/</span>
            <span className="text-slate-900 font-semibold">New Employee Onboarding</span>
          </div>
        }
        action={
          <Link
            href="/employees"
            className="rounded-xl border border-slate-200 px-3.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </Link>
        }
      >
        <div className="p-4 sm:p-6 lg:p-8 bg-slate-50">
          <div className="max-w-5xl mx-auto space-y-6">
            {/* Top Banner */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <h2 className="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                  Employee Onboarding Form
                </h2>
                <p className="mt-1 text-xs sm:text-sm text-slate-500">
                  Register a new staff member with corporate hierarchy, geofenced attendance, and statutory payroll.
                </p>
              </div>
            </div>

              {/* Status alerts */}
              {successMessage && (
                <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs sm:text-sm text-emerald-800 shadow-xs">
                  <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600" />
                  <span>{successMessage} Redirecting to employee directory...</span>
                </div>
              )}

              {errorMessage && (
                <div className="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-800 shadow-xs">
                  <AlertCircle className="h-5 w-5 shrink-0 text-red-600" />
                  <span>{errorMessage}</span>
                </div>
              )}

              <form onSubmit={handleSubmit} autoComplete="off" className="space-y-6">
                  {/* SECTION 1: EMPLOYMENT & ORGANIZATION */}
                  <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                    <div className="flex items-center gap-2 pb-4 border-b border-slate-100">
                      <div className="rounded-lg bg-blue-50 p-2 text-blue-600">
                        <Building className="h-4 w-4" />
                      </div>
                      <h3 className="text-sm font-bold uppercase tracking-wider text-slate-800">
                        1. Corporate & Employment Assignment
                      </h3>
                    </div>

                    <div className="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                      {/* Company Dropdown */}
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Company Entity *
                        </label>
                        <select
                          required
                          name="company_id"
                          value={formData.company_id}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        >
                          <option value="">Select Company</option>
                          {Array.isArray(companies) && companies.map((c) => (
                            <option key={c.id} value={c.id}>
                              {c.name} {c.is_primary ? '(Primary)' : ''}
                            </option>
                          ))}
                        </select>
                      </div>

                      {/* User Type (User / Admin) */}
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Portal User Type *
                        </label>
                        <select
                          required
                          name="usertype"
                          value={formData.usertype}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none font-semibold text-blue-700"
                        >
                          <option value="user">User (Standard Employee Self-Service)</option>
                          <option value="admin">Admin (HR / Administrative Access)</option>
                        </select>
                      </div>

                      {/* Employee ID (Auto Generated with manual override option) */}
                      <div>
                        <div className="flex items-center justify-between">
                          <label className="block text-xs font-semibold uppercase text-slate-700">
                            Employee ID *
                          </label>
                          <button
                            type="button"
                            onClick={handleRegenerateId}
                            disabled={generatingId}
                            className="text-[11px] font-semibold text-blue-600 hover:underline"
                          >
                            {generatingId ? 'Generating...' : 'Auto-Generate'}
                          </button>
                        </div>
                        <input
                          type="text"
                          required
                          name="employee_id"
                          value={formData.employee_id}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono font-bold text-slate-900 focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      {/* Department Dropdown */}
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Department
                        </label>
                        <select
                          name="department_id"
                          value={formData.department_id}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        >
                          <option value="">Select Department</option>
                          {Array.isArray(departments) && departments.map((d) => (
                            <option key={d.id} value={d.id}>
                              {d.name} {d.code ? `[${d.code}]` : ''}
                            </option>
                          ))}
                        </select>
                      </div>

                      {/* Designation Dropdown */}
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Designation / Job Title
                        </label>
                        <select
                          name="designation_id"
                          value={formData.designation_id}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        >
                          <option value="">Select Designation</option>
                          {Array.isArray(designations) && designations.map((des) => (
                            <option key={des.id} value={des.id}>
                              {des.name}
                            </option>
                          ))}
                        </select>
                      </div>

                      {/* Shift Dropdown */}
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Assigned Shift
                        </label>
                        <select
                          name="shift_id"
                          value={formData.shift_id}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        >
                          <option value="">Select Operational Shift</option>
                          {Array.isArray(shifts) && shifts.map((s) => (
                            <option key={s.id} value={s.id}>
                              {s.name} ({s.start_time.slice(0, 5)} - {s.end_time.slice(0, 5)})
                            </option>
                          ))}
                        </select>
                      </div>

                      {/* Date of Joining */}
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Date of Joining *
                        </label>
                        <input
                          type="date"
                          required
                          name="date_of_joining"
                          value={formData.date_of_joining}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      {/* Employment Type */}
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Employment Type *
                        </label>
                        <select
                          required
                          name="employment_type"
                          value={formData.employment_type}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        >
                          <option value="Full-Time">Full-Time (Permanent)</option>
                          <option value="Part-Time">Part-Time</option>
                          <option value="Contract">Contractual</option>
                          <option value="Probation">Probationary</option>
                          <option value="Internship">Internship</option>
                        </select>
                      </div>

                      {/* Probation Duration Dropdown */}
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Probation Duration *
                        </label>
                        <select
                          required
                          name="probation_duration"
                          value={formData.probation_duration}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        >
                          <option value="No Probation">No Probation (Direct Confirmed)</option>
                          <option value="1 Month">1 Month</option>
                          <option value="3 Months">3 Months</option>
                          <option value="6 Months">6 Months</option>
                          <option value="1 Year">1 Year</option>
                        </select>
                      </div>

                      {/* Reporting Manager Dropdown */}
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Reporting Manager
                        </label>
                        <select
                          name="reporting_manager_id"
                          value={formData.reporting_manager_id}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        >
                          <option value="">Select Reporting Manager (None / Self)</option>
                          {Array.isArray(existingEmployees) && existingEmployees.map((emp) => (
                            <option key={emp.id} value={emp.id}>
                              {emp.first_name} {emp.last_name || ''} ({emp.employee_id}) {emp.designation?.name ? `– ${emp.designation.name}` : ''}
                            </option>
                          ))}
                        </select>
                      </div>
                    </div>

                    {/* Attendance Location Type: Fixed vs Random */}
                    <div className="mt-5 pt-4 border-t border-slate-100">
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                          <label className="block text-xs font-semibold uppercase text-slate-700">
                            Attendance Location Type *
                          </label>
                          <select
                            required
                            name="attendance_location_type"
                            value={formData.attendance_location_type}
                            onChange={handleFieldChange}
                            className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none font-semibold text-slate-800"
                          >
                            <option value="fixed">Fixed (Restricted to specific GPS Geofence)</option>
                            <option value="random">Random / Remote (Punch from anywhere / Field staff)</option>
                          </select>
                        </div>

                        {formData.attendance_location_type === 'fixed' && (
                          <div>
                            <label className="block text-xs font-semibold uppercase text-slate-700">
                              Designated Geofenced Location *
                            </label>
                            <select
                              required
                              name="location_id"
                              value={formData.location_id}
                              onChange={handleFieldChange}
                              className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                            >
                              <option value="">Select Office Campus</option>
                              {Array.isArray(locations) && locations.map((loc) => (
                                <option key={loc.id} value={loc.id}>
                                  {loc.name} - {loc.city} ({loc.radius_meters}m geofence)
                                </option>
                              ))}
                            </select>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>

                  {/* SECTION 2: PERSONAL DETAILS */}
                  <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                    <div className="flex items-center gap-2 pb-4 border-b border-slate-100">
                      <div className="rounded-lg bg-blue-50 p-2 text-blue-600">
                        <User className="h-4 w-4" />
                      </div>
                      <h3 className="text-sm font-bold uppercase tracking-wider text-slate-800">
                        2. Personal Information & Portal Credentials
                      </h3>
                    </div>

                    <div className="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          First Name *
                        </label>
                        <input
                          type="text"
                          required
                          name="first_name"
                          value={formData.first_name}
                          onChange={handleFieldChange}
                          placeholder="e.g. John"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Last Name
                        </label>
                        <input
                          type="text"
                          name="last_name"
                          value={formData.last_name}
                          onChange={handleFieldChange}
                          placeholder="e.g. Doe"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Email (Login Username) *
                        </label>
                        <input
                          type="email"
                          required
                          name="email"
                          value={formData.email}
                          onChange={handleFieldChange}
                          placeholder="john.doe@company.com"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Mobile Phone
                        </label>
                        <input
                          type="text"
                          name="phone"
                          value={formData.phone}
                          onChange={handleFieldChange}
                          placeholder="+91 9876543210"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Date of Birth
                        </label>
                        <input
                          type="date"
                          name="date_of_birth"
                          value={formData.date_of_birth}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Gender
                        </label>
                        <select
                          name="gender"
                          value={formData.gender}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        >
                          <option value="Male">Male</option>
                          <option value="Female">Female</option>
                          <option value="Other">Other</option>
                        </select>
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Marital Status
                        </label>
                        <select
                          name="marital_status"
                          value={formData.marital_status}
                          onChange={handleFieldChange}
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        >
                          <option value="Single">Single</option>
                          <option value="Married">Married</option>
                          <option value="Divorced">Divorced</option>
                          <option value="Widowed">Widowed</option>
                        </select>
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Blood Group
                        </label>
                        <input
                          type="text"
                          name="blood_group"
                          value={formData.blood_group}
                          onChange={handleFieldChange}
                          placeholder="e.g. O+, B+, A+"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Initial Portal Password
                        </label>
                        <input
                          type="text"
                          name="portal_password"
                          value={formData.portal_password}
                          onChange={handleFieldChange}
                          placeholder="Welcome@123"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>
                    </div>

                    <div className="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Current Address
                        </label>
                        <textarea
                          rows={2}
                          name="current_address"
                          value={formData.current_address}
                          onChange={handleFieldChange}
                          placeholder="Current residential street and flat details"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Permanent Address
                        </label>
                        <textarea
                          rows={2}
                          name="permanent_address"
                          value={formData.permanent_address}
                          onChange={handleFieldChange}
                          placeholder="Permanent domicile address"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>
                    </div>
                  </div>

                  {/* SECTION 3: EMERGENCY CONTACT */}
                  <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                    <div className="flex items-center gap-2 pb-4 border-b border-slate-100">
                      <div className="rounded-lg bg-red-50 p-2 text-red-600">
                        <PhoneCall className="h-4 w-4" />
                      </div>
                      <h3 className="text-sm font-bold uppercase tracking-wider text-slate-800">
                        3. Emergency Contact Details
                      </h3>
                    </div>

                    <div className="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Contact Name
                        </label>
                        <input
                          type="text"
                          name="emergency_contact_name"
                          value={formData.emergency_contact_name}
                          onChange={handleFieldChange}
                          placeholder="e.g. Sarah Doe"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Relationship
                        </label>
                        <input
                          type="text"
                          name="emergency_contact_relation"
                          value={formData.emergency_contact_relation}
                          onChange={handleFieldChange}
                          placeholder="e.g. Spouse / Parent / Sibling"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Emergency Phone
                        </label>
                        <input
                          type="text"
                          name="emergency_contact_phone"
                          value={formData.emergency_contact_phone}
                          onChange={handleFieldChange}
                          placeholder="+91 9811223344"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>
                    </div>
                  </div>

                  {/* SECTION 4: STATUTORY & IDENTITY DETAILS */}
                  <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                    <div className="flex items-center gap-2 pb-4 border-b border-slate-100">
                      <div className="rounded-lg bg-indigo-50 p-2 text-indigo-600">
                        <FileText className="h-4 w-4" />
                      </div>
                      <h3 className="text-sm font-bold uppercase tracking-wider text-slate-800">
                        4. Statutory & Identity Credentials
                      </h3>
                    </div>

                    <div className="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          PAN Number
                        </label>
                        <input
                          type="text"
                          name="pan_number"
                          value={formData.pan_number}
                          onChange={handleFieldChange}
                          placeholder="ABCDE1234F"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono uppercase focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Aadhaar / National ID
                        </label>
                        <input
                          type="text"
                          name="aadhaar_number"
                          value={formData.aadhaar_number}
                          onChange={handleFieldChange}
                          placeholder="1234 5678 9012"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          UAN Number (PF)
                        </label>
                        <input
                          type="text"
                          name="uan_number"
                          value={formData.uan_number}
                          onChange={handleFieldChange}
                          placeholder="100987654321"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          ESIC Insurance Number
                        </label>
                        <input
                          type="text"
                          name="esic_number"
                          value={formData.esic_number}
                          onChange={handleFieldChange}
                          placeholder="3100987654321"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Passport Number
                        </label>
                        <input
                          type="text"
                          name="passport_number"
                          value={formData.passport_number}
                          onChange={handleFieldChange}
                          placeholder="Z1234567"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono uppercase focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>
                    </div>
                  </div>

                  {/* SECTION 5: BANK ACCOUNT DETAILS */}
                  <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                    <div className="flex items-center gap-2 pb-4 border-b border-slate-100">
                      <div className="rounded-lg bg-emerald-50 p-2 text-emerald-600">
                        <Landmark className="h-4 w-4" />
                      </div>
                      <h3 className="text-sm font-bold uppercase tracking-wider text-slate-800">
                        5. Bank Account & Payroll Disbursement
                      </h3>
                    </div>

                    <div className="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Bank Name
                        </label>
                        <input
                          type="text"
                          name="bank_name"
                          value={formData.bank_name}
                          onChange={handleFieldChange}
                          placeholder="e.g. HDFC Bank, ICICI"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Account Number
                        </label>
                        <input
                          type="text"
                          name="bank_account_number"
                          value={formData.bank_account_number}
                          onChange={handleFieldChange}
                          placeholder="50100012345678"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          IFSC / Routing Code
                        </label>
                        <input
                          type="text"
                          name="bank_ifsc_code"
                          value={formData.bank_ifsc_code}
                          onChange={handleFieldChange}
                          placeholder="HDFC0001234"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono uppercase focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">
                          Branch Name
                        </label>
                        <input
                          type="text"
                          name="bank_branch"
                          value={formData.bank_branch}
                          onChange={handleFieldChange}
                          placeholder="Downtown Central Branch"
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                        />
                      </div>
                    </div>
                  </div>

                  {/* SECTION 6: EDUCATIONAL QUALIFICATIONS */}
                  <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                    <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                      <div className="flex items-center gap-2">
                        <div className="rounded-lg bg-purple-50 p-2 text-purple-600">
                          <GraduationCap className="h-4 w-4" />
                        </div>
                        <h3 className="text-sm font-bold uppercase tracking-wider text-slate-800">
                          6. Educational Qualifications
                        </h3>
                      </div>
                      <button
                        type="button"
                        onClick={handleAddQualification}
                        className="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-800"
                      >
                        <Plus className="h-3.5 w-3.5" />
                        Add Qualification
                      </button>
                    </div>

                    <div className="mt-5 space-y-3">
                      {Array.isArray(qualifications) && qualifications.map((q, idx) => (
                        <div
                          key={idx}
                          className="grid grid-cols-1 sm:grid-cols-4 gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 items-end"
                        >
                          <div>
                            <label className="block text-[11px] font-semibold uppercase text-slate-600">
                              Degree / Certificate
                            </label>
                            <input
                              type="text"
                              value={q.degree}
                              onChange={(e) => handleQualificationChange(idx, 'degree', e.target.value)}
                              placeholder="e.g. B.Tech Computer Science"
                              className="mt-1 block w-full rounded-lg border border-slate-300 bg-white py-1.5 px-3 text-xs focus:border-blue-600 focus:outline-none"
                            />
                          </div>
                          <div>
                            <label className="block text-[11px] font-semibold uppercase text-slate-600">
                              University / College
                            </label>
                            <input
                              type="text"
                              value={q.institution}
                              onChange={(e) => handleQualificationChange(idx, 'institution', e.target.value)}
                              placeholder="e.g. National Institute of Tech"
                              className="mt-1 block w-full rounded-lg border border-slate-300 bg-white py-1.5 px-3 text-xs focus:border-blue-600 focus:outline-none"
                            />
                          </div>
                          <div className="grid grid-cols-2 gap-2">
                            <div>
                              <label className="block text-[11px] font-semibold uppercase text-slate-600">
                                Year
                              </label>
                              <input
                                type="text"
                                value={q.year_of_passing}
                                onChange={(e) => handleQualificationChange(idx, 'year_of_passing', e.target.value)}
                                placeholder="2022"
                                className="mt-1 block w-full rounded-lg border border-slate-300 bg-white py-1.5 px-3 text-xs focus:border-blue-600 focus:outline-none font-mono"
                              />
                            </div>
                            <div>
                              <label className="block text-[11px] font-semibold uppercase text-slate-600">
                                Grade / %
                              </label>
                              <input
                                type="text"
                                value={q.grade_or_percentage || ''}
                                onChange={(e) => handleQualificationChange(idx, 'grade_or_percentage', e.target.value)}
                                placeholder="8.5 CGPA"
                                className="mt-1 block w-full rounded-lg border border-slate-300 bg-white py-1.5 px-3 text-xs focus:border-blue-600 focus:outline-none"
                              />
                            </div>
                          </div>
                          <div className="flex justify-end">
                            {qualifications.length > 1 && (
                              <button
                                type="button"
                                onClick={() => handleRemoveQualification(idx)}
                                className="p-2 text-slate-400 hover:text-red-600 transition"
                                title="Remove row"
                              >
                                <Trash2 className="h-4 w-4" />
                              </button>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* SECTION 7: SALARY CALCULATION WIDGET (Image Spec) */}
                  <SalaryCalculatorWidget
                    basicSalary={formData.basic_salary}
                    monthlyTax={formData.monthly_tax}
                    enableEpf={formData.enable_epf}
                    currencySymbol="₹"
                    onBasicChange={(val) => setFormData((prev) => ({ ...prev, basic_salary: val }))}
                    onTaxChange={(val) => setFormData((prev) => ({ ...prev, monthly_tax: val }))}
                    onEpfToggle={(val) => setFormData((prev) => ({ ...prev, enable_epf: val }))}
                  />

                  {/* Submit Button Bar */}
                  <div className="pt-4 flex items-center justify-end gap-3">
                    <Link
                      href="/employees"
                      className="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition shadow-xs"
                    >
                      Cancel
                    </Link>
                    <button
                      type="submit"
                      disabled={saving}
                      className="flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:opacity-70"
                    >
                      {saving ? (
                        <>
                          <Loader2 className="h-4 w-4 animate-spin" />
                          Onboarding Employee...
                        </>
                      ) : (
                        <>
                          <Save className="h-4 w-4" />
                          Complete Employee Onboarding
                        </>
                      )}
                    </button>
                  </div>
                </form>
            </div>
          </div>
      </AppLayout>
    </ProtectedRoute>
  );
}
