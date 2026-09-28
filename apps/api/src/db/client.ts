import { drizzle } from 'drizzle-orm/postgres-js'
import postgres from 'postgres'
import * as schema from './schema'

let client: ReturnType<typeof postgres> | null = null

export function getDb(databaseUrl: string) {
  if (!client) {
    client = postgres(databaseUrl, {
      prepare: false,
      max: 1,
    })
  }
  return drizzle(client, { schema })
}

export type Database = ReturnType<typeof getDb>
