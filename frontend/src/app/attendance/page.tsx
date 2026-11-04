'use client';

import React, { useState, useEffect, useRef } from 'react';
import ProtectedRoute from '@/components/ProtectedRoute';
import AppLayout from '@/components/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { apiRequest } from '@/lib/api';
import {
  AttendanceTodayResponse,
  AttendanceRecord,
  FieldVisitRecord,
  AdminAttendanceStats,
} from '@/types/attendance';
import {
  Clock,
  MapPin,
  Camera,
  CheckCircle2,
  AlertCircle,
  Calendar,
  Users,
  Compass,
  ArrowRight,
  ShieldCheck,
  Building,
  RefreshCw,
  Eye,
  X,
  FileText,
  UserCheck,
  Sparkles,
  Info,
  Layers,
  ChevronRight,
  Search,
  Filter,
} from 'lucide-react';

export default function AttendancePage() {
  const { user, token } = useAuth();
  const isSuperadmin = user?.role === 'superadmin';

  // Navigation tab: 'my_attendance' vs 'admin_roster' vs 'field_visits_ledger'
  const [activeTab, setActiveTab] = useState<'my_attendance' | 'admin_roster' | 'field_visits_ledger'>(
    isSuperadmin ? 'admin_roster' : 'my_attendance'
  );

  // Today's Status State
  const [todayData, setTodayData] = useState<AttendanceTodayResponse | null>(null);
  const [loadingToday, setLoadingToday] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Employee Personal Attendance History State
  const [myHistoryRecords, setMyHistoryRecords] = useState<AttendanceRecord[]>([]);
  const [myHistoryStats, setMyHistoryStats] = useState<{
    total_present: number;
    total_late: number;
    total_half_day: number;
    total_hours: number;
  } | null>(null);
  const [loadingMyHistory, setLoadingMyHistory] = useState(false);

  // GPS Live State
  const [userCoords, setUserCoords] = useState<{ lat: number; lng: number } | null>(null);
  const [gpsLoading, setGpsLoading] = useState(false);
  const [gpsError, setGpsError] = useState<string | null>(null);

  // Field Visit Modal State
  const [showFieldVisitModal, setShowFieldVisitModal] = useState(false);
  const [fieldClient, setFieldClient] = useState('');
  const [fieldLocationName, setFieldLocationName] = useState('');
  const [fieldAddress, setFieldAddress] = useState('');
  const [fieldRemarks, setFieldRemarks] = useState('');
  const [fieldSelfie, setFieldSelfie] = useState<string | null>(null);
  const [submittingFieldVisit, setSubmittingFieldVisit] = useState(false);

  // Camera capture state
  const [cameraActive, setCameraActive] = useState(false);
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const streamRef = useRef<MediaStream | null>(null);

  // Admin Ledger State
  const [selectedDate, setSelectedDate] = useState('');
  const [currentDateFormatted, setCurrentDateFormatted] = useState('');
  const [currentTimeFormatted, setCurrentTimeFormatted] = useState('');
  const [adminAttendances, setAdminAttendances] = useState<AttendanceRecord[]>([]);
  const [adminVisits, setAdminVisits] = useState<FieldVisitRecord[]>([]);
  const [adminStats, setAdminStats] = useState<AdminAttendanceStats | null>(null);
  const [adminLoading, setAdminLoading] = useState(false);
  const [adminSearch, setAdminSearch] = useState('');
  const [adminStatusFilter, setAdminStatusFilter] = useState('');

  // Inspection drawer / Photo preview modal
  const [viewingVisit, setViewingVisit] = useState<FieldVisitRecord | null>(null);

  // Attendance Regularization State
  const [regularizeModal, setRegularizeModal] = useState<{
    isOpen: boolean;
    attendance: AttendanceRecord | null;
    status: 'present' | 'late' | 'half_day' | 'absent';
    punchInTime: string;
    punchOutTime: string;
    reason: string;
    remarks: string;
  }>({
    isOpen: false,
    attendance: null,
    status: 'present',
    punchInTime: '',
    punchOutTime: '',
    reason: 'mispunch',
    remarks: '',
  });
  const [submittingRegularize, setSubmittingRegularize] = useState(false);

  const showToast = (type: 'success' | 'error', message: string) => {
    setToast({ type, message });
    setTimeout(() => setToast(null), 5000);
  };

  // 1. Fetch Today's Attendance
  const loadTodayData = async () => {
    if (!token) return;
    try {
      setLoadingToday(true);
      const res = await apiRequest<AttendanceTodayResponse>('/attendance/today', {}, token);
      setTodayData(res);
      // Also refresh employee personal history
      loadMyHistory();
    } catch (err: any) {
      console.warn('Today attendance fetch note:', err.message);
    } finally {
      setLoadingToday(false);
    }
  };

  // 1b. Fetch Employee Personal Attendance History
  const loadMyHistory = async () => {
    if (!token) return;
    try {
      setLoadingMyHistory(true);
      const res = await apiRequest<{
        success: boolean;
        stats: { total_present: number; total_late: number; total_half_day: number; total_hours: number };
        data: { data: AttendanceRecord[] };
      }>('/attendance/my-history', {}, token);
      setMyHistoryRecords(res.data?.data || []);
      setMyHistoryStats(res.stats || null);
    } catch (err: any) {
      console.warn('My attendance history fetch note:', err.message);
    } finally {
      setLoadingMyHistory(false);
    }
  };

  // 2. Fetch Admin Ledger
  const loadAdminData = async (dateVal = selectedDate) => {
    if (!token) return;
    try {
      setAdminLoading(true);
      let url = `/attendance/admin/records?date=${dateVal}`;
      if (adminStatusFilter) url += `&status=${adminStatusFilter}`;
      if (adminSearch) url += `&search=${encodeURIComponent(adminSearch)}`;

      const res = await apiRequest<{ success: boolean; data: { data: AttendanceRecord[] }; stats: AdminAttendanceStats }>(
        url,
        {},
        token
      );
      setAdminAttendances(res.data?.data || []);
      setAdminStats(res.stats || null);

      // Also load field visits for this date
      const visitsRes = await apiRequest<{ success: boolean; data: { data: FieldVisitRecord[] } }>(
        `/attendance/admin/field-visits?date=${dateVal}`,
        {},
        token
      );
      setAdminVisits(visitsRes.data?.data || []);
    } catch (err: any) {
      showToast('error', err.message || 'Failed to load company attendance ledger');
    } finally {
      setAdminLoading(false);
    }
  };

  const openRegularizeModal = (record: AttendanceRecord) => {
    // Format times cleanly to HH:mm for the HTML <input type="time" />
    const formatTimeForInput = (t?: string | null, fallback = '') => {
      if (!t) return fallback;
      const parts = t.split(':');
      if (parts.length >= 2) {
        return `${parts[0].padStart(2, '0')}:${parts[1].padStart(2, '0')}`;
      }
      return fallback;
    };

    setRegularizeModal({
      isOpen: true,
      attendance: record,
      status: (record.status === 'evaluating' ? 'present' : record.status as any) || 'present',
      punchInTime: formatTimeForInput(record.punch_in_time, '09:30'),
      punchOutTime: formatTimeForInput(record.punch_out_time, '18:30'),
      reason: record.status === 'late' ? 'late_arrival' : 'mispunch',
      remarks: record.regularization_remarks || '',
    });
  };

  const handleSaveRegularize = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!regularizeModal.attendance) return;
    try {
      setSubmittingRegularize(true);
      await apiRequest(
        '/attendance/admin/regularize',
        {
          method: 'POST',
          body: JSON.stringify({
            attendance_id: regularizeModal.attendance.id,
            status: regularizeModal.status,
            punch_in_time: regularizeModal.punchInTime,
            punch_out_time: regularizeModal.punchOutTime,
            regularization_reason: regularizeModal.reason,
            regularization_remarks: regularizeModal.remarks,
          }),
        },
        token || undefined
      );
      showToast('success', 'Attendance record regularized successfully.');
      setRegularizeModal((prev) => ({ ...prev, isOpen: false }));
      loadAdminData(selectedDate);
    } catch (err: any) {
      showToast('error', err.message || 'Failed to regularize attendance record.');
    } finally {
      setSubmittingRegularize(false);
    }
  };

  useEffect(() => {
    // Client-side date and live clock initialization
    const now = new Date();
    const todayStr = now.toISOString().split('T')[0];
    setSelectedDate(todayStr);
    setCurrentDateFormatted(
      now.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      })
    );
    setCurrentTimeFormatted(
      now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    );

    let lastCheckedDate = todayStr;
    const timer = setInterval(() => {
      const liveNow = new Date();
      const currentDay = liveNow.toISOString().split('T')[0];

      // After 12:00 AM (midnight rollover), automatically reset and refresh attendance for the new day
      if (currentDay !== lastCheckedDate) {
        lastCheckedDate = currentDay;
        setSelectedDate(currentDay);
        setCurrentDateFormatted(
          liveNow.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric',
          })
        );
        loadTodayData();
      }

      setCurrentTimeFormatted(
        liveNow.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })
      );
    }, 1000);

    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    loadTodayData();
    if (isSuperadmin) {
      loadAdminData(selectedDate || undefined);
    }
  }, [token, selectedDate]);

  // Request GPS
  const detectGps = (callback?: (coords: { lat: number; lng: number }) => void) => {
    if (!navigator.geolocation) {
      setGpsError('Geolocation is not supported by your browser.');
      return;
    }
    setGpsLoading(true);
    setGpsError(null);

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const coords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
        setUserCoords(coords);
        setGpsLoading(false);
        if (callback) callback(coords);
      },
      (err) => {
        setGpsLoading(false);
        setGpsError(err.message || 'Unable to retrieve your current location.');
        showToast('error', 'Location permission denied or unavailable. Please enable GPS.');
      },
      { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
    );
  };

  // Auto detect GPS on component mount for instant ready state
  useEffect(() => {
    detectGps();
  }, []);

  // Handle Punch In
  const handlePunchIn = async () => {
    if (!token) return;

    const executePunchIn = async (coords: { lat: number; lng: number } | null) => {
      try {
        setActionLoading(true);
        const res = await apiRequest<{ success: boolean; message: string; attendance: AttendanceRecord }>(
          '/attendance/punch-in',
          {
            method: 'POST',
            body: JSON.stringify({
              latitude: coords?.lat,
              longitude: coords?.lng,
            }),
          },
          token
        );
        showToast('success', res.message);
        loadTodayData();
        if (isSuperadmin) loadAdminData();
      } catch (err: any) {
        showToast('error', err.message || 'Failed to punch in');
      } finally {
        setActionLoading(false);
      }
    };

    if (!userCoords) {
      detectGps((coords) => executePunchIn(coords));
    } else {
      executePunchIn(userCoords);
    }
  };

  // Handle Punch Out
  const handlePunchOut = async () => {
    if (!token) return;

    const executePunchOut = async (coords: { lat: number; lng: number } | null) => {
      try {
        setActionLoading(true);
        const res = await apiRequest<{ success: boolean; message: string; attendance: AttendanceRecord }>(
          '/attendance/punch-out',
          {
            method: 'POST',
            body: JSON.stringify({
              latitude: coords?.lat,
              longitude: coords?.lng,
            }),
          },
          token
        );
        showToast('success', res.message);
        loadTodayData();
        if (isSuperadmin) loadAdminData();
      } catch (err: any) {
        showToast('error', err.message || 'Failed to punch out');
      } finally {
        setActionLoading(false);
      }
    };

    if (!userCoords) {
      detectGps((coords) => executePunchOut(coords));
    } else {
      executePunchOut(userCoords);
    }
  };

  // Camera Management for Field Visit
  const startCamera = async () => {
    try {
      setCameraActive(true);
      const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
      });
      streamRef.current = stream;
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
      }
    } catch (err: any) {
      showToast('error', 'Camera access denied or unavailable: ' + err.message);
      setCameraActive(false);
    }
  };

  const stopCamera = () => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach((track) => track.stop());
      streamRef.current = null;
    }
    setCameraActive(false);
  };

  const capturePhoto = () => {
    if (!videoRef.current) return;
    const canvas = document.createElement('canvas');
    canvas.width = videoRef.current.videoWidth || 640;
    canvas.height = videoRef.current.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    if (ctx) {
      ctx.drawImage(videoRef.current, 0, 0, canvas.width, canvas.height);
      const dataUri = canvas.toDataURL('image/jpeg', 0.85);
      setFieldSelfie(dataUri);
      stopCamera();
    }
  };

  const handleOpenFieldVisitModal = () => {
    setShowFieldVisitModal(true);
    setFieldSelfie(null);
    setFieldClient('');
    setFieldRemarks('');
    setFieldLocationName('');
    setFieldAddress('');
    detectGps();
    startCamera();
  };

  const handleCloseFieldVisitModal = () => {
    stopCamera();
    setShowFieldVisitModal(false);
  };

  // Submit Field Visit
  const handleSubmitFieldVisit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return;

    if (!userCoords) {
      showToast('error', 'GPS location is required for field visit verification. Please allow location.');
      detectGps();
      return;
    }

    if (!fieldSelfie) {
      showToast('error', 'Please capture a selfie verification photo.');
      return;
    }

    if (!fieldRemarks.trim()) {
      showToast('error', 'Please enter field visit remarks.');
      return;
    }

    try {
      setSubmittingFieldVisit(true);
      const res = await apiRequest<{ success: boolean; message: string; field_visit: FieldVisitRecord }>(
        '/attendance/field-visit',
        {
          method: 'POST',
          body: JSON.stringify({
            latitude: userCoords.lat,
            longitude: userCoords.lng,
            location_name: fieldLocationName || 'Client Site / Field Visit',
            address: fieldAddress,
            client_name: fieldClient,
            remarks: fieldRemarks,
            selfie_image: fieldSelfie,
          }),
        },
        token || undefined
      );
      showToast('success', res.message);
      handleCloseFieldVisitModal();
      loadTodayData();
      if (isSuperadmin) loadAdminData();
    } catch (err: any) {
      showToast('error', err.message || 'Failed to submit field visit');
    } finally {
      setSubmittingFieldVisit(false);
    }
  };

  const employee = todayData?.employee;
  const attendance = todayData?.attendance;
  const isRandom = employee?.attendance_location_type === 'random';
  const hasPunchedIn = Boolean(attendance?.punch_in_time);
  const hasPunchedOut = Boolean(attendance?.punch_out_time);

  return (
    <ProtectedRoute>
      <AppLayout
        title={
          <div className="flex items-center gap-2 text-xs font-medium text-slate-500">
            <span>Enterprise</span>
            <span>/</span>
            <span className="text-slate-900 font-semibold">Attendance & Field Mobility</span>
          </div>
        }
        action={
          <div className="flex items-center gap-2">
            <button
              onClick={() => {
                loadTodayData();
                if (isSuperadmin) loadAdminData();
              }}
              className="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition cursor-pointer"
              title="Refresh attendance records"
            >
              <RefreshCw className="h-4 w-4" />
            </button>
            <button
              onClick={handleOpenFieldVisitModal}
              className="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 cursor-pointer"
            >
              <Camera className="h-3.5 w-3.5" />
              <span>Record Field Visit</span>
            </button>
          </div>
        }
      >
        <div className="p-4 sm:p-6 lg:p-8 space-y-6">
          {/* Toast Notification */}
          {toast && (
            <div
              className={`p-4 rounded-xl border flex items-center justify-between text-xs sm:text-sm shadow-sm transition ${toast.type === 'success'
                  ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                  : 'bg-red-50 border-red-200 text-red-800'
                }`}
            >
              <div className="flex items-center gap-2.5">
                {toast.type === 'success' ? (
                  <CheckCircle2 className="h-5 w-5 text-emerald-600 shrink-0" />
                ) : (
                  <AlertCircle className="h-5 w-5 text-red-600 shrink-0" />
                )}
                <span className="font-medium">{toast.message}</span>
              </div>
              <button onClick={() => setToast(null)} className="text-slate-400 hover:text-slate-600 cursor-pointer">
                <X className="h-4 w-4" />
              </button>
            </div>
          )}

          {/* Module Nav Tabs (for superadmin vs individual) */}
          <div className="flex items-center justify-between border-b border-slate-200 pb-2">
            <div className="flex items-center gap-2 sm:gap-4 overflow-x-auto">
              <button
                onClick={() => setActiveTab('my_attendance')}
                className={`flex items-center gap-2 pb-2 px-1 text-xs sm:text-sm font-semibold transition border-b-2 cursor-pointer whitespace-nowrap ${activeTab === 'my_attendance'
                    ? 'border-blue-600 text-blue-600'
                    : 'border-transparent text-slate-500 hover:text-slate-900'
                  }`}
              >
                <Clock className="h-4 w-4" />
                <span>My Attendance Clock</span>
              </button>

              {isSuperadmin && (
                <>
                  <button
                    onClick={() => setActiveTab('admin_roster')}
                    className={`flex items-center gap-2 pb-2 px-1 text-xs sm:text-sm font-semibold transition border-b-2 cursor-pointer whitespace-nowrap ${activeTab === 'admin_roster'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-slate-500 hover:text-slate-900'
                      }`}
                  >
                    <Users className="h-4 w-4" />
                    <span>Organization Attendance Roster</span>
                  </button>
                  <button
                    onClick={() => setActiveTab('field_visits_ledger')}
                    className={`flex items-center gap-2 pb-2 px-1 text-xs sm:text-sm font-semibold transition border-b-2 cursor-pointer whitespace-nowrap ${activeTab === 'field_visits_ledger'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-slate-500 hover:text-slate-900'
                      }`}
                  >
                    <Camera className="h-4 w-4" />
                    <span>Field Visits & Selfie Audit</span>
                  </button>
                </>
              )}
            </div>

            {/* GPS Status Indicator Chip */}
            <div className="hidden md:flex items-center gap-2 text-xs">
              <span className="text-slate-400">GPS Sensor:</span>
              {gpsLoading ? (
                <span className="flex items-center gap-1 text-blue-600 font-medium animate-pulse">
                  <Compass className="h-3.5 w-3.5 animate-spin" /> Detecting...
                </span>
              ) : userCoords ? (
                <span className="flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full font-semibold border border-emerald-200">
                  <MapPin className="h-3 w-3 text-emerald-600" />
                  {userCoords.lat.toFixed(4)}, {userCoords.lng.toFixed(4)}
                </span>
              ) : (
                <button
                  onClick={() => detectGps()}
                  className="flex items-center gap-1 text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md hover:underline cursor-pointer"
                >
                  <AlertCircle className="h-3.5 w-3.5 text-amber-600" /> Enable Location
                </button>
              )}
            </div>
          </div>

          {/* ======================= TAB 1: MY ATTENDANCE CLOCK ======================= */}
          {activeTab === 'my_attendance' && (
            !employee ? (
              <div className="bg-white rounded-2xl border border-slate-200 p-8 text-center max-w-xl mx-auto space-y-3 shadow-xs">
                <div className="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto">
                  <ShieldCheck className="w-6 h-6" />
                </div>
                <h3 className="text-base font-bold text-slate-900">Administrator Console Profile</h3>
                <p className="text-xs text-slate-500 leading-relaxed">
                  You are logged in under the system-level <strong>Super Administrator</strong> account. Time-clock punching is reserved for employees registered in the workforce roster.
                </p>
                <div className="pt-2">
                  <button
                    onClick={() => setActiveTab('admin_roster')}
                    className="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition cursor-pointer"
                  >
                    View Organization Attendance Roster
                  </button>
                </div>
              </div>
            ) : (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Primary Punch Clock Card */}
              <div className="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-100">
                  <div>
                    <div className="flex items-center gap-2">
                      <h2 className="text-xl font-bold text-slate-900">Digital Punch Clock</h2>
                      <span
                        className={`px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider ${isRandom
                            ? 'bg-purple-100 text-purple-700'
                            : 'bg-blue-100 text-blue-700'
                          }`}
                      >
                        {isRandom ? 'Random Location' : 'Fixed Geofenced'}
                      </span>
                    </div>
                    <p className="mt-1 text-xs sm:text-sm text-slate-500">
                      {isRandom
                        ? 'Random / On-field mode: Only Punch In is required for attendance.'
                        : `Assigned campus: ${employee?.assigned_location?.name || 'Main Office'} (strict radius verification).`}
                    </p>
                  </div>

                  <div className="text-right">
                    <p className="text-xs font-semibold text-slate-400 uppercase">Today</p>
                    <p className="text-base font-bold text-slate-900">
                      {currentDateFormatted || 'Loading date...'}
                    </p>
                  </div>
                </div>

                {/* Live Clock Display */}
                <div className="rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 text-white p-6 sm:p-8 text-center relative overflow-hidden shadow-inner">
                  <div className="absolute top-0 right-0 p-8 opacity-10">
                    <Clock className="h-40 w-40" />
                  </div>

                  <p className="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-1">
                    System Time
                  </p>
                  <p className="text-3xl sm:text-5xl font-mono font-bold tracking-tight text-white mb-4">
                    {currentTimeFormatted || '--:--:--'}
                  </p>

                  <div className="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs backdrop-blur-xs">
                    <Compass className="h-3.5 w-3.5 text-blue-400" />
                    <span>
                      Shift: {employee?.shift ? `${employee.shift.name} (${employee.shift.start_time} - ${employee.shift.end_time})` : 'Regular Day Shift (09:00 - 18:00)'}
                    </span>
                  </div>
                </div>

                {/* Punch In / Out Action Buttons */}
                <div className="space-y-4 pt-2">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {/* Punch In Card */}
                    <div
                      className={`p-5 rounded-2xl border transition ${hasPunchedIn
                          ? 'border-emerald-200 bg-emerald-50/40'
                          : 'border-slate-200 bg-white hover:border-blue-300'
                        }`}
                    >
                      <div className="flex items-center justify-between mb-3">
                        <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Punch In</span>
                        {hasPunchedIn ? (
                          <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 px-2 py-0.5 text-[11px] font-bold">
                            <CheckCircle2 className="h-3 w-3" /> Recorded
                          </span>
                        ) : (
                          <span className="rounded-full bg-slate-100 text-slate-500 px-2 py-0.5 text-[11px] font-semibold">
                            Pending
                          </span>
                        )}
                      </div>

                      {hasPunchedIn ? (
                        <div className="space-y-1">
                          <p className="text-2xl font-bold font-mono text-emerald-900">
                            {attendance?.punch_in_time}
                          </p>
                          <p className="text-xs text-emerald-700">
                            Status: <strong className="uppercase">{attendance?.punch_in_status}</strong>
                            {attendance?.punch_in_distance_meters != null && (
                              <span className="ml-1">({attendance.punch_in_distance_meters}m from campus)</span>
                            )}
                          </p>
                        </div>
                      ) : (
                        <button
                          onClick={handlePunchIn}
                          disabled={actionLoading}
                          className="w-full flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60 cursor-pointer"
                        >
                          <Clock className="h-4 w-4" />
                          <span>{actionLoading ? 'Verifying GPS...' : 'Punch In Now'}</span>
                        </button>
                      )}
                    </div>

                    {/* Punch Out Card (Disabled or Hidden if Random) */}
                    <div
                      className={`p-5 rounded-2xl border transition ${isRandom
                          ? 'border-slate-200 bg-slate-50 opacity-60'
                          : hasPunchedOut
                            ? 'border-emerald-200 bg-emerald-50/40'
                            : 'border-slate-200 bg-white'
                        }`}
                    >
                      <div className="flex items-center justify-between mb-3">
                        <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Punch Out</span>
                        {isRandom ? (
                          <span className="rounded-full bg-purple-100 text-purple-700 px-2 py-0.5 text-[10px] font-bold">
                            Not Required
                          </span>
                        ) : hasPunchedOut ? (
                          <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 px-2 py-0.5 text-[11px] font-bold">
                            <CheckCircle2 className="h-3 w-3" /> Completed
                          </span>
                        ) : (
                          <span className="rounded-full bg-slate-100 text-slate-500 px-2 py-0.5 text-[11px] font-semibold">
                            Pending
                          </span>
                        )}
                      </div>

                      {isRandom ? (
                        <p className="text-xs text-slate-500 italic py-2">
                          Your profile is set to Random Location. Only Punch In is required for full-day attendance credit.
                        </p>
                      ) : (
                        <div className="space-y-3">
                          {hasPunchedOut && (
                            <div className="space-y-1">
                              <p className="text-2xl font-bold font-mono text-emerald-900">
                                {attendance?.punch_out_time}
                              </p>
                              <p className="text-xs text-emerald-700">
                                Total Hours: <strong>{attendance?.total_working_hours} hrs</strong>
                              </p>
                            </div>
                          )}
                          <button
                            onClick={handlePunchOut}
                            disabled={!hasPunchedIn || actionLoading}
                            className={`w-full flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition disabled:opacity-50 cursor-pointer ${
                              hasPunchedOut
                                ? 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300'
                                : 'bg-slate-900 hover:bg-slate-800 text-white shadow-sm'
                            }`}
                          >
                            <ArrowRight className="h-4 w-4" />
                            <span>
                              {actionLoading
                                ? 'Validating...'
                                : hasPunchedOut
                                ? 'Update Punch Out Time'
                                : 'Punch Out Now'}
                            </span>
                          </button>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Geofence & Rules Disclaimer */}
                  <div className="rounded-xl bg-slate-50 border border-slate-200/80 p-3.5 flex items-start gap-3 text-xs text-slate-600">
                    <Info className="h-4 w-4 text-blue-600 shrink-0 mt-0.5" />
                    <div>
                      {isRandom ? (
                        <p>
                          <strong>Random Location Mode:</strong> Geofencing is relaxed. Punch in records your live coordinates without distance gating, and no punch out is needed.
                        </p>
                      ) : (
                        <p>
                          <strong>Fixed Geofenced Mode:</strong> Must punch in within{' '}
                          <strong>{employee?.assigned_location?.radius_meters || 100}m</strong> of{' '}
                          <strong>{employee?.assigned_location?.name || 'Assigned Location'}</strong>.
                        </p>
                      )}
                    </div>
                  </div>
                </div>
              </div>

              {/* Sidebar Info & Today's Field Visits Card */}
              <div className="space-y-6">
                {/* Profile Snapshot */}
                <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
                  <div className="flex items-center gap-3">
                    <div className="h-12 w-12 rounded-xl bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-lg">
                      {employee?.name?.[0]?.toUpperCase() || 'E'}
                    </div>
                    <div>
                      <h3 className="text-sm font-bold text-slate-900">{employee?.name}</h3>
                      <p className="text-xs text-slate-500 font-mono">{employee?.employee_id}</p>
                    </div>
                  </div>

                  <div className="space-y-2 pt-3 border-t border-slate-100 text-xs">
                    <div className="flex justify-between py-1">
                      <span className="text-slate-500">Location Rule:</span>
                      <span className="font-semibold text-slate-900 capitalize">
                        {employee?.attendance_location_type || 'Fixed'}
                      </span>
                    </div>
                    <div className="flex justify-between py-1">
                      <span className="text-slate-500">Assigned Campus:</span>
                      <span className="font-semibold text-slate-900 truncate max-w-[160px]">
                        {employee?.assigned_location?.name || 'Company HQ'}
                      </span>
                    </div>
                    <div className="flex justify-between py-1">
                      <span className="text-slate-500">Shift Grace Period:</span>
                      <span className="font-semibold text-slate-900">
                        {employee?.shift?.grace_period_minutes || 15} minutes
                      </span>
                    </div>
                  </div>

                  <button
                    onClick={handleOpenFieldVisitModal}
                    className="w-full flex items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/60 hover:bg-indigo-50 p-2.5 text-xs font-bold text-indigo-700 transition cursor-pointer"
                  >
                    <Camera className="h-4 w-4" />
                    <span>Record Field Visit with Selfie</span>
                  </button>
                </div>

                {/* Today's Field Visits List */}
                <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-3">
                  <div className="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h4 className="text-xs font-bold uppercase tracking-wider text-slate-800">
                      Today's Field Visits ({todayData?.field_visits?.length || 0})
                    </h4>
                    <span className="text-[10px] text-slate-400">GPS & Selfie Verified</span>
                  </div>

                  {todayData?.field_visits && todayData.field_visits.length > 0 ? (
                    <div className="space-y-2.5 max-h-72 overflow-y-auto">
                      {todayData.field_visits.map((fv) => (
                        <div
                          key={fv.id}
                          onClick={() => setViewingVisit(fv)}
                          className="p-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs cursor-pointer transition flex items-center justify-between gap-3"
                        >
                          <div className="min-w-0">
                            <p className="font-bold text-slate-800 truncate">{fv.location_name || fv.client_name || 'Field Site'}</p>
                            <p className="text-[11px] text-slate-500 truncate">{fv.remarks}</p>
                            <span className="text-[10px] text-slate-400 font-mono">{fv.visit_time}</span>
                          </div>
                          {fv.selfie_image && (
                            <img
                              src={fv.selfie_image}
                              alt="Selfie"
                              className="h-10 w-10 rounded-lg object-cover border border-slate-300 shrink-0"
                            />
                          )}
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-xs text-slate-400 py-4 text-center">No field visits recorded yet today.</p>
                  )}
                </div>
              </div>

              {/* Personal Monthly Attendance History Ledger */}
              <div className="lg:col-span-3 space-y-4 pt-4 border-t border-slate-200">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  <div>
                    <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                      <Calendar className="w-4 h-4 text-blue-600" />
                      My Personal Attendance History
                    </h3>
                    <p className="text-xs text-slate-500">
                      Your historical daily punch logs, shift working hours, and punctuality status records.
                    </p>
                  </div>

                  {myHistoryStats && (
                    <div className="flex items-center gap-2 text-xs">
                      <span className="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-bold border border-emerald-100">
                        {myHistoryStats.total_present} Present
                      </span>
                      <span className="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 font-bold border border-amber-100">
                        {myHistoryStats.total_late} Late
                      </span>
                      <span className="px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 font-bold border border-orange-100">
                        {myHistoryStats.total_half_day} Half Day
                      </span>
                      <span className="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold border border-blue-100 font-mono">
                        {myHistoryStats.total_hours} hrs Worked
                      </span>
                    </div>
                  )}
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs border-collapse">
                      <thead>
                        <tr className="border-b border-slate-200 bg-slate-50/75 text-slate-500 uppercase font-semibold text-[11px]">
                          <th className="py-3 px-4">Date</th>
                          <th className="py-3 px-4">Shift Mode</th>
                          <th className="py-3 px-4">Punch In</th>
                          <th className="py-3 px-4">Punch Out</th>
                          <th className="py-3 px-4">Total Working</th>
                          <th className="py-3 px-4">Location / Radius</th>
                          <th className="py-3 px-4">Final Status</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {loadingMyHistory ? (
                          <tr>
                            <td colSpan={7} className="py-10 text-center text-slate-400">
                              <RefreshCw className="h-5 w-5 animate-spin mx-auto mb-2 text-blue-600" />
                              Loading your attendance history...
                            </td>
                          </tr>
                        ) : myHistoryRecords.length === 0 ? (
                          <tr>
                            <td colSpan={7} className="py-10 text-center text-slate-400">
                              No past attendance logs found for your profile.
                            </td>
                          </tr>
                        ) : (
                          myHistoryRecords.map((item) => (
                            <tr key={item.id} className="hover:bg-slate-50/70 transition">
                              <td className="py-3 px-4 font-bold text-slate-900 font-mono">
                                {new Date(item.date).toLocaleDateString('en-US', {
                                  weekday: 'short',
                                  month: 'short',
                                  day: 'numeric',
                                  year: 'numeric',
                                })}
                              </td>
                              <td className="py-3 px-4">
                                <span
                                  className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                                    item.location_type === 'random'
                                      ? 'bg-purple-50 text-purple-700 border border-purple-200'
                                      : 'bg-blue-50 text-blue-700 border border-blue-200'
                                  }`}
                                >
                                  {item.location_type}
                                </span>
                              </td>
                              <td className="py-3 px-4 font-mono font-bold text-slate-900">
                                {item.punch_in_time || '--:--'}
                              </td>
                              <td className="py-3 px-4 font-mono text-slate-700">
                                {item.punch_out_time || (
                                  <span className="text-amber-600 font-semibold italic">Ongoing...</span>
                                )}
                              </td>
                              <td className="py-3 px-4 font-mono font-semibold text-slate-900">
                                {item.total_working_hours > 0 ? `${item.total_working_hours} hrs` : '-'}
                              </td>
                              <td className="py-3 px-4">
                                {item.punch_in_latitude && item.punch_in_longitude ? (
                                  <span className="inline-flex items-center gap-1 text-emerald-700 text-[11px]">
                                    <MapPin className="h-3 w-3" />
                                    {item.punch_in_location?.name || 'Verified GPS'}
                                  </span>
                                ) : (
                                  <span className="text-slate-400 text-[11px]">Direct Punch</span>
                                )}
                              </td>
                              <td className="py-3 px-4">
                                <div className="flex items-center gap-1.5">
                                  <span
                                    className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                                      item.status === 'present'
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : item.status === 'evaluating'
                                        ? 'bg-blue-100 text-blue-800 animate-pulse'
                                        : item.status === 'half_day'
                                        ? 'bg-orange-100 text-orange-800'
                                        : item.status === 'late'
                                        ? 'bg-amber-100 text-amber-800'
                                        : 'bg-red-100 text-red-800'
                                    }`}
                                  >
                                    {item.status}
                                  </span>
                                  {item.is_regularized && (
                                    <span
                                      title={item.regularization_remarks || ''}
                                      className="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200"
                                    >
                                      Regularized
                                    </span>
                                  )}
                                </div>
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            )
          )}

          {/* ======================= TAB 2: ORGANIZATION ATTENDANCE ROSTER (SUPERADMIN) ======================= */}
          {activeTab === 'admin_roster' && (
            <div className="space-y-6">
              {/* Summary Stats Grid */}
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                  <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Present Today</p>
                  <p className="mt-1 text-2xl font-bold text-emerald-600">{adminStats?.total_present ?? 0}</p>
                  <p className="text-[11px] text-slate-500 mt-1">Punched In</p>
                </div>
                <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                  <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Late Arrivals</p>
                  <p className="mt-1 text-2xl font-bold text-amber-600">{adminStats?.total_late ?? 0}</p>
                  <p className="text-[11px] text-slate-500 mt-1">Exceeded Grace</p>
                </div>
                <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                  <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Random Location</p>
                  <p className="mt-1 text-2xl font-bold text-purple-600">{adminStats?.total_random_punches ?? 0}</p>
                  <p className="text-[11px] text-slate-500 mt-1">Field / Flexible</p>
                </div>
                <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                  <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Field Visits</p>
                  <p className="mt-1 text-2xl font-bold text-indigo-600">{adminStats?.total_field_visits ?? 0}</p>
                  <p className="text-[11px] text-slate-500 mt-1">Selfie Verified</p>
                </div>
              </div>

              {/* Filters & Date Selector Bar */}
              <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div className="flex flex-wrap items-center gap-3">
                  <div className="flex items-center gap-1.5 border border-slate-200 rounded-xl px-3 py-1.5 bg-slate-50 text-xs">
                    <Calendar className="h-4 w-4 text-slate-400" />
                    <input
                      type="date"
                      value={selectedDate}
                      onChange={(e) => {
                        setSelectedDate(e.target.value);
                        loadAdminData(e.target.value);
                      }}
                      className="bg-transparent font-medium text-slate-900 focus:outline-none"
                    />
                  </div>

                  <select
                    value={adminStatusFilter}
                    onChange={(e) => {
                      setAdminStatusFilter(e.target.value);
                      loadAdminData();
                    }}
                    className="border border-slate-200 rounded-xl px-3 py-1.5 bg-slate-50 text-xs text-slate-800 font-medium focus:outline-none"
                  >
                    <option value="">All Attendance Statuses</option>
                    <option value="present">Present (On-time)</option>
                    <option value="late">Late Arrival</option>
                    <option value="half_day">Half Day</option>
                  </select>
                </div>

                <div className="relative">
                  <Search className="absolute left-3 top-2.5 h-3.5 w-3.5 text-slate-400" />
                  <input
                    type="text"
                    value={adminSearch}
                    onChange={(e) => setAdminSearch(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && loadAdminData()}
                    placeholder="Search employee name, ID..."
                    className="w-full sm:w-64 border border-slate-200 rounded-xl py-1.5 pl-9 pr-3 text-xs bg-slate-50 text-slate-800 focus:bg-white focus:outline-none"
                  />
                </div>
              </div>

              {/* Attendance Table */}
              <div className="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-xs border-collapse">
                    <thead>
                      <tr className="border-b border-slate-200 bg-slate-50/75 text-slate-500 uppercase font-semibold">
                        <th className="py-3 px-4">Employee</th>
                        <th className="py-3 px-4">Mode</th>
                        <th className="py-3 px-4">Punch In</th>
                        <th className="py-3 px-4">Punch Out</th>
                        <th className="py-3 px-4">Total Working</th>
                        <th className="py-3 px-4">GPS Verification</th>
                        <th className="py-3 px-4">Status</th>
                        <th className="py-3 px-4 text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {adminLoading ? (
                        <tr>
                          <td colSpan={8} className="py-12 text-center text-slate-400">
                            <RefreshCw className="h-6 w-6 animate-spin mx-auto mb-2 text-blue-600" />
                            Loading employee records...
                          </td>
                        </tr>
                      ) : adminAttendances.length === 0 ? (
                        <tr>
                          <td colSpan={8} className="py-12 text-center text-slate-400">
                            No attendance punches recorded for {selectedDate}.
                          </td>
                        </tr>
                      ) : (
                        adminAttendances.map((rec) => (
                          <tr key={rec.id} className="hover:bg-slate-50/70 transition">
                            <td className="py-3 px-4">
                              <div className="flex items-center gap-2.5">
                                <div className="h-8 w-8 rounded-lg bg-blue-50 text-blue-700 font-bold flex items-center justify-center shrink-0">
                                  {rec.employee?.first_name?.[0]?.toUpperCase() || 'E'}
                                </div>
                                <div>
                                  <p className="font-bold text-slate-900">
                                    {rec.employee?.first_name} {rec.employee?.last_name}
                                  </p>
                                  <p className="text-[11px] text-slate-500 font-mono">{rec.employee?.employee_id}</p>
                                </div>
                              </div>
                            </td>
                            <td className="py-3 px-4">
                              <span
                                className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${rec.location_type === 'random'
                                    ? 'bg-purple-50 text-purple-700 border border-purple-200'
                                    : 'bg-blue-50 text-blue-700 border border-blue-200'
                                  }`}
                              >
                                {rec.location_type}
                              </span>
                            </td>
                            <td className="py-3 px-4 font-mono font-bold text-slate-900">
                              {rec.punch_in_time}
                            </td>
                            <td className="py-3 px-4 font-mono text-slate-700">
                              {rec.location_type === 'random' ? (
                                <span className="text-slate-400 italic">Not required</span>
                              ) : (
                                rec.punch_out_time || <span className="text-amber-600 font-semibold">Active...</span>
                              )}
                            </td>
                            <td className="py-3 px-4 font-semibold text-slate-900">
                              {rec.total_working_hours > 0 ? `${rec.total_working_hours} hrs` : '-'}
                            </td>
                            <td className="py-3 px-4">
                              {rec.punch_in_latitude && rec.punch_in_longitude ? (
                                <div className="space-y-0.5 text-[11px]">
                                  <span className="inline-flex items-center gap-1 text-emerald-700">
                                    <MapPin className="h-3 w-3" />
                                    {rec.punch_in_location?.name || 'Live GPS'}
                                  </span>
                                  {rec.punch_in_distance_meters != null && (
                                    <p className="text-slate-400">{rec.punch_in_distance_meters}m radius</p>
                                  )}
                                </div>
                              ) : (
                                <span className="text-slate-400">Random coords</span>
                              )}
                            </td>
                            <td className="py-3 px-4">
                              <div className="flex flex-col gap-1 items-start">
                                <span
                                  className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                                    rec.status === 'present'
                                      ? 'bg-emerald-100 text-emerald-800'
                                      : rec.status === 'evaluating'
                                      ? 'bg-blue-100 text-blue-800 animate-pulse'
                                      : rec.status === 'half_day'
                                      ? 'bg-orange-100 text-orange-800'
                                      : rec.status === 'late'
                                      ? 'bg-amber-100 text-amber-800'
                                      : 'bg-red-100 text-red-800'
                                  }`}
                                >
                                  {rec.status}
                                </span>
                                {rec.is_regularized && (
                                  <span
                                    title={rec.regularization_remarks || rec.regularization_reason || ''}
                                    className="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200"
                                  >
                                    Regularized
                                  </span>
                                )}
                              </div>
                            </td>
                            <td className="py-3 px-4 text-right">
                              <button
                                onClick={() => openRegularizeModal(rec)}
                                className="px-2.5 py-1 text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition cursor-pointer inline-flex items-center gap-1"
                              >
                                Regularize
                              </button>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {/* ======================= TAB 3: FIELD VISITS & SELFIE AUDIT (SUPERADMIN) ======================= */}
          {activeTab === 'field_visits_ledger' && (
            <div className="space-y-6">
              <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                  <h3 className="text-sm font-bold text-slate-900">Field Visits & Mobile Selfies Audit</h3>
                  <p className="text-xs text-slate-500">
                    Live GPS location stamp and photo verification submitted by on-duty field employees.
                  </p>
                </div>
                <div className="flex items-center gap-2 border border-slate-200 rounded-xl px-3 py-1.5 bg-slate-50 text-xs">
                  <Calendar className="h-4 w-4 text-slate-400" />
                  <input
                    type="date"
                    value={selectedDate}
                    onChange={(e) => {
                      setSelectedDate(e.target.value);
                      loadAdminData(e.target.value);
                    }}
                    className="bg-transparent font-medium text-slate-900 focus:outline-none"
                  />
                </div>
              </div>

              {/* Grid of Field Visit Cards */}
              {adminVisits.length === 0 ? (
                <div className="p-12 text-center bg-white rounded-2xl border border-slate-200 text-slate-400">
                  <Camera className="h-8 w-8 mx-auto mb-2 text-slate-300" />
                  No field visits submitted on {selectedDate}.
                </div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                  {adminVisits.map((v) => (
                    <div
                      key={v.id}
                      className="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs hover:shadow-md transition space-y-3 cursor-pointer"
                      onClick={() => setViewingVisit(v)}
                    >
                      <div className="relative rounded-xl overflow-hidden bg-slate-900 h-48 border border-slate-100">
                        <img
                          src={v.selfie_image}
                          alt="Field Selfie"
                          className="w-full h-full object-cover"
                        />
                        <div className="absolute bottom-2 left-2 right-2 bg-slate-900/80 backdrop-blur-xs text-white p-2 rounded-lg text-[11px] flex items-center justify-between">
                          <span className="flex items-center gap-1">
                            <Clock className="h-3 w-3 text-blue-400" />
                            {v.visit_time}
                          </span>
                          <span className="flex items-center gap-1 font-mono text-[10px] text-emerald-400">
                            <MapPin className="h-3 w-3" />
                            {v.latitude.toFixed(4)}, {v.longitude.toFixed(4)}
                          </span>
                        </div>
                      </div>

                      <div>
                        <div className="flex items-center justify-between">
                          <h4 className="text-sm font-bold text-slate-900 truncate">
                            {v.client_name || v.location_name || 'Field Client Site'}
                          </h4>
                          <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">
                            {v.status}
                          </span>
                        </div>
                        <p className="text-xs font-semibold text-blue-600 mt-0.5">
                          {v.employee?.first_name} {v.employee?.last_name} ({v.employee?.employee_id})
                        </p>
                        <p className="mt-2 text-xs text-slate-600 line-clamp-2 bg-slate-50 p-2 rounded-lg border border-slate-100">
                          "{v.remarks}"
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* ======================= MODAL: RECORD FIELD VISIT ======================= */}
          {showFieldVisitModal && (
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs overflow-y-auto">
              <div className="relative w-full max-w-lg my-8 rounded-2xl bg-white border border-slate-200 shadow-2xl p-6 space-y-4">
                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                  <div className="flex items-center gap-2">
                    <div className="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                      <Camera className="h-5 w-5" />
                    </div>
                    <div>
                      <h3 className="text-base font-bold text-slate-900">Record Field Visit</h3>
                      <p className="text-xs text-slate-500">Selfie verification + GPS coordinates</p>
                    </div>
                  </div>
                  <button onClick={handleCloseFieldVisitModal} className="text-slate-400 hover:text-slate-700 cursor-pointer">
                    <X className="h-5 w-5" />
                  </button>
                </div>

                <form onSubmit={handleSubmitFieldVisit} className="space-y-4">
                  {/* Camera / Selfie Section */}
                  <div>
                    <label className="block text-xs font-bold uppercase text-slate-700 mb-1">
                      Selfie Verification Photo *
                    </label>

                    {fieldSelfie ? (
                      <div className="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 h-56 flex items-center justify-center">
                        <img src={fieldSelfie} alt="Captured Selfie" className="w-full h-full object-cover" />
                        <button
                          type="button"
                          onClick={() => {
                            setFieldSelfie(null);
                            startCamera();
                          }}
                          className="absolute bottom-3 right-3 rounded-xl bg-white/90 backdrop-blur-xs px-3 py-1.5 text-xs font-bold text-slate-800 shadow-md hover:bg-white cursor-pointer"
                        >
                          Retake Photo
                        </button>
                      </div>
                    ) : (
                      <div className="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 h-56 flex flex-col items-center justify-center">
                        {cameraActive ? (
                          <>
                            <video
                              ref={videoRef}
                              autoPlay
                              playsInline
                              muted
                              className="w-full h-full object-cover"
                            />
                            <button
                              type="button"
                              onClick={capturePhoto}
                              className="absolute bottom-3 rounded-full bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-lg hover:bg-indigo-700 flex items-center gap-2 cursor-pointer"
                            >
                              <Camera className="h-4 w-4" />
                              <span>Take Photo</span>
                            </button>
                          </>
                        ) : (
                          <div className="text-center p-4">
                            <Camera className="h-8 w-8 text-slate-400 mx-auto mb-2" />
                            <button
                              type="button"
                              onClick={startCamera}
                              className="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 cursor-pointer"
                            >
                              Enable Camera
                            </button>
                          </div>
                        )}
                      </div>
                    )}
                  </div>

                  {/* GPS Coordinates Bar */}
                  <div className="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
                    <span className="text-slate-500 flex items-center gap-1.5">
                      <MapPin className="h-4 w-4 text-emerald-600" />
                      GPS Stamping:
                    </span>
                    {userCoords ? (
                      <span className="font-mono font-bold text-slate-800">
                        {userCoords.lat.toFixed(5)}, {userCoords.lng.toFixed(5)}
                      </span>
                    ) : (
                      <button
                        type="button"
                        onClick={() => detectGps()}
                        className="text-blue-600 font-semibold hover:underline"
                      >
                        Detect Location
                      </button>
                    )}
                  </div>

                  {/* Client & Location */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700">Client / Site Name</label>
                      <input
                        type="text"
                        value={fieldClient}
                        onChange={(e) => setFieldClient(e.target.value)}
                        placeholder="e.g. Acme Corp / Branch 2"
                        className="mt-1 block w-full rounded-xl border border-slate-300 py-2 px-3 text-xs focus:border-indigo-600 focus:outline-none"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-slate-700">Location Area</label>
                      <input
                        type="text"
                        value={fieldLocationName}
                        onChange={(e) => setFieldLocationName(e.target.value)}
                        placeholder="e.g. Industrial Park Sector 5"
                        className="mt-1 block w-full rounded-xl border border-slate-300 py-2 px-3 text-xs focus:border-indigo-600 focus:outline-none"
                      />
                    </div>
                  </div>

                  {/* Remarks */}
                  <div>
                    <label className="block text-xs font-bold uppercase text-slate-700">Visit Remarks & Purpose *</label>
                    <textarea
                      required
                      rows={3}
                      value={fieldRemarks}
                      onChange={(e) => setFieldRemarks(e.target.value)}
                      placeholder="Describe the client meeting, inspection results, or task accomplished..."
                      className="mt-1 block w-full rounded-xl border border-slate-300 py-2 px-3 text-xs focus:border-indigo-600 focus:outline-none"
                    />
                  </div>

                  <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button
                      type="button"
                      onClick={handleCloseFieldVisitModal}
                      className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      disabled={submittingFieldVisit || !fieldSelfie}
                      className="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 cursor-pointer"
                    >
                      {submittingFieldVisit ? 'Submitting Record...' : 'Save & Submit Visit'}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}

          {/* ======================= MODAL: VIEW VISIT DETAILS ======================= */}
          {viewingVisit && (
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
              <div className="relative w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-2xl p-6 space-y-4">
                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                  <h3 className="text-base font-bold text-slate-900">Field Visit Audit</h3>
                  <button onClick={() => setViewingVisit(null)} className="text-slate-400 hover:text-slate-700 cursor-pointer">
                    <X className="h-5 w-5" />
                  </button>
                </div>

                <div className="rounded-xl overflow-hidden border border-slate-200 bg-slate-900 h-64">
                  <img src={viewingVisit.selfie_image} alt="Selfie" className="w-full h-full object-cover" />
                </div>

                <div className="space-y-2 text-xs">
                  <div className="flex justify-between py-1 border-b border-slate-100">
                    <span className="text-slate-500">Employee:</span>
                    <span className="font-semibold text-slate-900">
                      {viewingVisit.employee?.first_name} {viewingVisit.employee?.last_name} ({viewingVisit.employee?.employee_id})
                    </span>
                  </div>
                  <div className="flex justify-between py-1 border-b border-slate-100">
                    <span className="text-slate-500">Timestamp:</span>
                    <span className="font-mono text-slate-800">{viewingVisit.date} at {viewingVisit.visit_time}</span>
                  </div>
                  <div className="flex justify-between py-1 border-b border-slate-100">
                    <span className="text-slate-500">GPS Coordinates:</span>
                    <span className="font-mono font-bold text-emerald-600">
                      {viewingVisit.latitude.toFixed(6)}, {viewingVisit.longitude.toFixed(6)}
                    </span>
                  </div>
                  <div className="py-1">
                    <span className="text-slate-500 block mb-1">Remarks:</span>
                    <div className="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 font-medium">
                      {viewingVisit.remarks}
                    </div>
                  </div>
                </div>

                <div className="pt-2 flex justify-end">
                  <button
                    onClick={() => setViewingVisit(null)}
                    className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
                  >
                    Close
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* ======================= MODAL: REGULARIZE ATTENDANCE ======================= */}
          {regularizeModal.isOpen && regularizeModal.attendance && (
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
              <div className="relative w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-hidden">
                <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                  <div>
                    <h3 className="font-bold text-sm text-slate-900">Regularize Attendance</h3>
                    <p className="text-[11px] text-slate-500">
                      {regularizeModal.attendance.employee?.first_name} {regularizeModal.attendance.employee?.last_name} &bull; {regularizeModal.attendance.date}
                    </p>
                  </div>
                  <button
                    onClick={() => setRegularizeModal((prev) => ({ ...prev, isOpen: false }))}
                    className="text-slate-400 hover:text-slate-600 cursor-pointer"
                  >
                    <X className="w-5 h-5" />
                  </button>
                </div>

                <form onSubmit={handleSaveRegularize} className="p-5 space-y-4">
                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      Adjustment Status *
                    </label>
                    <select
                      value={regularizeModal.status}
                      onChange={(e) => setRegularizeModal((prev) => ({ ...prev, status: e.target.value as any }))}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none"
                    >
                      <option value="present">Present (Full Day)</option>
                      <option value="late">Late Arrival</option>
                      <option value="half_day">Half Day</option>
                      <option value="absent">Absent</option>
                    </select>
                  </div>

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                        Punch In Time
                      </label>
                      <input
                        type="time"
                        value={regularizeModal.punchInTime}
                        onChange={(e) => setRegularizeModal((prev) => ({ ...prev, punchInTime: e.target.value }))}
                        className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                        Punch Out Time
                      </label>
                      <input
                        type="time"
                        value={regularizeModal.punchOutTime}
                        onChange={(e) => setRegularizeModal((prev) => ({ ...prev, punchOutTime: e.target.value }))}
                        className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      Regularization Reason *
                    </label>
                    <select
                      value={regularizeModal.reason}
                      onChange={(e) => setRegularizeModal((prev) => ({ ...prev, reason: e.target.value }))}
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none"
                    >
                      <option value="mispunch">Mispunch / Missed Punch Out</option>
                      <option value="late_arrival">Approved Late Arrival / Traffic</option>
                      <option value="outdoor_duty">Outdoor Duty / Client Site Work</option>
                      <option value="biometric_failure">Device / Geofence Glitch</option>
                      <option value="other">Other Official Reason</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold uppercase text-slate-700 mb-1">
                      Admin Remarks & Note *
                    </label>
                    <textarea
                      required
                      rows={2}
                      value={regularizeModal.remarks}
                      onChange={(e) => setRegularizeModal((prev) => ({ ...prev, remarks: e.target.value }))}
                      placeholder="e.g. Regularized per manager verbal approval and verified gate pass."
                      className="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 resize-none focus:bg-white focus:outline-none"
                    />
                  </div>

                  <div className="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button
                      type="button"
                      onClick={() => setRegularizeModal((prev) => ({ ...prev, isOpen: false }))}
                      className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      disabled={submittingRegularize}
                      className="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50"
                    >
                      {submittingRegularize ? 'Saving Regularization...' : 'Apply Regularization'}
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

