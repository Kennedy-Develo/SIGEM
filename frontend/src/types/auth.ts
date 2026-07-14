export type UserRole = 'administrator' | 'manager' | 'operator' | 'reader'

export type UserStatus = 'pending' | 'active' | 'blocked'

export interface AuthUser {
  id: number
  name: string
  email: string
  role: UserRole
  status: UserStatus
  email_verified_at: string | null
  approved_at: string | null
  blocked_at: string | null
  last_login_at: string | null
  created_at: string
  updated_at: string
}

export interface LoginCredentials {
  email: string
  password: string
  remember: boolean
}

export interface LoginResponse {
  message: string
  user: AuthUser
}

export interface RegisterCredentials {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface RegisterResponse {
  message: string
  user: Pick<AuthUser, 'id' | 'name' | 'email' | 'role' | 'status'>
}
