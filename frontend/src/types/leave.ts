export interface LeaveType {
  id: number;
  company_id?: number | null;
  name: string;
  code: string;
  description?: string | null;
  default_days_per_year: number;
  is_paid: boolean;
  requires_approval: boolean;
  is_active: boolean;
}

export interface LeaveBalance {
  id: number;
  employee_id: number;
  leave_type_id: number;
  year: number;
  allocated_days: number;
  used_days: number;
  pending_days: number;
  remaining_days: number;
  leave_type?: LeaveType;
}

export interface LeaveApplication {
  id: number;
  employee_id: number;
  leave_type_id: number;
  start_date: string;
  end_date: string;
  total_days: number;
  is_half_day: boolean;
  half_day_type?: 'first_half' | 'second_half' | null;
  reason: string;
  status: 'pending' | 'approved' | 'rejected' | 'cancelled';
  action_by_user_id?: number | null;
  admin_remarks?: string | null;
  action_at?: string | null;
  created_at: string;
  employee?: {
    id: number;
    first_name: string;
    last_name?: string | null;
    employee_id: string;
    email: string;
    department?: { name: string };
    designation?: { name: string };
  };
  leave_type?: LeaveType;
  action_by_user?: {
    id: number;
    name: string;
    email: string;
  };
}

export interface Holiday {
  id: number;
  company_id?: number | null;
  title: string;
  date: string;
  day_name?: string | null;
  type: 'gazetted' | 'restricted' | 'optional' | 'company';
  description?: string | null;
  year: number;
  is_active: boolean;
}

export interface MyLeaveOverviewResponse {
  employee: {
    id: number;
    name: string;
    employee_id: string;
  } | null;
  current_year: number;
  balances: LeaveBalance[];
  recent_applications: LeaveApplication[];
  upcoming_holidays: Holiday[];
}

export interface AdminLeaveStats {
  pending_count: number;
  approved_this_month: number;
  rejected_count: number;
  active_employees: number;
}

