'use client';

import React, { useEffect, useState } from 'react';
import ProtectedRoute from '@/components/ProtectedRoute';
import AppLayout from '@/components/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { apiRequest } from '@/lib/api';
import Cookies from 'js-cookie';
import { getCachedSettings, fetchSettingsOverview, invalidateSettingsCache } from '@/lib/settingsCache';
import Link from 'next/link';
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
  Mail,
  Server,
  Lock,
  Send,
  CheckCircle2,
  AlertCircle,
  Loader2,
  KeyRound,
  Eye,
  EyeOff,
  HelpCircle,
  ArrowLeft,
  RefreshCw,
  Plus,
  Building,
  Edit2,
  Trash2,
  Globe,
  Phone,
  MapPin,
  Check,
  Award,
  Clock,
  Navigation,
  Compass,
  Crosshair,
} from 'lucide-react';

// Interfaces
interface SmtpSettings {
  mail_mailer: string;
  mail_host: string;
  mail_port: string;
  mail_username: string;
  mail_password?: string;
  mail_encryption: string;
  mail_from_address: string;
  mail_from_name: string;
  is_configured: boolean;
}

export interface Company {
  id: number;
  name: string;
  legal_name?: string;
  registration_number?: string;
  tax_id?: string;
  email?: string;
  phone?: string;
  website?: string;
  address?: string;
  city?: string;
  state?: string;
  country: string;
  postal_code?: string;
  currency: string;
  timezone: string;
  is_primary: boolean;
  is_active: boolean;
}

export interface Department {
  id: number;
  company_id?: number | null;
  name: string;
  code?: string;
  description?: string;
  is_active: boolean;
  company?: { id: number; name: string };
  designations_count?: number;
}

export interface Designation {
  id: number;
  department_id?: number | null;
  name: string;
  code?: string;
  description?: string;
  is_active: boolean;
  department?: { id: number; name: string };
}

export interface Shift {
  id: number;
  company_id?: number | null;
  name: string;
  code?: string;
  start_time: string;
  end_time: string;
  grace_period_minutes: number;
  half_day_minutes: number;
  full_day_minutes: number;
  is_night_shift: boolean;
  is_active: boolean;
  company?: { id: number; name: string };
}

export interface LocationEntity {
  id: number;
  company_id?: number | null;
  name: string;
  code?: string;
  address?: string;
  city?: string;
  state?: string;
  country: string;
  latitude: number;
  longitude: number;
  radius_meters: number;
  enforce_geofence: boolean;
  is_active: boolean;
  company?: { id: number; name: string };
}

type TabType = 'company' | 'department' | 'designation' | 'shift' | 'location' | 'smtp';

export default function SettingsPage() {
  const { user, logout, token } = useAuth();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [activeTab, setActiveTab] = useState<TabType>('company');

  // Fast Instant Hydration from Cache
  const [companies, setCompanies] = useState<Company[]>(() => (getCachedSettings()?.companies as Company[]) || []);
  const [departments, setDepartments] = useState<Department[]>(() => (getCachedSettings()?.departments as Department[]) || []);
  const [designations, setDesignations] = useState<Designation[]>(() => (getCachedSettings()?.designations as Designation[]) || []);
  const [shifts, setShifts] = useState<Shift[]>(() => (getCachedSettings()?.shifts as Shift[]) || []);
  const [locations, setLocations] = useState<LocationEntity[]>(() => (getCachedSettings()?.locations as LocationEntity[]) || []);

  // Loading States - Only true if no cached data exists at all
  const [dataLoading, setDataLoading] = useState(() => !getCachedSettings() && companies.length === 0);
  const [modalSaving, setModalSaving] = useState(false);

  // SMTP State
  const [smtpData, setSmtpData] = useState<SmtpSettings>(() => (getCachedSettings()?.smtp as SmtpSettings) || {
    mail_mailer: 'smtp',
    mail_host: '',
    mail_port: '587',
    mail_username: '',
    mail_password: '',
    mail_encryption: 'tls',
    mail_from_address: '',
    mail_from_name: '',
    is_configured: false,
  });
  const [showSmtpPassword, setShowSmtpPassword] = useState(false);
  const [smtpTesting, setSmtpTesting] = useState(false);
  const [testEmail, setTestEmail] = useState('');
  const [smtpTestStatus, setSmtpTestStatus] = useState<{ success: boolean; message: string } | null>(null);

  // Modals & Editing Objects
  const [modalType, setModalType] = useState<TabType | null>(null);
  const [editingItem, setEditingItem] = useState<any>(null);

  // Form Model States
  const [companyForm, setCompanyForm] = useState<Partial<Company>>({});
  const [departmentForm, setDepartmentForm] = useState<Partial<Department>>({});
  const [designationForm, setDesignationForm] = useState<Partial<Designation>>({});
  const [shiftForm, setShiftForm] = useState<Partial<Shift>>({});
  const [locationForm, setLocationForm] = useState<Partial<LocationEntity>>({});
  const [detectingGps, setDetectingGps] = useState(false);
  const [detectGpsMessage, setDetectGpsMessage] = useState<{ text: string; isError?: boolean } | null>(null);

  // Feedback Messages
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const navItems = [
    { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { name: 'Employees', href: '/employees', icon: Users },
    { name: 'Departments', href: '/settings', icon: Building2 },
    { name: 'Attendance', href: '/dashboard', icon: CalendarCheck, badge: 'Soon' },
    { name: 'Payroll', href: '/dashboard', icon: CreditCard, badge: 'Soon' },
    { name: 'Settings', href: '/settings', icon: Settings, active: true },
  ];

  // Load All Settings Data (Cached + Background Revalidation)
  const loadAllData = async (forceRefresh = false) => {
    try {
      if (forceRefresh) {
        invalidateSettingsCache();
      }
      const data = await fetchSettingsOverview(forceRefresh);

      setCompanies(data.companies || []);
      setDepartments(data.departments || []);
      setDesignations(data.designations || []);
      setShifts(data.shifts || []);
      setLocations(data.locations || []);
      if (data.smtp) setSmtpData(data.smtp);
      if (user?.email) setTestEmail(user.email);
    } catch (err: unknown) {
      if (err instanceof Error) setErrorMessage(err.message);
    } finally {
      setDataLoading(false);
    }
  };

  useEffect(() => {
    const syncTabFromUrl = () => {
      if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const tabParam = params.get('tab') as TabType;
        if (tabParam && ['company', 'department', 'designation', 'shift', 'location', 'smtp'].includes(tabParam)) {
          setActiveTab(tabParam);
        } else if (!tabParam) {
          setActiveTab('company');
        }
      }
    };

    syncTabFromUrl();
    window.addEventListener('popstate', syncTabFromUrl);
    loadAllData();

    return () => {
      window.removeEventListener('popstate', syncTabFromUrl);
    };
  }, []);

  // Alert dismiss timeout
  const showSuccess = (msg: string) => {
    setSuccessMessage(msg);
    setTimeout(() => setSuccessMessage(null), 4000);
  };

  // ==================== OPEN MODAL HANDLERS ====================
  const handleOpenAdd = (type: TabType) => {
    setModalType(type);
    setEditingItem(null);
    setErrorMessage(null);
    setDetectGpsMessage(null);
    setDetectingGps(false);

    if (type === 'company') {
      setCompanyForm({
        name: '',
        legal_name: '',
        registration_number: '',
        tax_id: '',
        email: '',
        phone: '',
        website: '',
        address: '',
        city: '',
        state: '',
        country: 'India',
        postal_code: '',
        currency: 'INR',
        timezone: 'Asia/Kolkata',
        is_primary: companies.length === 0,
        is_active: true,
      });
    } else if (type === 'department') {
      setDepartmentForm({
        name: '',
        code: '',
        description: '',
        company_id: companies[0]?.id || undefined,
        is_active: true,
      });
    } else if (type === 'designation') {
      setDesignationForm({
        name: '',
        code: '',
        description: '',
        department_id: departments[0]?.id || undefined,
        is_active: true,
      });
    } else if (type === 'shift') {
      setShiftForm({
        name: '',
        code: '',
        start_time: '09:00',
        end_time: '18:00',
        grace_period_minutes: 15,
        half_day_minutes: 240,
        full_day_minutes: 480,
        is_night_shift: false,
        is_active: true,
        company_id: companies[0]?.id || undefined,
      });
    } else if (type === 'location') {
      setLocationForm({
        name: '',
        code: '',
        address: '',
        city: '',
        state: '',
        country: 'India',
        latitude: 19.0760,
        longitude: 72.8777,
        radius_meters: 100,
        enforce_geofence: true,
        is_active: true,
        company_id: companies[0]?.id || undefined,
      });
    }
  };

  const handleOpenEdit = (type: TabType, item: any) => {
    setModalType(type);
    setEditingItem(item);
    setErrorMessage(null);
    setDetectGpsMessage(null);
    setDetectingGps(false);

    if (type === 'company') setCompanyForm({ ...item });
    if (type === 'department') setDepartmentForm({ ...item });
    if (type === 'designation') setDesignationForm({ ...item });
    if (type === 'shift') setShiftForm({ ...item });
    if (type === 'location') setLocationForm({ ...item });
  };

  // Geolocation detection handler
  const handleDetectCurrentLocation = () => {
    if (!navigator.geolocation) {
      setDetectGpsMessage({ text: 'Geolocation is not supported by your browser.', isError: true });
      return;
    }

    setDetectingGps(true);
    setDetectGpsMessage(null);

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = parseFloat(position.coords.latitude.toFixed(6));
        const lng = parseFloat(position.coords.longitude.toFixed(6));
        const accuracy = Math.round(position.coords.accuracy);

        setLocationForm((prev) => ({
          ...prev,
          latitude: lat,
          longitude: lng,
        }));

        setDetectGpsMessage({
          text: `Location detected (±${accuracy}m accuracy): ${lat}, ${lng}`,
          isError: false,
        });
        setDetectingGps(false);
      },
      (error) => {
        let msg = 'Unable to retrieve your location.';
        if (error.code === error.PERMISSION_DENIED) {
          msg = 'Location permission denied. Please allow location access in your browser.';
        } else if (error.code === error.POSITION_UNAVAILABLE) {
          msg = 'Location information is unavailable.';
        } else if (error.code === error.TIMEOUT) {
          msg = 'Location request timed out.';
        }
        setDetectGpsMessage({ text: msg, isError: true });
        setDetectingGps(false);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
      }
    );
  };

  // ==================== SAVE HANDLER ====================
  const handleSaveModal = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token || !modalType) return;
    setModalSaving(true);
    setErrorMessage(null);

    try {
      let endpoint = `/settings/${modalType === 'company' ? 'companies' : modalType + 's'}`;
      let body: any = {};

      if (modalType === 'company') body = companyForm;
      if (modalType === 'department') body = departmentForm;
      if (modalType === 'designation') body = designationForm;
      if (modalType === 'shift') body = shiftForm;
      if (modalType === 'location') body = locationForm;

      if (editingItem) {
        endpoint += `/${editingItem.id}`;
        await apiRequest(endpoint, { method: 'PUT', body: JSON.stringify(body) }, token);
        showSuccess(`${modalType.toUpperCase()} updated successfully.`);
      } else {
        await apiRequest(endpoint, { method: 'POST', body: JSON.stringify(body) }, token);
        showSuccess(`${modalType.toUpperCase()} created successfully.`);
      }

      setModalType(null);
      await loadAllData(true);
    } catch (err: unknown) {
      if (err instanceof Error) setErrorMessage(err.message);
    } finally {
      setModalSaving(false);
    }
  };

  // ==================== DELETE HANDLER ====================
  const handleDeleteItem = async (type: TabType, id: number, name: string) => {
    if (!token) return;
    if (!confirm(`Are you sure you want to delete "${name}"?`)) return;

    try {
      const endpoint = `/settings/${type === 'company' ? 'companies' : type + 's'}/${id}`;
      await apiRequest(endpoint, { method: 'DELETE' }, token);
      showSuccess(`Deleted successfully.`);
      await loadAllData(true);
    } catch (err: unknown) {
      if (err instanceof Error) setErrorMessage(err.message);
    }
  };

  // SMTP Actions
  const handleSmtpSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return;
    setModalSaving(true);
    try {
      const res = await apiRequest<{ message: string }>('/settings/smtp', { method: 'POST', body: JSON.stringify(smtpData) }, token);
      showSuccess(res.message);
      setSmtpData((prev) => ({ ...prev, is_configured: true }));
    } catch (err: unknown) {
      if (err instanceof Error) setErrorMessage(err.message);
    } finally {
      setModalSaving(false);
    }
  };

  const handleTestConnection = async () => {
    if (!testEmail || !token) return;
    setSmtpTesting(true);
    setSmtpTestStatus(null);
    try {
      const res = await apiRequest<{ message: string }>('/settings/smtp/test', { method: 'POST', body: JSON.stringify({ test_email: testEmail }) }, token);
      setSmtpTestStatus({ success: true, message: res.message });
    } catch (err: unknown) {
      if (err instanceof Error) setSmtpTestStatus({ success: false, message: err.message });
    } finally {
      setSmtpTesting(false);
    }
  };

  return (
    <ProtectedRoute>
      <AppLayout
        title={
          <div className="flex items-center gap-2 text-xs font-medium text-slate-500">
            <Link href="/dashboard" className="hover:text-blue-600 transition flex items-center gap-1">
              <ArrowLeft className="h-3.5 w-3.5" />
              Dashboard
            </Link>
            <span>/</span>
            <span className="text-slate-900 font-semibold">Settings</span>
            <span>/</span>
            <span className="text-blue-600 font-semibold capitalize">{activeTab} Manager</span>
          </div>
        }
        action={
          activeTab !== 'smtp' ? (
            <button
              type="button"
              onClick={() => handleOpenAdd(activeTab)}
              className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white shadow-xs transition hover:bg-blue-700 cursor-pointer"
            >
              <Plus className="h-4 w-4" />
              <span>
                Add {activeTab === 'company' ? 'Company Entity' : activeTab.charAt(0).toUpperCase() + activeTab.slice(1)}
              </span>
            </button>
          ) : undefined
        }
      >
        <div className="p-4 sm:p-6 lg:p-8 bg-slate-50">
          {/* Header section with Dynamic Add Button */}
          <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div>
                <h2 className="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                  System Settings & Organization
                </h2>
                <p className="mt-1 text-xs sm:text-sm text-slate-500">
                  Manage corporate structure, operational shifts, geofenced work locations, and mail settings.
                </p>
              </div>

              {activeTab !== 'smtp' && (
                <button
                  type="button"
                  onClick={() => handleOpenAdd(activeTab)}
                  className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-xs transition hover:bg-blue-700"
                >
                  <Plus className="h-4 w-4" />
                  <span>
                    Add {activeTab === 'company' ? 'Company Entity' : activeTab.charAt(0).toUpperCase() + activeTab.slice(1)}
                  </span>
                </button>
              )}
            </div>

            {/* Comprehensive Horizontal Tab Bar */}
            <div className="mb-6 flex border-b border-slate-200 overflow-x-auto gap-1">
              {[
                { id: 'company', label: `Companies (${companies.length})`, icon: Building },
                { id: 'department', label: `Departments (${departments.length})`, icon: Building2 },
                { id: 'designation', label: `Designations (${designations.length})`, icon: Award },
                { id: 'shift', label: `Shift Manager (${shifts.length})`, icon: Clock },
                { id: 'location', label: `Location & Geofence (${locations.length})`, icon: Compass },
                { id: 'smtp', label: 'SMTP Mail Service', icon: Mail },
              ].map((tab) => {
                const TabIcon = tab.icon;
                const isActive = activeTab === tab.id;
                return (
                  <button
                    key={tab.id}
                    type="button"
                    onClick={() => setActiveTab(tab.id as TabType)}
                    className={`flex items-center gap-2 border-b-2 px-3.5 py-2.5 text-xs sm:text-sm font-semibold transition whitespace-nowrap ${
                      isActive
                        ? 'border-blue-600 text-blue-600 bg-white/70'
                        : 'border-transparent text-slate-500 hover:text-slate-800'
                    }`}
                  >
                    <TabIcon className="h-4 w-4 shrink-0" />
                    <span>{tab.label}</span>
                  </button>
                );
              })}
            </div>

            {/* Notification messages */}
            {successMessage && (
              <div className="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs sm:text-sm text-emerald-800 shadow-xs">
                <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600" />
                <span>{successMessage}</span>
              </div>
            )}

            {errorMessage && (
              <div className="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-800 shadow-xs">
                <AlertCircle className="h-5 w-5 shrink-0 text-red-600" />
                <span>{errorMessage}</span>
              </div>
            )}

            {dataLoading && companies.length === 0 && departments.length === 0 ? (
              <div className="flex flex-col items-center justify-center py-20 gap-3">
                <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
                <span className="text-xs font-medium text-slate-500">Loading module data...</span>
              </div>
            ) : (
              <>
                {/* ===================== TAB: COMPANIES ===================== */}
                {activeTab === 'company' && (
                  <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    {companies.map((c) => (
                      <div
                        key={c.id}
                        className={`rounded-2xl border bg-white p-5 shadow-xs flex flex-col justify-between ${
                          c.is_primary ? 'border-blue-300 ring-1 ring-blue-100' : 'border-slate-200'
                        }`}
                      >
                        <div>
                          <div className="flex items-start justify-between gap-3">
                            <div className="flex items-center gap-3">
                              <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700 border border-blue-100 font-bold">
                                {c.name.slice(0, 2).toUpperCase()}
                              </div>
                              <div>
                                <h3 className="text-base font-bold text-slate-900">{c.name}</h3>
                                <p className="text-xs text-slate-500">{c.legal_name || 'Operating Entity'}</p>
                              </div>
                            </div>
                            {c.is_primary && (
                              <span className="rounded-full bg-blue-50 border border-blue-200 px-2.5 py-0.5 text-[10px] font-bold text-blue-700">
                                Primary
                              </span>
                            )}
                          </div>

                          <div className="mt-4 space-y-1.5 text-xs text-slate-600 border-t border-slate-100 pt-3">
                            {c.registration_number && (
                              <div className="flex justify-between">
                                <span className="text-slate-400">Reg No:</span>
                                <span className="font-mono text-slate-800">{c.registration_number}</span>
                              </div>
                            )}
                            {c.tax_id && (
                              <div className="flex justify-between">
                                <span className="text-slate-400">Tax ID:</span>
                                <span className="font-mono text-slate-800">{c.tax_id}</span>
                              </div>
                            )}
                            {c.email && (
                              <div className="flex items-center gap-2 text-slate-600">
                                <Mail className="h-3.5 w-3.5 text-slate-400" />
                                <span className="truncate">{c.email}</span>
                              </div>
                            )}
                            {c.city && c.country && (
                              <div className="flex items-center gap-2 text-slate-600">
                                <MapPin className="h-3.5 w-3.5 text-slate-400" />
                                <span>{c.city}, {c.country}</span>
                              </div>
                            )}
                          </div>
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                          <span className="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium uppercase text-slate-600">
                            {c.currency} • {c.timezone}
                          </span>
                          <div className="flex items-center gap-1.5">
                            <button
                              type="button"
                              onClick={() => handleOpenEdit('company', c)}
                              className="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50"
                            >
                              <Edit2 className="h-4 w-4" />
                            </button>
                            <button
                              type="button"
                              onClick={() => handleDeleteItem('company', c.id, c.name)}
                              className="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50"
                            >
                              <Trash2 className="h-4 w-4" />
                            </button>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {/* ===================== TAB: DEPARTMENTS ===================== */}
                {activeTab === 'department' && (
                  <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    {departments.map((d) => (
                      <div key={d.id} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                        <div>
                          <div className="flex items-start justify-between">
                            <div className="flex items-center gap-3">
                              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 font-bold">
                                <Building2 className="h-5 w-5" />
                              </div>
                              <div>
                                <h3 className="text-base font-bold text-slate-900">{d.name}</h3>
                                {d.code && (
                                  <span className="inline-block font-mono text-[11px] text-indigo-600 font-semibold">
                                    [{d.code}]
                                  </span>
                                )}
                              </div>
                            </div>
                            <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">
                              {d.designations_count || 0} Roles
                            </span>
                          </div>
                          <p className="mt-3 text-xs text-slate-500 line-clamp-2">
                            {d.description || 'No description provided.'}
                          </p>
                          {d.company && (
                            <div className="mt-3 flex items-center gap-1.5 text-xs text-slate-400">
                              <Building className="h-3.5 w-3.5" />
                              <span className="truncate">{d.company.name}</span>
                            </div>
                          )}
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end gap-1.5">
                          <button
                            type="button"
                            onClick={() => handleOpenEdit('department', d)}
                            className="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50"
                          >
                            <Edit2 className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            onClick={() => handleDeleteItem('department', d.id, d.name)}
                            className="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50"
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {/* ===================== TAB: DESIGNATIONS ===================== */}
                {activeTab === 'designation' && (
                  <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    {designations.map((des) => (
                      <div key={des.id} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                        <div>
                          <div className="flex items-start justify-between">
                            <div className="flex items-center gap-3">
                              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 border border-purple-100 font-bold">
                                <Award className="h-5 w-5" />
                              </div>
                              <div>
                                <h3 className="text-base font-bold text-slate-900">{des.name}</h3>
                                {des.code && (
                                  <span className="inline-block font-mono text-[11px] text-purple-600 font-semibold">
                                    [{des.code}]
                                  </span>
                                )}
                              </div>
                            </div>
                          </div>

                          <div className="mt-3.5 flex items-center gap-2">
                            <span className="rounded-lg bg-indigo-50 border border-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                              {des.department?.name || 'General Department'}
                            </span>
                          </div>

                          <p className="mt-2 text-xs text-slate-500 line-clamp-2">
                            {des.description || 'Job role responsibility profile.'}
                          </p>
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end gap-1.5">
                          <button
                            type="button"
                            onClick={() => handleOpenEdit('designation', des)}
                            className="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50"
                          >
                            <Edit2 className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            onClick={() => handleDeleteItem('designation', des.id, des.name)}
                            className="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50"
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {/* ===================== TAB: SHIFT MANAGER ===================== */}
                {activeTab === 'shift' && (
                  <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    {shifts.map((sh) => (
                      <div key={sh.id} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                        <div>
                          <div className="flex items-start justify-between">
                            <div className="flex items-center gap-3">
                              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 border border-amber-100">
                                <Clock className="h-5 w-5" />
                              </div>
                              <div>
                                <h3 className="text-base font-bold text-slate-900">{sh.name}</h3>
                                {sh.code && <span className="font-mono text-[11px] text-amber-600">[{sh.code}]</span>}
                              </div>
                            </div>
                            {sh.is_night_shift && (
                              <span className="rounded-full bg-slate-900 text-white px-2 py-0.5 text-[10px] font-semibold">
                                Night Shift
                              </span>
                            )}
                          </div>

                          {/* Shift Timings Card */}
                          <div className="mt-4 rounded-xl bg-slate-50 p-3 border border-slate-100">
                            <div className="flex items-center justify-between text-xs font-bold text-slate-800">
                              <span>Work Hours:</span>
                              <span className="text-blue-600 font-mono">{sh.start_time.slice(0, 5)} - {sh.end_time.slice(0, 5)}</span>
                            </div>
                            <div className="mt-2 grid grid-cols-2 gap-2 text-[11px] text-slate-500 border-t border-slate-200/60 pt-2">
                              <div>Late Grace: <strong className="text-slate-700">{sh.grace_period_minutes}m</strong></div>
                              <div>Min Half-Day: <strong className="text-slate-700">{sh.half_day_minutes / 60}h</strong></div>
                            </div>
                          </div>
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end gap-1.5">
                          <button
                            type="button"
                            onClick={() => handleOpenEdit('shift', sh)}
                            className="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50"
                          >
                            <Edit2 className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            onClick={() => handleDeleteItem('shift', sh.id, sh.name)}
                            className="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50"
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {/* ===================== TAB: LOCATION MANAGER (LAT/LONG & RADIUS) ===================== */}
                {activeTab === 'location' && (
                  <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    {locations.map((loc) => (
                      <div key={loc.id} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                        <div>
                          <div className="flex items-start justify-between">
                            <div className="flex items-center gap-3">
                              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                                <MapPin className="h-5 w-5" />
                              </div>
                              <div>
                                <h3 className="text-base font-bold text-slate-900">{loc.name}</h3>
                                <p className="text-xs text-slate-500">{loc.city}, {loc.country}</p>
                              </div>
                            </div>
                            {loc.enforce_geofence && (
                              <span className="rounded-full bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                Geofence On
                              </span>
                            )}
                          </div>

                          {/* Geofence specs */}
                          <div className="mt-4 rounded-xl bg-slate-50 p-3 border border-slate-100 space-y-2">
                            <div className="flex items-center justify-between text-xs">
                              <span className="text-slate-500">Allowed Punch Radius:</span>
                              <span className="font-bold text-emerald-700 font-mono bg-emerald-100/60 px-2 py-0.5 rounded-md">
                                {loc.radius_meters} meters
                              </span>
                            </div>
                            <div className="flex items-center justify-between text-[11px] font-mono text-slate-600 border-t border-slate-200/60 pt-2">
                              <span>Lat: {Number(loc.latitude).toFixed(6)}</span>
                              <span>Long: {Number(loc.longitude).toFixed(6)}</span>
                            </div>
                          </div>

                          {loc.address && (
                            <p className="mt-2.5 text-xs text-slate-500 line-clamp-2">
                              {loc.address}
                            </p>
                          )}
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                          <a
                            href={`https://www.google.com/maps?q=${loc.latitude},${loc.longitude}`}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 hover:underline"
                          >
                            <Navigation className="h-3 w-3" />
                            View on Map
                          </a>

                          <div className="flex items-center gap-1.5">
                            <button
                              type="button"
                              onClick={() => handleOpenEdit('location', loc)}
                              className="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50"
                            >
                              <Edit2 className="h-4 w-4" />
                            </button>
                            <button
                              type="button"
                              onClick={() => handleDeleteItem('location', loc.id, loc.name)}
                              className="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50"
                            >
                              <Trash2 className="h-4 w-4" />
                            </button>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {/* ===================== TAB: SMTP CONFIGURATION ===================== */}
                {activeTab === 'smtp' && (
                  <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 space-y-6">
                      <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                        <form onSubmit={handleSmtpSave} className="space-y-4">
                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                              <label className="block text-xs font-semibold uppercase text-slate-700">Driver</label>
                              <select
                                value={smtpData.mail_mailer}
                                onChange={(e) => setSmtpData({ ...smtpData, mail_mailer: e.target.value })}
                                className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                              >
                                <option value="smtp">SMTP</option>
                                <option value="sendmail">Sendmail</option>
                                <option value="log">Log</option>
                              </select>
                            </div>
                            <div>
                              <label className="block text-xs font-semibold uppercase text-slate-700">Encryption</label>
                              <select
                                value={smtpData.mail_encryption}
                                onChange={(e) => setSmtpData({ ...smtpData, mail_encryption: e.target.value })}
                                className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                              >
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                              </select>
                            </div>
                          </div>

                          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div className="sm:col-span-2">
                              <label className="block text-xs font-semibold uppercase text-slate-700">Host</label>
                              <input
                                type="text"
                                required
                                value={smtpData.mail_host}
                                onChange={(e) => setSmtpData({ ...smtpData, mail_host: e.target.value })}
                                className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                              />
                            </div>
                            <div>
                              <label className="block text-xs font-semibold uppercase text-slate-700">Port</label>
                              <input
                                type="number"
                                required
                                value={smtpData.mail_port}
                                onChange={(e) => setSmtpData({ ...smtpData, mail_port: e.target.value })}
                                className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                              />
                            </div>
                          </div>

                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                              <label className="block text-xs font-semibold uppercase text-slate-700">Username</label>
                              <input
                                type="text"
                                value={smtpData.mail_username}
                                onChange={(e) => setSmtpData({ ...smtpData, mail_username: e.target.value })}
                                className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                              />
                            </div>
                            <div>
                              <label className="block text-xs font-semibold uppercase text-slate-700">Password</label>
                              <div className="relative mt-1">
                                <input
                                  type={showSmtpPassword ? 'text' : 'password'}
                                  value={smtpData.mail_password || ''}
                                  onChange={(e) => setSmtpData({ ...smtpData, mail_password: e.target.value })}
                                  placeholder="Leave blank to keep existing"
                                  className="block w-full rounded-lg border border-slate-300 py-2 pl-3 pr-10 text-sm"
                                />
                                <button
                                  type="button"
                                  onClick={() => setShowSmtpPassword(!showSmtpPassword)}
                                  className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400"
                                >
                                  {showSmtpPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                </button>
                              </div>
                            </div>
                          </div>

                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                              <label className="block text-xs font-semibold uppercase text-slate-700">From Address</label>
                              <input
                                type="email"
                                required
                                value={smtpData.mail_from_address}
                                onChange={(e) => setSmtpData({ ...smtpData, mail_from_address: e.target.value })}
                                className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                              />
                            </div>
                            <div>
                              <label className="block text-xs font-semibold uppercase text-slate-700">Sender Name</label>
                              <input
                                type="text"
                                required
                                value={smtpData.mail_from_name}
                                onChange={(e) => setSmtpData({ ...smtpData, mail_from_name: e.target.value })}
                                className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                              />
                            </div>
                          </div>

                          <div className="pt-3 flex justify-end">
                            <button
                              type="submit"
                              disabled={modalSaving}
                              className="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                              Save SMTP Settings
                            </button>
                          </div>
                        </form>
                      </div>
                    </div>

                    <div className="space-y-6">
                      <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                        <h4 className="text-sm font-bold text-slate-900">Test SMTP</h4>
                        <input
                          type="email"
                          value={testEmail}
                          onChange={(e) => setTestEmail(e.target.value)}
                          placeholder="recipient@test.com"
                          className="mt-3 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                        <button
                          type="button"
                          onClick={handleTestConnection}
                          disabled={smtpTesting}
                          className="mt-3 w-full rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white hover:bg-slate-800"
                        >
                          {smtpTesting ? 'Sending...' : 'Send Test Mail'}
                        </button>
                        {smtpTestStatus && (
                          <div className={`mt-3 p-3 rounded-lg text-xs ${smtpTestStatus.success ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800'}`}>
                            {smtpTestStatus.message}
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                )}
              </>
            )}
        </div>

        {/* ===================== UNIVERSAL ADD/EDIT MODAL ===================== */}
        {modalType && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs overflow-y-auto">
            <div className="relative w-full max-w-xl my-8 rounded-2xl bg-white border border-slate-200 shadow-2xl p-6 sm:p-7">
              <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 className="text-base sm:text-lg font-bold text-slate-900">
                  {editingItem ? 'Edit' : 'Add'} {modalType.toUpperCase()}
                </h3>
                <button
                  type="button"
                  onClick={() => setModalType(null)}
                  className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700"
                >
                  <X className="h-5 w-5" />
                </button>
              </div>

              <form onSubmit={handleSaveModal} className="mt-4 space-y-4">
                {/* COMPANY MODAL */}
                {modalType === 'company' && (
                  <>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Brand Name *</label>
                        <input
                          type="text"
                          required
                          value={companyForm.name || ''}
                          onChange={(e) => setCompanyForm({ ...companyForm, name: e.target.value })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Legal Name</label>
                        <input
                          type="text"
                          value={companyForm.legal_name || ''}
                          onChange={(e) => setCompanyForm({ ...companyForm, legal_name: e.target.value })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Reg No (CIN/LLC)</label>
                        <input
                          type="text"
                          value={companyForm.registration_number || ''}
                          onChange={(e) => setCompanyForm({ ...companyForm, registration_number: e.target.value })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Tax ID / GST</label>
                        <input
                          type="text"
                          value={companyForm.tax_id || ''}
                          onChange={(e) => setCompanyForm({ ...companyForm, tax_id: e.target.value })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Currency *</label>
                        <select
                          value={companyForm.currency || 'INR'}
                          onChange={(e) => setCompanyForm({ ...companyForm, currency: e.target.value })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        >
                          <option value="INR">INR (₹)</option>
                          <option value="USD">USD ($)</option>
                          <option value="EUR">EUR (€)</option>
                          <option value="GBP">GBP (£)</option>
                          <option value="AED">AED (د.إ)</option>
                        </select>
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Timezone *</label>
                        <select
                          value={companyForm.timezone || 'Asia/Kolkata'}
                          onChange={(e) => setCompanyForm({ ...companyForm, timezone: e.target.value })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        >
                          <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                          <option value="UTC">UTC</option>
                          <option value="America/New_York">America/New_York (EST)</option>
                          <option value="Europe/London">Europe/London (GMT)</option>
                          <option value="Asia/Dubai">Asia/Dubai (GST)</option>
                        </select>
                      </div>
                    </div>
                  </>
                )}

                {/* DEPARTMENT MODAL */}
                {modalType === 'department' && (
                  <>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                      <div className="sm:col-span-2">
                        <label className="block text-xs font-semibold uppercase text-slate-700">Department Name *</label>
                        <input
                          type="text"
                          required
                          value={departmentForm.name || ''}
                          onChange={(e) => setDepartmentForm({ ...departmentForm, name: e.target.value })}
                          placeholder="e.g. Engineering, Sales, Human Resources"
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Code</label>
                        <input
                          type="text"
                          value={departmentForm.code || ''}
                          onChange={(e) => setDepartmentForm({ ...departmentForm, code: e.target.value })}
                          placeholder="e.g. ENG"
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                    </div>
                    {companies.length > 0 && (
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Belongs to Company</label>
                        <select
                          value={departmentForm.company_id || ''}
                          onChange={(e) => setDepartmentForm({ ...departmentForm, company_id: Number(e.target.value) })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        >
                          {companies.map((c) => (
                            <option key={c.id} value={c.id}>{c.name}</option>
                          ))}
                        </select>
                      </div>
                    )}
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Description</label>
                      <textarea
                        rows={3}
                        value={departmentForm.description || ''}
                        onChange={(e) => setDepartmentForm({ ...departmentForm, description: e.target.value })}
                        className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                      />
                    </div>
                  </>
                )}

                {/* DESIGNATION MODAL */}
                {modalType === 'designation' && (
                  <>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                      <div className="sm:col-span-2">
                        <label className="block text-xs font-semibold uppercase text-slate-700">Designation / Role Title *</label>
                        <input
                          type="text"
                          required
                          value={designationForm.name || ''}
                          onChange={(e) => setDesignationForm({ ...designationForm, name: e.target.value })}
                          placeholder="e.g. Senior Frontend Architect"
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Role Code</label>
                        <input
                          type="text"
                          value={designationForm.code || ''}
                          onChange={(e) => setDesignationForm({ ...designationForm, code: e.target.value })}
                          placeholder="e.g. SDE-3"
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                    </div>
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Department</label>
                      <select
                        value={designationForm.department_id || ''}
                        onChange={(e) => setDesignationForm({ ...designationForm, department_id: Number(e.target.value) })}
                        className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                      >
                        <option value="">No Department Assigned</option>
                        {departments.map((d) => (
                          <option key={d.id} value={d.id}>{d.name}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Job Description / Responsibilities</label>
                      <textarea
                        rows={3}
                        value={designationForm.description || ''}
                        onChange={(e) => setDesignationForm({ ...designationForm, description: e.target.value })}
                        className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                      />
                    </div>
                  </>
                )}

                {/* SHIFT MODAL */}
                {modalType === 'shift' && (
                  <>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                      <div className="sm:col-span-2">
                        <label className="block text-xs font-semibold uppercase text-slate-700">Shift Name *</label>
                        <input
                          type="text"
                          required
                          value={shiftForm.name || ''}
                          onChange={(e) => setShiftForm({ ...shiftForm, name: e.target.value })}
                          placeholder="e.g. Regular Day Shift"
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Shift Code</label>
                        <input
                          type="text"
                          value={shiftForm.code || ''}
                          onChange={(e) => setShiftForm({ ...shiftForm, code: e.target.value })}
                          placeholder="e.g. SH-01"
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Start Time *</label>
                        <input
                          type="time"
                          required
                          value={shiftForm.start_time || '09:00'}
                          onChange={(e) => setShiftForm({ ...shiftForm, start_time: e.target.value })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">End Time *</label>
                        <input
                          type="time"
                          required
                          value={shiftForm.end_time || '18:00'}
                          onChange={(e) => setShiftForm({ ...shiftForm, end_time: e.target.value })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                    </div>
                    <div className="grid grid-cols-3 gap-3">
                      <div>
                        <label className="block text-[11px] font-semibold uppercase text-slate-600">Grace (Mins)</label>
                        <input
                          type="number"
                          value={shiftForm.grace_period_minutes ?? 15}
                          onChange={(e) => setShiftForm({ ...shiftForm, grace_period_minutes: Number(e.target.value) })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-1.5 px-2.5 text-xs"
                        />
                      </div>
                      <div>
                        <label className="block text-[11px] font-semibold uppercase text-slate-600">Half-Day (Mins)</label>
                        <input
                          type="number"
                          value={shiftForm.half_day_minutes ?? 240}
                          onChange={(e) => setShiftForm({ ...shiftForm, half_day_minutes: Number(e.target.value) })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-1.5 px-2.5 text-xs"
                        />
                      </div>
                      <div>
                        <label className="block text-[11px] font-semibold uppercase text-slate-600">Full-Day (Mins)</label>
                        <input
                          type="number"
                          value={shiftForm.full_day_minutes ?? 480}
                          onChange={(e) => setShiftForm({ ...shiftForm, full_day_minutes: Number(e.target.value) })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-1.5 px-2.5 text-xs"
                        />
                      </div>
                    </div>
                    <div className="flex items-center gap-2 pt-2">
                      <input
                        type="checkbox"
                        id="is_night_shift"
                        checked={shiftForm.is_night_shift ?? false}
                        onChange={(e) => setShiftForm({ ...shiftForm, is_night_shift: e.target.checked })}
                        className="h-4 w-4 rounded border-slate-300 text-blue-600"
                      />
                      <label htmlFor="is_night_shift" className="text-xs font-medium text-slate-700">
                        Overnight Shift (ends next calendar day)
                      </label>
                    </div>
                  </>
                )}

                {/* LOCATION MODAL (LAT, LONG, GEOFENCE RADIUS) */}
                {modalType === 'location' && (
                  <>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                      <div className="sm:col-span-2">
                        <label className="block text-xs font-semibold uppercase text-slate-700">Campus / Branch Name *</label>
                        <input
                          type="text"
                          required
                          value={locationForm.name || ''}
                          onChange={(e) => setLocationForm({ ...locationForm, name: e.target.value })}
                          placeholder="e.g. Headquarters / Mumbai Campus"
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Branch Code</label>
                        <input
                          type="text"
                          value={locationForm.code || ''}
                          onChange={(e) => setLocationForm({ ...locationForm, code: e.target.value })}
                          placeholder="e.g. HQ-01"
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                    </div>

                    {/* Detect Location Button and Notification */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 rounded-xl bg-blue-50/70 border border-blue-100">
                      <div>
                        <p className="text-xs font-semibold text-blue-900 flex items-center gap-1.5">
                          <Crosshair className="h-4 w-4 text-blue-600 shrink-0" />
                          <span>Auto-Detect Current GPS Coordinates</span>
                        </p>
                        <p className="text-[11px] text-blue-600 mt-0.5">
                          Uses browser / device GPS sensor to accurately pin this campus
                        </p>
                      </div>
                      <button
                        type="button"
                        onClick={handleDetectCurrentLocation}
                        disabled={detectingGps}
                        className="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white shadow-xs transition disabled:opacity-70 whitespace-nowrap shrink-0"
                      >
                        {detectingGps ? (
                          <>
                            <Loader2 className="h-3.5 w-3.5 animate-spin" />
                            <span>Detecting GPS...</span>
                          </>
                        ) : (
                          <>
                            <Crosshair className="h-3.5 w-3.5" />
                            <span>Detect Location</span>
                          </>
                        )}
                      </button>
                    </div>

                    {detectGpsMessage && (
                      <div
                        className={`rounded-lg p-2.5 text-xs flex items-center gap-2 ${
                          detectGpsMessage.isError
                            ? 'bg-red-50 text-red-700 border border-red-200'
                            : 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                        }`}
                      >
                        {detectGpsMessage.isError ? (
                          <AlertCircle className="h-4 w-4 text-red-600 shrink-0" />
                        ) : (
                          <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                        )}
                        <span>{detectGpsMessage.text}</span>
                      </div>
                    )}

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Latitude (GPS) *</label>
                        <input
                          type="number"
                          step="any"
                          required
                          value={locationForm.latitude ?? ''}
                          onChange={(e) => setLocationForm({ ...locationForm, latitude: parseFloat(e.target.value) })}
                          placeholder="e.g. 19.076090"
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm font-mono"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Longitude (GPS) *</label>
                        <input
                          type="number"
                          step="any"
                          required
                          value={locationForm.longitude ?? ''}
                          onChange={(e) => setLocationForm({ ...locationForm, longitude: parseFloat(e.target.value) })}
                          placeholder="e.g. 72.877426"
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm font-mono"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Punch Radius (Meters) *</label>
                        <input
                          type="number"
                          min={10}
                          max={50000}
                          required
                          value={locationForm.radius_meters ?? 100}
                          onChange={(e) => setLocationForm({ ...locationForm, radius_meters: parseInt(e.target.value) })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm font-bold text-emerald-700"
                        />
                      </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">City</label>
                        <input
                          type="text"
                          value={locationForm.city || ''}
                          onChange={(e) => setLocationForm({ ...locationForm, city: e.target.value })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold uppercase text-slate-700">Country *</label>
                        <input
                          type="text"
                          required
                          value={locationForm.country || 'India'}
                          onChange={(e) => setLocationForm({ ...locationForm, country: e.target.value })}
                          className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700">Physical Address</label>
                      <input
                        type="text"
                        value={locationForm.address || ''}
                        onChange={(e) => setLocationForm({ ...locationForm, address: e.target.value })}
                        placeholder="Floor, Building, Road name"
                        className="mt-1 block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm"
                      />
                    </div>

                    <div className="flex items-center gap-2 pt-2">
                      <input
                        type="checkbox"
                        id="enforce_geofence"
                        checked={locationForm.enforce_geofence ?? true}
                        onChange={(e) => setLocationForm({ ...locationForm, enforce_geofence: e.target.checked })}
                        className="h-4 w-4 rounded border-slate-300 text-blue-600"
                      />
                      <label htmlFor="enforce_geofence" className="text-xs font-medium text-slate-700">
                        Strict Geofence Enforcement (employees can only clock in within the {locationForm.radius_meters || 100}m radius)
                      </label>
                    </div>
                  </>
                )}

                <div className="pt-4 border-t border-slate-100 flex justify-end gap-2.5">
                  <button
                    type="button"
                    onClick={() => setModalType(null)}
                    className="rounded-xl border border-slate-200 px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={modalSaving}
                    className="rounded-xl bg-blue-600 px-5 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                  >
                    {modalSaving ? 'Saving...' : 'Save Changes'}
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
