import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { Bulletin, GenerateBulletinPayload, GenerateClassBulletinsPayload } from '@/types/bulletin.types';

export const bulletinService = {
  list(page = 1) {
    return api.get<ApiEnvelope<Bulletin[]>>('/academic/bulletins', { params: { page } });
  },

  generate(payload: GenerateBulletinPayload) {
    return api.post<ApiEnvelope<Bulletin>>('/academic/bulletins/generate', payload);
  },

  generateClass(payload: GenerateClassBulletinsPayload) {
    return api.post<ApiEnvelope<null>>('/academic/bulletins/generate-class', payload);
  },

  validate(id: string, classCouncilComment?: string | null) {
    return api.patch<ApiEnvelope<Bulletin>>(`/academic/bulletins/${id}/validate`, {
      class_council_comment: classCouncilComment ?? null,
    });
  },

  publish(id: string) {
    return api.patch<ApiEnvelope<Bulletin>>(`/academic/bulletins/${id}/publish`);
  },

  lock(id: string) {
    return api.patch<ApiEnvelope<Bulletin>>(`/academic/bulletins/${id}/lock`);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/bulletins/${id}`);
  },
};
