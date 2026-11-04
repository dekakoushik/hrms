export interface PayrollPeriod {
  year: number;
  month: number;
  period_start: string;
  period_end: string;
  calendar_days: number;
  sundays_count: number;
  holidays_count: number;
  holidays_list?: { title: string; date: string }[];
}

export interface PayrollSummary {
  total_employees: number;
  total_gross_payout: number;
  total_deductions: number;
  total_net_payout: number;
}

export interface EmployeePayrollItem {
  employee_id: number;
  employee_code: string;
  employee_name: string;
  department: string;
  designation: string;
  calendar_days: number;
  sundays_count: number;
  holidays_count: number;
  present_days: number;
  paid_leave_days: number;
  unpaid_leave_days: number;
  half_days: number;
  late_days_count: number;
  late_penalty_half_days: number;
  absent_days: number;
  total_payable_days: number;

  basic_salary: number;
  da_amount: number;
  hra_amount: number;
  ca_amount: number;
  ea_amount: number;
  sa_amount: number;
  gross_salary: number;

  earned_basic: number;
  earned_gross: number;

  employee_pf: number;
  employee_esic: number;
  monthly_tax: number;
  total_deductions: number;
  net_salary: number;
}

export interface PayrollCalculateResponse {
  success: boolean;
  period: PayrollPeriod;
  summary: PayrollSummary;
  payroll_id?: number | null;
  data: EmployeePayrollItem[];
}

