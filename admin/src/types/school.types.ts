export interface SchoolRequest {
  id: string;
  school_name: string;
  firstname: string;
  lastname: string;
  email: string;
  phone: string;
  status: 'PENDING' | 'APPROVED' | 'REJECTED';
  created_at: string;
}

export interface PaginatedSchoolRequests {
  data: SchoolRequest[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
