import api from '../api/axios';

export const schoolService = {
  async saveBasicInfo(data: FormData) {
    const response = await api.post('/school/setup/basic-info', data, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  async saveContactInfo(data: Record<string, any>) {
    const response = await api.post('/school/setup/contact-info', data);
    return response.data;
  },

  async completeSetup() {
    const response = await api.post('/school/setup/complete');
    return response.data;
  }
};
