export interface ItTicketReply {
  id: number;
  it_ticket_id: number;
  user_id: number;
  message: string;
  attachment_path?: string | null;
  attachment_url?: string | null;
  created_at: string;
  user?: {
    id: number;
    name: string;
    email: string;
    role: string;
  };
}

export interface ItTicket {
  id: number;
  ticket_number: string;
  employee_id: number;
  created_by_user_id: number;
  category: 'hardware' | 'software' | 'network' | 'access_credentials' | 'email' | 'printer' | 'other';
  priority: 'low' | 'medium' | 'high' | 'critical';
  status: 'open' | 'in_progress' | 'resolved' | 'closed';
  subject: string;
  description: string;
  attachment_path?: string | null;
  attachment_url?: string | null;
  assigned_to_user_id?: number | null;
  resolution_notes?: string | null;
  resolved_at?: string | null;
  created_at: string;
  updated_at: string;
  replies_count?: number;
  employee?: {
    id: number;
    employee_code: string;
    name: string;
    department?: {
      id: number;
      name: string;
    };
    designation?: {
      id: number;
      title: string;
    };
  };
  creator?: {
    id: number;
    name: string;
    email: string;
  };
  assignee?: {
    id: number;
    name: string;
    email: string;
  };
  replies?: ItTicketReply[];
}

export interface TicketStats {
  open: number;
  in_progress: number;
  resolved: number;
  total: number;
}

export interface TicketsIndexResponse {
  stats: TicketStats;
  tickets: ItTicket[];
}

