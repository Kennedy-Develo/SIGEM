import type { UserRole } from '@/types/auth'

export type ManifestationSource = 'fala_br' | 'sei'

export type ManifestationType =
  | 'access_to_information'
  | 'communication'
  | 'denunciation'
  | 'praise'
  | 'complaint'
  | 'simplify'
  | 'request'
  | 'suggestion'

export type ManifestationStatus = 'registered' | 'in_progress' | 'completed' | 'archived'

export type ManifestationDeadlineStatus = 'overdue' | 'today' | 'next_7_days'

export type ManifestationSort = 'nup' | 'opened_at' | 'current_deadline_at' | 'created_at'

export type SortDirection = 'asc' | 'desc'

export interface CatalogOption<T extends string = string> {
  value: T
  label: string
}

export interface ManifestationStatusOption extends CatalogOption<ManifestationStatus> {
  is_final: boolean
}

export interface ManifestationSubsubjectOption {
  id: number
  name: string
}

export interface ManifestationSubjectOption {
  id: number
  name: string
  subsubjects: ManifestationSubsubjectOption[]
}

export interface ManifestationSectorOption {
  id: number
  acronym: string
  name: string
  label: string
}

export interface ManifestationAssigneeOption {
  id: number
  name: string
  email: string
  role: UserRole
  role_label: string
}

export interface ManifestationCatalogs {
  sources: CatalogOption<ManifestationSource>[]
  types: CatalogOption<ManifestationType>[]
  statuses: ManifestationStatusOption[]
  subjects: ManifestationSubjectOption[]
  sectors: ManifestationSectorOption[]
  assignees: ManifestationAssigneeOption[]
}

export interface ManifestationSubject {
  id: number
  name: string
}

export interface ManifestationSubsubject {
  id: number
  subject_id: number
  name: string
}

export interface ManifestationSector {
  id: number
  acronym: string
  name: string
}

export interface ManifestationAssignee {
  id: number
  name: string
  email: string
  role: UserRole
}

export interface Manifestation {
  id: number
  nup: string
  source: ManifestationSource
  type: ManifestationType
  status: ManifestationStatus
  subject_id: number
  subsubject_id: number
  sector_id: number
  conclusion_responsible_area: string | null
  current_assignee_id: number | null
  created_by_id: number
  updated_by_id: number | null
  summary: string | null
  description: string | null
  opened_at: string
  original_deadline_at: string | null
  current_deadline_at: string | null
  extended_at: string | null
  extension_reason: string | null
  forwarded_to_external_agency_at: string | null
  external_agency: string | null
  answered_by_ombudsman_at: string | null
  completed_at: string | null
  archived_at: string | null
  created_at: string
  updated_at: string
  subject: ManifestationSubject
  subsubject: ManifestationSubsubject
  sector: ManifestationSector
  current_assignee: ManifestationAssignee | null
}

export interface ManifestationIndicators {
  overdue: number
  due_today: number
  due_next_7_days: number
  extended: number
  completed: number
}

export interface ManifestationFilters {
  search?: string
  source?: ManifestationSource
  type?: ManifestationType
  status?: ManifestationStatus
  subject_id?: number
  subsubject_id?: number
  sector_id?: number
  current_assignee_id?: number
  opened_from?: string
  opened_to?: string
  deadline_from?: string
  deadline_to?: string
  deadline_status?: ManifestationDeadlineStatus
  is_extended?: boolean
  is_forwarded?: boolean
  is_answered_by_ombudsman?: boolean
  sort_by?: ManifestationSort
  sort_direction?: SortDirection
  per_page?: 10 | 15 | 25 | 50 | 100
  page?: number
}

export interface ManifestationPagination {
  current_page: number
  data: Manifestation[]
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}

export interface ManifestationListResponse {
  manifestations: ManifestationPagination
  indicators: ManifestationIndicators
}

export interface StoreManifestationPayload {
  nup: string
  source: ManifestationSource
  type: ManifestationType
  subject_id: number
  subsubject_id: number
  sector_id: number
  conclusion_responsible_area?: string | null
  current_assignee_id?: number | null
  summary?: string | null
  description?: string | null
  opened_at: string
  original_deadline_at?: string | null
  current_deadline_at?: string | null
}

export interface StoreManifestationResponse {
  message: string
  manifestation: Manifestation
}
