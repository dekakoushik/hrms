export interface DocumentFolder {
  id: number;
  company_id?: number | null;
  name: string;
  slug: string;
  description?: string | null;
  icon?: string;
  created_by_user_id?: number | null;
  documents_count?: number;
  created_at: string;
  updated_at: string;
}

export interface CompanyDocument {
  id: number;
  folder_id: number;
  company_id?: number | null;
  title: string;
  description?: string | null;
  file_path: string;
  file_name: string;
  file_size: number;
  file_type?: string | null;
  version: string;
  is_active: boolean;
  uploaded_by_user_id?: number | null;
  created_at: string;
  updated_at: string;
  file_url?: string;
  formatted_file_size?: string;
  folder?: {
    id: number;
    name: string;
    slug: string;
    icon?: string;
  };
  uploader?: {
    id: number;
    name: string;
    email: string;
  };
}

