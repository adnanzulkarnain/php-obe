import api from './api';
import type { ApiResponse, CPMK } from '../types/api';

export const cpmkService = {
  // Get all CPMK
  getAll: (params?: { kode_mk?: string; id_kurikulum?: number }) => {
    return api.get<ApiResponse<CPMK[]>>('/cpmk', { params });
  },

  // Get single CPMK by ID
  getById: (id: number) => {
    return api.get<ApiResponse<CPMK>>(`/cpmk/${id}`);
  },

  // Create new CPMK
  create: (data: {
    kode_mk: string;
    id_kurikulum: number;
    kode_cpmk: string;
    deskripsi: string;
  }) => {
    return api.post<ApiResponse<CPMK>>('/cpmk', data);
  },

  // Update existing CPMK
  update: (id: number, data: {
    kode_cpmk?: string;
    deskripsi?: string;
  }) => {
    return api.put<ApiResponse<CPMK>>(`/cpmk/${id}`, data);
  },

  // Delete CPMK
  delete: (id: number) => {
    return api.delete<ApiResponse<void>>(`/cpmk/${id}`);
  },

  // Get SubCPMK
  getSubCPMK: (id_cpmk: number) => {
    return api.get<ApiResponse<any[]>>(`/cpmk/${id_cpmk}/subcpmk`);
  },

  // Create SubCPMK
  createSubCPMK: (id_cpmk: number, data: {
    kode_subcpmk: string;
    deskripsi: string;
  }) => {
    return api.post<ApiResponse<any>>(`/cpmk/${id_cpmk}/subcpmk`, data);
  },

  // Update SubCPMK
  updateSubCPMK: (id: number, data: {
    kode_subcpmk?: string;
    deskripsi?: string;
  }) => {
    return api.put<ApiResponse<any>>(`/subcpmk/${id}`, data);
  },

  // Delete SubCPMK
  deleteSubCPMK: (id: number) => {
    return api.delete<ApiResponse<void>>(`/subcpmk/${id}`);
  },

  // Get CPL Mappings
  getCPLMappings: (id_cpmk: number) => {
    return api.get<ApiResponse<any[]>>(`/cpmk/${id_cpmk}/cpl-mappings`);
  },

  // Map CPMK to CPL
  mapToCPL: (id_cpmk: number, data: {
    id_cpl: number;
    bobot: number;
  }) => {
    return api.post<ApiResponse<any>>(`/cpmk/${id_cpmk}/map-cpl`, data);
  },

  // Delete CPL Mapping
  deleteMapping: (id: number) => {
    return api.delete<ApiResponse<void>>(`/cpmk-cpl-mapping/${id}`);
  },
};
