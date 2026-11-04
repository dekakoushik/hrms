export interface AttendanceLocation {
  id: number;
  name: string;
  code?: string;
  address?: string;
  city?: string;
  latitude: number;
  longitude: number;
  radius_meters: number;
  enforce_geofence: boolean;
}

export interface ShiftInfo {
  id: number;
  name: string;
  start_time: string;
  end_time: string;
  grace_period_minutes: number;
  half_day_minutes: number;
  full_day_minutes: number;
  is_night_shift: boolean;
}

export interface AttendanceRecord {
  id: number;
  employee_id: number;
  date: string;
  punch_in_time: string;
  punch_in_latitude?: number | null;
  punch_in_longitude?: number | null;
  punch_in_location_id?: number | null;
  punch_in_distance_meters?: number | null;
  punch_in_status: 'present' | 'late' | 'half_day';
  punch_in_note?: string | null;
  punch_out_time?: string | null;
  punch_out_latitude?: number | null;
  punch_out_longitude?: number | null;
  punch_out_location_id?: number | null;
  punch_out_distance_meters?: number | null;
  punch_out_note?: string | null;
  total_working_hours: number;
  status: 'present' | 'late' | 'half_day' | 'absent' | 'evaluating';
  location_type: 'fixed' | 'random';
  is_regularized?: boolean;
  regularization_reason?: string | null;
  regularization_remarks?: string | null;
  regularized_by_user_id?: number | null;
  regularized_at?: string | null;
  regularized_by_user?: {
    id: number;
    name: string;
    email: string;
  } | null;
  punch_in_location?: AttendanceLocation | null;
  punch_out_location?: AttendanceLocation | null;
  employee?: {
    id: number;
    employee_id: string;
    first_name: string;
    last_name?: string;
    full_name?: string;
    email: string;
    company?: { id: number; name: string };
    department?: { id: number; name: string };
    designation?: { id: number; name: string };
    shift?: ShiftInfo;
  };
}

export interface FieldVisitRecord {
  id: number;
  employee_id: number;
  date: string;
  visit_time: string;
  latitude: number;
  longitude: number;
  location_name?: string;
  address?: string;
  client_name?: string;
  remarks: string;
  selfie_image: string;
  status: 'submitted' | 'approved' | 'rejected';
  admin_feedback?: string | null;
  created_at: string;
  employee?: {
    id: number;
    employee_id: string;
    first_name: string;
    last_name?: string;
    full_name?: string;
    email: string;
    department?: { id: number; name: string };
    company?: { id: number; name: string };
  };
}

export interface AttendanceTodayResponse {
  success: boolean;
  employee: {
    id: number;
    employee_id: string;
    name: string;
    email: string;
    usertype: string;
    attendance_location_type: 'fixed' | 'random';
    shift?: ShiftInfo | null;
    assigned_location?: AttendanceLocation | null;
  };
  attendance: AttendanceRecord | null;
  field_visits: FieldVisitRecord[];
  server_time: string;
}

export interface AdminAttendanceStats {
  total_present: number;
  total_late: number;
  total_random_punches: number;
  total_field_visits: number;
}

