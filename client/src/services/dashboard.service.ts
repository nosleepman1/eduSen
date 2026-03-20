import axios from '../api/axios';

export interface DashboardStats {
  counts: {
    students: number;
    teachers: number;
    personnel: number;
    classes: number;
    monthly_payments: number;
  };
  recent_activities: any[];
  charts: {
    enrollments: {
      labels: string[];
      data: number[];
    };
    payments: {
      labels: string[];
      data: number[];
    };
  };
}

export const dashboardService = {
  getStats: async () => {
    const response = await axios.get<{ data: DashboardStats }>('/dashboard/stats');
    return response.data.data;
  },
};
