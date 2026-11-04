export interface EmployeeDashboardStats {
  role: 'employee';
  employee: {
    id: number;
    name: string;
    employee_code: string;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string | null;
    designation?: {
      id: number;
      title: string;
    };
    department?: {
      id: number;
      name: string;
    };
    shift?: {
      name: string;
      start_time: string;
      end_time: string;
    };
    location?: {
      name: string;
    };
  } | null;
  today_attendance: {
    id: number;
    date: string;
    punch_in_time: string | null;
    punch_out_time: string | null;
    status: string;
    total_working_hours: number;
    is_late: boolean;
    late_minutes: number;
  } | null;
  month_stats: {
    present_days: number;
    late_days: number;
    half_days: number;
    total_working_hours: number;
    month_name: string;
  };
  leave_balances: {
    id: number;
    leave_type_id: number;
    allocated_days: number;
    used_days: number;
    pending_days: number;
    remaining_days: number;
    leave_type?: {
      id: number;
      name: string;
      code: string;
      is_paid: boolean;
    };
  }[];
  pending_leaves_count: number;
  my_tickets: {
    id: number;
    ticket_number: string;
    subject: string;
    category: string;
    priority: string;
    status: string;
    created_at: string;
    resolution_notes?: string | null;
  }[];
  my_tickets_stats: {
    open: number;
    in_progress: number;
    resolved: number;
  };
  upcoming_holidays: {
    id: number;
    title: string;
    date: string;
    type: string;
    description?: string | null;
  }[];
  recent_history: {
    id: number;
    date: string;
    punch_in_time: string | null;
    punch_out_time: string | null;
    status: string;
    total_working_hours: number;
    is_late: boolean;
  }[];
  latest_payslip?: {
    id: number;
    calendar_days: number;
    payable_days: number;
    net_salary: number;
    status: string;
    payroll?: {
      month: number;
      year: number;
    };
  } | null;
  notices?: {
    id: number;
    title: string;
    content: string;
    priority: 'normal' | 'important' | 'urgent';
    target_audience: string;
    publish_date?: string | null;
    is_pinned: boolean;
    created_at: string;
    creator?: {
      id: number;
      name: string;
      role: string;
    };
    department?: {
      id: number;
      name: string;
    } | null;
  }[];
}

export interface AdminDashboardStats {
  role: 'admin';
  workforce: {
    total_employees: number;
    active_employees: number;
    probation_employees: number;
  };
  attendance_today: {
    present: number;
    late: number;
    evaluating: number;
    half_day: number;
    attendance_rate: number;
  };
  leaves: {
    pending_leaves: number;
    approved_this_month: number;
  };
  it_helpdesk: {
    open: number;
    in_progress: number;
    critical: number;
  };
  payroll: {
    disbursement_estimate: number;
    processed_records: number;
    month_name: string;
  };
  compliance: {
    active_documents: number;
  };
  department_distribution: {
    id: number;
    name: string;
    employees_count: number;
  }[];
  recent_attendance: {
    id: number;
    employee_id: number;
    date: string;
    punch_in_time: string | null;
    punch_out_time: string | null;
    status: string;
    total_working_hours: number;
    is_late: boolean;
    employee?: {
      id: number;
      name: string;
      employee_code: string;
      department?: {
        name: string;
      };
      designation?: {
        title: string;
      };
    };
  }[];
  weekly_trend: {
    day: string;
    date: string;
    present: number;
    late: number;
  }[];
  notices?: {
    id: number;
    title: string;
    content: string;
    priority: 'normal' | 'important' | 'urgent';
    target_audience: string;
    publish_date?: string | null;
    is_pinned: boolean;
    created_at: string;
    creator?: {
      id: number;
      name: string;
      role: string;
    };
    department?: {
      id: number;
      name: string;
    } | null;
  }[];
}

export type DashboardResponse = AdminDashboardStats | EmployeeDashboardStats;
