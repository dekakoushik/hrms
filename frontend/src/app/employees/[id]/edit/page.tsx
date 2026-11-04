'use client';

import React, { useEffect, useState, Suspense } from 'react';
import ProtectedRoute from '@/components/ProtectedRoute';
import AppLayout from '@/components/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { apiRequest } from '@/lib/api';
import Link from 'next/link';
import { useRouter, useParams } from 'next/navigation';
import SalaryCalculatorWidget from '@/components/SalaryCalculatorWidget';
import { Qualification, Employee } from '@/types/employee';
import { getCachedSettings, fetchSettingsOverview } from '@/lib/settingsCache';
import {
  ArrowLeft,
  CheckCircle2,
  AlertCircle,
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
  Shield,
  Loader2,
} from 'lucide-react';

function EmployeeEditContent() {
  const { user, token } = useAuth();
  const router = useRouter();
  const params = useParams();
  const employeeId = params?.id as string;

  // Master Reference Options
  const [companies, setCompanies] = useState<any[]>(() => getCachedSettings()?.companies || []);
  const [departments, setDepartments] = useState<any[]>(() => getCachedSettings()?.departments || []);
  const [designations, setDesignations] = useState<any[]>(() => getCachedSettings()?.designations || []);
  const [shifts, setShifts] = useState<any[]>(() => getCachedSettings()?.shifts || []);
  const [locations, setLocations] = useState<any[]>(() => getCachedSettings()?.locations || []);
  const [existingEmployees, setExistingEmployees] = useState<any[]>([]);

  // State
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  // Form Data
  const [formData, setFormData] = useState({
    // Employment Details
    company_id: '',
    department_id: '',
    designation_id: '',
    shift_id: '',
    reporting_manager_id: '',
    attendance_location_type: 'fixed',
    location_id: '',
    employee_id: '',
    usertype: 'user',
    status: 'active',
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

    // Optional Password reset
    portal_password: '',
  });

  const [qualifications, setQualifications] = useState<Qualification[]>([
    { degree: '', institution: '', year_of_passing: '', grade_or_percentage: '' },
  ]);

  // Load Employee Details & Master Settings
  useEffect(() => {
    let isMounted = true;

    async function loadData() {
      try {
        setLoading(true);

        // Fetch Master Settings
        fetchSettingsOverview().then((overviewRes) => {
          if (!isMounted) return;
          setCompanies(overviewRes.companies || []);
          setDepartments(overviewRes.departments || []);
          setDesignations(overviewRes.designations || []);
          setShifts(overviewRes.shifts || []);
          setLocations(overviewRes.locations || []);
        });

        // Fetch Employees for manager dropdown
        apiRequest<{ data: any[] }>('/employees?per_page=100', {}, token || undefined).then((empRes) => {
          if (!isMounted) return;
          setExistingEmployees(empRes.data || []);
        });

        // Fetch Current Employee Record
        const responseData = await apiRequest<{ employee?: Employee } | Employee>(`/employees/${employeeId}`, {}, token || undefined);
        if (!isMounted) return;

        const empData: Employee = (responseData as any)?.employee || responseData;

        setFormData({
          company_id: empData.company_id ? String(empData.company_id) : '',
          department_id: empData.department_id ? String(empData.department_id) : '',
          designation_id: empData.designation_id ? String(empData.designation_id) : '',
          shift_id: empData.shift_id ? String(empData.shift_id) : '',
          reporting_manager_id: empData.reporting_manager_id ? String(empData.reporting_manager_id) : '',
          attendance_location_type: empData.attendance_location_type || 'fixed',
          location_id: empData.location_id ? String(empData.location_id) : '',
          employee_id: empData.employee_id || '',
          usertype: empData.usertype || 'user',
          status: empData.status || 'active',
          date_of_joining: empData.date_of_joining || '',
          employment_type: empData.employment_type || 'Full-Time',
          probation_duration: empData.probation_duration || '3 Months',

          first_name: empData.first_name || '',
          last_name: empData.last_name || '',
          email: empData.email || '',
          phone: empData.phone || '',
          date_of_birth: empData.date_of_birth || '',
          gender: empData.gender || 'Male',
          marital_status: empData.marital_status || 'Single',
          blood_group: empData.blood_group || '',
          current_address: empData.current_address || '',
          permanent_address: empData.permanent_address || '',

          emergency_contact_name: empData.emergency_contact_name || '',
          emergency_contact_relation: empData.emergency_contact_relation || '',
          emergency_contact_phone: empData.emergency_contact_phone || '',

          pan_number: empData.pan_number || '',
          aadhaar_number: empData.aadhaar_number || '',
          uan_number: empData.uan_number || '',
          esic_number: empData.esic_number || '',
          passport_number: empData.passport_number || '',

          bank_name: empData.bank_name || '',
          bank_account_number: empData.bank_account_number || '',
          bank_ifsc_code: empData.bank_ifsc_code || '',
          bank_branch: empData.bank_branch || '',

          basic_salary: empData.basic_salary ? Number(empData.basic_salary) : 0,
          monthly_tax: empData.monthly_tax ? Number(empData.monthly_tax) : 0,
          enable_epf: Boolean(empData.enable_epf),
          portal_password: '',
        });

        if (Array.isArray(empData.qualifications) && empData.qualifications.length > 0) {
          setQualifications(empData.qualifications);
        }
      } catch (err: any) {
        if (isMounted) setErrorMessage(err.message || 'Failed to load employee record.');
      } finally {
        if (isMounted) setLoading(false);
      }
    }

    if (employeeId) {
      loadData();
    }

    return () => {
      isMounted = false;
    };
  }, [employeeId, token]);

  const handleFieldChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleQualificationChange = (index: number, field: keyof Qualification, value: string) => {
    setQualifications((prev) => {
      const updated = [...prev];
      updated[index] = { ...updated[index], [field]: value };
      return updated;
    });
  };

  const addQualification = () => {
    setQualifications((prev) => [
      ...prev,
      { degree: '', institution: '', year_of_passing: '', grade_or_percentage: '' },
    ]);
  };

  const removeQualification = (index: number) => {
    if (qualifications.length > 1) {
      setQualifications((prev) => prev.filter((_, i) => i !== index));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);
    setSuccessMessage(null);
    setSaving(true);

    try {
      const payload: Record<string, any> = {
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

      if (!formData.portal_password) {
        delete payload.portal_password;
      }

      const res = await apiRequest<{ message: string }>(
        `/employees/${employeeId}`,
        {
          method: 'PUT',
          body: JSON.stringify(payload),
        },
        token || undefined
      );

      setSuccessMessage(res.message);
      setTimeout(() => {
        router.push('/employees');
      }, 1200);
    } catch (err: any) {
      setErrorMessage(err.message || 'Failed to update employee profile.');
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
            <span className="text-slate-900 font-semibold">Edit Employee Profile</span>
          </div>
        }
        action={
          <div className="flex items-center gap-2">
            <Link
              href="/employees"
              className="rounded-xl border border-slate-200 px-3.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
            >
              Cancel
            </Link>
          </div>
        }
      >
        <div className="p-4 sm:p-6 lg:p-8 bg-slate-50">
          <div className="max-w-5xl mx-auto space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <h2 className="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                  Update Employee Master Profile
                </h2>
                <p className="mt-1 text-xs sm:text-sm text-slate-500">
                  Superadmin mode: Modify corporate assignments, biometric shift rules, statutory tax, and credentials.
                </p>
              </div>
              <div className="flex items-center gap-2 bg-blue-50 border border-blue-200 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-semibold">
                <Shield className="w-3.5 h-3.5" />
                Superadmin Editing Mode
              </div>
            </div>

            {/* Status alerts */}
            {successMessage && (
              <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs sm:text-sm text-emerald-800 shadow-xs">
                <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600" />
                <span>{successMessage} Redirecting to workforce roster...</span>
              </div>
            )}

            {errorMessage && (
              <div className="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-800 shadow-xs">
                <AlertCircle className="h-5 w-5 shrink-0 text-red-600" />
                <span>{errorMessage}</span>
              </div>
            )}

            {loading ? (
              <div className="bg-white rounded-2xl border border-slate-200 p-16 text-center space-y-3">
                <Loader2 className="w-8 h-8 text-blue-600 animate-spin mx-auto" />
                <p className="text-sm text-slate-500 font-medium">Loading employee dossier...</p>
              </div>
            ) : (
              <form onSubmit={handleSubmit} autoComplete="off" className="space-y-6">
                {/* SECTION 1: EMPLOYMENT & ORGANIZATION */}
                <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                  <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                      <div className="rounded-lg bg-blue-50 p-2 text-blue-600">
                        <Building className="h-4 w-4" />
                      </div>
                      <h3 className="text-sm font-bold uppercase tracking-wider text-slate-800">
                        1. Corporate & Employment Assignment
                      </h3>
                    </div>

                    <div className="flex items-center gap-2">
                      <span className="text-xs font-semibold text-slate-500">Employment Status:</span>
                      <select
                        name="status"
                        value={formData.status}
                        onChange={handleFieldChange}
                        className="text-xs font-bold rounded-lg border border-slate-300 bg-slate-50 px-2.5 py-1 focus:bg-white focus:outline-none"
                      >
                        <option value="active">Active</option>
                        <option value="probation">Probation</option>
                        <option value="notice">Notice Period</option>
                        <option value="terminated">Terminated</option>
                        <option value="resigned">Resigned</option>
                      </select>
                    </div>
                  </div>

                  <div className="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {/* Company */}
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Company Entity *</label>
                      <select
                        required
                        name="company_id"
                        value={formData.company_id}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      >
                        <option value="">Select Company</option>
                        {Array.isArray(companies) &&
                          companies.map((c) => (
                            <option key={c.id} value={c.id}>
                              {c.name} {c.is_primary ? '(Primary)' : ''}
                            </option>
                          ))}
                      </select>
                    </div>

                    {/* Portal User Type */}
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Portal User Type *</label>
                      <select
                        required
                        name="usertype"
                        value={formData.usertype}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-semibold text-blue-700 focus:border-blue-600 focus:bg-white focus:outline-none"
                      >
                        <option value="user">User (Standard Employee Self-Service)</option>
                        <option value="admin">Admin (HR / Administrative Access)</option>
                      </select>
                    </div>

                    {/* Employee ID */}
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Employee ID *</label>
                      <input
                        type="text"
                        required
                        name="employee_id"
                        value={formData.employee_id}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono font-bold text-slate-900 focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    {/* Department */}
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Department</label>
                      <select
                        name="department_id"
                        value={formData.department_id}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      >
                        <option value="">Select Department</option>
                        {Array.isArray(departments) &&
                          departments.map((d) => (
                            <option key={d.id} value={d.id}>
                              {d.name} {d.code ? `[${d.code}]` : ''}
                            </option>
                          ))}
                      </select>
                    </div>

                    {/* Designation */}
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Designation / Job Title</label>
                      <select
                        name="designation_id"
                        value={formData.designation_id}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      >
                        <option value="">Select Designation</option>
                        {Array.isArray(designations) &&
                          designations.map((des) => (
                            <option key={des.id} value={des.id}>
                              {des.name}
                            </option>
                          ))}
                      </select>
                    </div>

                    {/* Shift */}
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Assigned Shift</label>
                      <select
                        name="shift_id"
                        value={formData.shift_id}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      >
                        <option value="">Select Operational Shift</option>
                        {Array.isArray(shifts) &&
                          shifts.map((s) => (
                            <option key={s.id} value={s.id}>
                              {s.name} ({s.start_time.slice(0, 5)} - {s.end_time.slice(0, 5)})
                            </option>
                          ))}
                      </select>
                    </div>

                    {/* Date of Joining */}
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Date of Joining *</label>
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
                      <label className="block text-xs font-semibold uppercase text-slate-700">Employment Type *</label>
                      <select
                        name="employment_type"
                        value={formData.employment_type}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      >
                        <option value="Full-Time">Full-Time (Permanent)</option>
                        <option value="Part-Time">Part-Time</option>
                        <option value="Contract">Contractual</option>
                        <option value="Internship">Internship</option>
                      </select>
                    </div>

                    {/* Probation Duration */}
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Probation Duration *</label>
                      <select
                        name="probation_duration"
                        value={formData.probation_duration}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      >
                        <option value="None">None (Direct Confirmed)</option>
                        <option value="1 Month">1 Month</option>
                        <option value="3 Months">3 Months</option>
                        <option value="6 Months">6 Months</option>
                      </select>
                    </div>

                    {/* Reporting Manager Dropdown */}
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Reporting Manager</label>
                      <select
                        name="reporting_manager_id"
                        value={formData.reporting_manager_id}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      >
                        <option value="">Select Reporting Manager (None / Self)</option>
                        {Array.isArray(existingEmployees) &&
                          existingEmployees
                            .filter((emp) => String(emp.id) !== String(employeeId))
                            .map((emp) => (
                              <option key={emp.id} value={emp.id}>
                                {emp.first_name} {emp.last_name || ''} ({emp.employee_id}){' '}
                                {emp.designation?.name ? `– ${emp.designation.name}` : ''}
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
                          className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-semibold text-slate-800 focus:border-blue-600 focus:bg-white focus:outline-none"
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
                            {Array.isArray(locations) &&
                              locations.map((loc) => (
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

                {/* SECTION 2: PERSONAL INFORMATION & PORTAL CREDENTIALS */}
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
                      <label className="block text-xs font-semibold uppercase text-slate-700">First Name *</label>
                      <input
                        type="text"
                        required
                        name="first_name"
                        value={formData.first_name}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Last Name</label>
                      <input
                        type="text"
                        name="last_name"
                        value={formData.last_name}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Email (Login Username) *</label>
                      <input
                        type="email"
                        required
                        name="email"
                        value={formData.email}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Phone Number</label>
                      <input
                        type="tel"
                        name="phone"
                        value={formData.phone}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Date of Birth</label>
                      <input
                        type="date"
                        name="date_of_birth"
                        value={formData.date_of_birth}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Gender</label>
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
                      <label className="block text-xs font-semibold uppercase text-slate-700">Marital Status</label>
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
                      <label className="block text-xs font-semibold uppercase text-slate-700">Blood Group</label>
                      <input
                        type="text"
                        name="blood_group"
                        value={formData.blood_group}
                        onChange={handleFieldChange}
                        placeholder="e.g. O+, B+, A-"
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Reset Portal Password</label>
                      <input
                        type="text"
                        name="portal_password"
                        value={formData.portal_password}
                        onChange={handleFieldChange}
                        placeholder="Leave blank to keep current"
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div className="sm:col-span-2">
                      <label className="block text-xs font-semibold uppercase text-slate-700">Current Address</label>
                      <textarea
                        rows={2}
                        name="current_address"
                        value={formData.current_address}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div className="sm:col-span-2 lg:col-span-1">
                      <label className="block text-xs font-semibold uppercase text-slate-700">Permanent Address</label>
                      <textarea
                        rows={2}
                        name="permanent_address"
                        value={formData.permanent_address}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>
                  </div>
                </div>

                {/* SECTION 3: EMERGENCY CONTACTS */}
                <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                  <div className="flex items-center gap-2 pb-4 border-b border-slate-100">
                    <div className="rounded-lg bg-blue-50 p-2 text-blue-600">
                      <PhoneCall className="h-4 w-4" />
                    </div>
                    <h3 className="text-sm font-bold uppercase tracking-wider text-slate-800">
                      3. Emergency Contacts
                    </h3>
                  </div>

                  <div className="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Contact Person Name</label>
                      <input
                        type="text"
                        name="emergency_contact_name"
                        value={formData.emergency_contact_name}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Relation</label>
                      <input
                        type="text"
                        name="emergency_contact_relation"
                        value={formData.emergency_contact_relation}
                        onChange={handleFieldChange}
                        placeholder="e.g. Spouse, Father, Guardian"
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Emergency Phone</label>
                      <input
                        type="tel"
                        name="emergency_contact_phone"
                        value={formData.emergency_contact_phone}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>
                  </div>
                </div>

                {/* SECTION 4: STATUTORY & BANKING */}
                <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                  <div className="flex items-center gap-2 pb-4 border-b border-slate-100">
                    <div className="rounded-lg bg-blue-50 p-2 text-blue-600">
                      <Landmark className="h-4 w-4" />
                    </div>
                    <h3 className="text-sm font-bold uppercase tracking-wider text-slate-800">
                      4. Statutory Identity & Direct Bank Deposit
                    </h3>
                  </div>

                  <div className="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">PAN Card</label>
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
                      <label className="block text-xs font-semibold uppercase text-slate-700">Aadhaar UID</label>
                      <input
                        type="text"
                        name="aadhaar_number"
                        value={formData.aadhaar_number}
                        onChange={handleFieldChange}
                        placeholder="12-digit UID"
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">EPF UAN</label>
                      <input
                        type="text"
                        name="uan_number"
                        value={formData.uan_number}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">ESIC Insurance No</label>
                      <input
                        type="text"
                        name="esic_number"
                        value={formData.esic_number}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Bank Name</label>
                      <input
                        type="text"
                        name="bank_name"
                        value={formData.bank_name}
                        onChange={handleFieldChange}
                        placeholder="e.g. HDFC Bank, ICICI Bank"
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Account Number</label>
                      <input
                        type="text"
                        name="bank_account_number"
                        value={formData.bank_account_number}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">IFSC Code</label>
                      <input
                        type="text"
                        name="bank_ifsc_code"
                        value={formData.bank_ifsc_code}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm font-mono uppercase focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Branch Name</label>
                      <input
                        type="text"
                        name="bank_branch"
                        value={formData.bank_branch}
                        onChange={handleFieldChange}
                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 px-3 text-sm focus:border-blue-600 focus:bg-white focus:outline-none"
                      />
                    </div>
                  </div>
                </div>

                {/* SECTION 5: QUALIFICATIONS */}
                <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                  <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                      <div className="rounded-lg bg-blue-50 p-2 text-blue-600">
                        <GraduationCap className="h-4 w-4" />
                      </div>
                      <h3 className="text-sm font-bold uppercase tracking-wider text-slate-800">
                        5. Educational Credentials
                      </h3>
                    </div>
                    <button
                      type="button"
                      onClick={addQualification}
                      className="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 cursor-pointer"
                    >
                      <Plus className="h-3.5 w-3.5" />
                      Add Credential
                    </button>
                  </div>

                  <div className="mt-5 space-y-3">
                    {Array.isArray(qualifications) &&
                      qualifications.map((q, idx) => (
                        <div
                          key={idx}
                          className="grid grid-cols-1 sm:grid-cols-4 gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/60 items-center"
                        >
                          <div>
                            <input
                              type="text"
                              placeholder="Degree / Course"
                              value={q.degree}
                              onChange={(e) => handleQualificationChange(idx, 'degree', e.target.value)}
                              className="w-full text-xs bg-white border border-slate-200 rounded-lg p-2"
                            />
                          </div>
                          <div>
                            <input
                              type="text"
                              placeholder="University / Board"
                              value={q.institution}
                              onChange={(e) => handleQualificationChange(idx, 'institution', e.target.value)}
                              className="w-full text-xs bg-white border border-slate-200 rounded-lg p-2"
                            />
                          </div>
                          <div>
                            <input
                              type="text"
                              placeholder="Passing Year"
                              value={q.year_of_passing}
                              onChange={(e) => handleQualificationChange(idx, 'year_of_passing', e.target.value)}
                              className="w-full text-xs bg-white border border-slate-200 rounded-lg p-2"
                            />
                          </div>
                          <div className="flex items-center gap-2">
                            <input
                              type="text"
                              placeholder="Grade / %"
                              value={q.grade_or_percentage}
                              onChange={(e) => handleQualificationChange(idx, 'grade_or_percentage', e.target.value)}
                              className="w-full text-xs bg-white border border-slate-200 rounded-lg p-2 font-mono"
                            />
                            {qualifications.length > 1 && (
                              <button
                                type="button"
                                onClick={() => removeQualification(idx)}
                                className="text-slate-400 hover:text-red-600 p-1 cursor-pointer"
                              >
                                <Trash2 className="w-4 h-4" />
                              </button>
                            )}
                          </div>
                        </div>
                      ))}
                  </div>
                </div>

                {/* SECTION 6: COMPENSATION & STATUTORY SALARY CALCULATOR */}
                <div className="mt-5">
                  <SalaryCalculatorWidget
                    basicSalary={formData.basic_salary}
                    monthlyTax={formData.monthly_tax}
                    enableEpf={formData.enable_epf}
                    onBasicChange={(val: number) => setFormData((prev) => ({ ...prev, basic_salary: val }))}
                    onTaxChange={(val: number) => setFormData((prev) => ({ ...prev, monthly_tax: val }))}
                    onEpfToggle={(val: boolean) => setFormData((prev) => ({ ...prev, enable_epf: val }))}
                  />
                </div>

                {/* SUBMIT BUTTON BAR */}
                <div className="sticky bottom-4 z-20 flex items-center justify-between rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur-md">
                  <Link
                    href="/employees"
                    className="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                  >
                    Cancel
                  </Link>

                  <button
                    type="submit"
                    disabled={saving}
                    className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm hover:bg-blue-700 transition cursor-pointer disabled:opacity-50"
                  >
                    {saving ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin" />
                        Saving Updates...
                      </>
                    ) : (
                      <>
                        <Save className="w-4 h-4" />
                        Save Employee Changes
                      </>
                    )}
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>
      </AppLayout>
    </ProtectedRoute>
  );
}

export default function EmployeeEditPage() {
  return (
    <Suspense
      fallback={
        <div className="flex min-h-screen items-center justify-center bg-slate-50">
          <div className="flex flex-col items-center gap-3">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent" />
            <p className="text-sm font-medium text-slate-600">Loading Employee Workspace...</p>
          </div>
        </div>
      }
    >
      <EmployeeEditContent />
    </Suspense>
  );
}
