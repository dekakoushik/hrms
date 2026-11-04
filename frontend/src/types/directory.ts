export interface DirectoryEmployee {
  id: number;
  user_id: number;
  company_id?: number | null;
  department_id?: number | null;
  designation_id?: number | null;
  location_id?: number | null;
  shift_id?: number | null;
  reporting_manager_id?: number | null;
  employee_id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string | null;
  gender?: string | null;
  date_of_joining?: string | null;
  employment_type?: string | null;
  attendance_location_type?: string | null;
  status: string;
  department?: {
    id: number;
    name: string;
  };
  designation?: {
    id: number;
    title: string;
  };
  company?: {
    id: number;
    name: string;
  };
  location?: {
    id: number;
    name: string;
    city?: string | null;
    state?: string | null;
  };
  shift?: {
    id: number;
    name: string;
    start_time?: string | null;
    end_time?: string | null;
  };
  reporting_manager?: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    employee_id: string;
  };
}

export interface DirectoryResponse {
  count: number;
  employees: DirectoryEmployee[];
}

