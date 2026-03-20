import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { Classroom, ClassroomFormPayload } from '@/types/classroom.types';

export const classroomService = {
  list(page = 1) {
    return api.get<ApiEnvelope<Classroom[]>>('/academic/classrooms', { params: { page } });
  },

  create(payload: ClassroomFormPayload) {
    return api.post<ApiEnvelope<Classroom>>('/academic/classrooms', payload);
  },

  update(id: string, payload: Partial<Omit<ClassroomFormPayload, 'level_id'>>) {
    return api.put<ApiEnvelope<Classroom>>(`/academic/classrooms/${id}`, payload);
  },

  open(id: string) {
    return api.patch<ApiEnvelope<Classroom>>(`/academic/classrooms/${id}/open`);
  },

  close(id: string) {
    return api.patch<ApiEnvelope<Classroom>>(`/academic/classrooms/${id}/close`);
  },

  /** null main_teacher_id unassigns — the server accepts nullable here. */
  assignPrincipalTeacher(id: string, mainTeacherId: string | null) {
    return api.patch<ApiEnvelope<Classroom>>(`/academic/classrooms/${id}/principal-teacher`, {
      main_teacher_id: mainTeacherId,
    });
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/classrooms/${id}`);
  },
};
