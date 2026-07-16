import type { UserRole, UserStatus } from '@/types/auth'

export type AuditAction = 'user.access_updated'

export interface AuditActor {
  id: number
  name: string
  email: string
}

export interface AuditSubject {
  type: string
  id: number | string | null
  name: string | null
  email: string | null
}

export interface AuditValues {
  role?: UserRole
  status?: UserStatus
  approved_by?: number | null
  approved_at?: string | null
  blocked_at?: string | null
  [key: string]: unknown
}

export interface AuditMetadata {
  actor_name?: string
  actor_email?: string
  changed_fields?: string[]
  [key: string]: unknown
}

export interface AuditLog {
  id: number
  action: AuditAction
  action_label: string
  actor: AuditActor | null
  subject: AuditSubject
  old_values: AuditValues | null
  new_values: AuditValues | null
  metadata: AuditMetadata | null
  ip_address: string | null
  user_agent: string | null
  created_at: string | null
}

export interface AuditFilters {
  search?: string
  action?: AuditAction
  actor_id?: number
  user_id?: number
  from?: string
  to?: string
  per_page?: 15 | 25 | 50
  page?: number
}

export interface AuditPagination {
  current_page: number
  data: AuditLog[]
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}
