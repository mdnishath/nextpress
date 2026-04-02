import { apiPost, apiPut, apiDelete } from './useApi';
import type { Section } from '../types/builder';

/**
 * API calls for section CRUD operations.
 * These are called by the save flow, not directly by components.
 */

export async function apiAddSection(pageId: number, data: Partial<Section>): Promise<Section> {
  return apiPost<Section>(`/pages/${pageId}/sections`, data as Record<string, unknown>);
}

export async function apiUpdateContent(sectionId: string, content: Record<string, unknown>) {
  return apiPut(`/sections/${sectionId}/content`, content);
}

export async function apiUpdateStyle(sectionId: string, style: Record<string, unknown>) {
  return apiPut(`/sections/${sectionId}/style`, style);
}

export async function apiUpdateVariant(sectionId: string, variantId: string) {
  return apiPut(`/sections/${sectionId}/variant`, { variant_id: variantId });
}

export async function apiUpdateLayout(sectionId: string, layout: Record<string, unknown>) {
  return apiPut(`/sections/${sectionId}/layout`, layout);
}

export async function apiToggleSection(sectionId: string) {
  return apiPut(`/sections/${sectionId}/toggle`, {});
}

export async function apiMoveSection(sectionId: string, parentId: string | null, sortOrder: number) {
  return apiPut(`/sections/${sectionId}/move`, { parent_id: parentId, sort_order: sortOrder });
}

export async function apiReorderSections(pageId: number, orderedIds: string[]) {
  return apiPost(`/pages/${pageId}/sections/reorder`, { order: orderedIds });
}

export async function apiDuplicateSection(sectionId: string): Promise<Section> {
  return apiPost<Section>(`/sections/${sectionId}/duplicate`, {});
}

export async function apiDeleteSection(sectionId: string) {
  return apiDelete(`/sections/${sectionId}`);
}
