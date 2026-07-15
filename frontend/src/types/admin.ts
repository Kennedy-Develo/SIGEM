import type { UserRole, UserStatus } from '@/types/auth'

export interface UserApprover {
  id: number
  name: string
}

export interface ManagedUser {
  id: number
  name: string
  email: string
  role: UserRole
  status: UserStatus
  approved_by: number | null
  approved_at: string | null
  blocked_at: string | null
  last_login_at: string | null
  created_at: string
  approver: UserApprover | null
}

export interface UserFilters {
  search?: string
  role?: UserRole
  status?: UserStatus
  page?: number
}

export interface UserPagination {
  current_page: number
  data: ManagedUser[]
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}

export interface UpdateUserAccess {
  role: UserRole
  status: Extract<UserStatus, 'active' | 'blocked'>
}

export interface UpdateUserAccessResponse {
  message: string
  user: ManagedUser
}
