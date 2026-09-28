import type { Role, User } from '@stockku/shared'

export interface Bindings {
  ENVIRONMENT: string
  SUPABASE_URL: string
  SUPABASE_ANON_KEY: string
  SUPABASE_SERVICE_ROLE_KEY?: string
  DATABASE_URL: string
}

export interface Variables {
  userId: string
  userRole: Role
  user: User
}
