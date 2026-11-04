export interface Qualification {
  degree: string;
  institution: string;
  year_of_passing: string;
  grade_or_percentage?: string;
}

export interface SalaryBreakdown {
  basic_salary: number;
  da_amount: number;
  hra_amount: number;
  ca_amount: number;
  ea_amount: number;
  sa_amount: number;
  gross_salary: number;
  gratuity_amount: number;
  employee_pf: number;
  employee_esic: number;
  employer_esic: number;
  monthly_tax: number;
  total_deductions: number;
  net_salary: number;
  ctc_amount: number;
  enable_epf: boolean;
}

export interface Employee {
  id: number;
  user_id?: number | null;
  company_id: number;
  department_id?: number | null;
  designation_id?: number | null;
  shift_id?: number | null;
  location_id?: number | null;
  reporting_manager_id?: number | null;
  employee_id: string;
  usertype: 'user' | 'admin';
  date_of_joining: string;
  employment_type: string;
  probation_duration: string;
  attendance_location_type: 'fixed' | 'random';
  first_name: string;
  last_name?: string;
  email: string;
  phone?: string;
  date_of_birth?: string;
  gender?: 'Male' | 'Female' | 'Other';
  marital_status?: 'Single' | 'Married' | 'Divorced' | 'Widowed';
  blood_group?: string;
  current_address?: string;
  permanent_address?: string;
  emergency_contact_name?: string;
  emergency_contact_relation?: string;
  emergency_contact_phone?: string;
  pan_number?: string;
  aadhaar_number?: string;
  uan_number?: string;
  esic_number?: string;
  passport_number?: string;
  bank_name?: string;
  bank_account_number?: string;
  bank_ifsc_code?: string;
  bank_branch?: string;
  qualifications?: Qualification[];
  basic_salary: number;
  monthly_tax: number;
  enable_epf: boolean;
  da_amount: number;
  hra_amount: number;
  ca_amount: number;
  ea_amount: number;
  sa_amount: number;
  gross_salary: number;
  gratuity_amount: number;
  employee_pf: number;
  employee_esic: number;
  employer_esic: number;
  total_deductions: number;
  ctc_amount: number;
  net_salary: number;
  status: 'active' | 'probation' | 'notice' | 'terminated' | 'resigned';
  created_at?: string;
  company?: { id: number; name: string };
  department?: { id: number; name: string };
  designation?: { id: number; name: string };
  shift?: { id: number; name: string; start_time: string; end_time: string };
  location?: { id: number; name: string; campus_name?: string; city: string; country: string };
  reporting_manager?: { id: number; first_name: string; last_name?: string; employee_id: string };
}

export interface SettingsOverviewData {
  companies: Array<{ id: number; name: string }>;
  departments: Array<{ id: number; name: string }>;
  designations: Array<{ id: number; name: string }>;
  shifts: Array<{ id: number; name: string; start_time: string; end_time: string }>;
  locations: Array<{ id: number; name?: string; campus_name?: string; city: string; country: string }>;
}

/**
 * Client-side calculation mirror matching exact backend formula
 */
export function calculateSalaryClient(
  basic: number,
  monthlyTax: number,
  enableEpf: boolean
): SalaryBreakdown {
  const basicSalary = Number(basic) || 0;
  const da = Math.round(basicSalary * 0.20 * 100) / 100;
  const hra = Math.round(basicSalary * 0.60 * 100) / 100;
  const ca = Math.round(basicSalary * 0.20 * 100) / 100;
  const ea = Math.round(basicSalary * 0.08 * 100) / 100;
  const sa = Math.round(basicSalary * 0.20 * 100) / 100;

  const gross = Math.round((basicSalary + da + hra + ca + ea + sa) * 100) / 100;
  const gratuity = Math.round((basicSalary + da) * 0.0481 * 100) / 100;

  let employeePf = 0;
  if (enableEpf) {
    const pfCalc = (basicSalary + da) * 0.12;
    employeePf = Math.round(Math.min(pfCalc, 3000) * 100) / 100;
  }

  let employeeEsic = 0;
  let employerEsic = 0;
  if (gross <= 21000) {
    employeeEsic = Math.round(gross * 0.0075 * 100) / 100;
    employerEsic = Math.round(gross * 0.0325 * 100) / 100;
  }

  const tax = Math.round(Math.max(0, Number(monthlyTax) || 0) * 100) / 100;
  const totalDeductions = Math.round((employeePf + employeeEsic + tax) * 100) / 100;
  const netSalary = Math.round((gross - totalDeductions) * 100) / 100;
  const ctc = Math.round((gross + gratuity + employerEsic + employeePf) * 100) / 100;

  return {
    basic_salary: basicSalary,
    da_amount: da,
    hra_amount: hra,
    ca_amount: ca,
    ea_amount: ea,
    sa_amount: sa,
    gross_salary: gross,
    gratuity_amount: gratuity,
    employee_pf: employeePf,
    employee_esic: employeeEsic,
    employer_esic: employerEsic,
    monthly_tax: tax,
    total_deductions: totalDeductions,
    net_salary: netSalary,
    ctc_amount: ctc,
    enable_epf: enableEpf,
  };
}
