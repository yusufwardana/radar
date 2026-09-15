import { z } from "zod";

export const BrowserCrawlRequest = z.object({
  request_id: z.string().uuid(),
  source_id: z.number().int().positive(),
  url: z.string().url(),
  mode: z.enum(["extract", "fixture"]),
  fixture: z.enum(["static", "dynamic"]).optional(),
  selectors: z.record(z.string()).default({}),
  timeout_ms: z.number().int().min(1000).max(30000).default(20000),
});

export type BrowserCrawlRequest = z.infer<typeof BrowserCrawlRequest>;

export const BrowserCrawlResult = z.object({
  request_id: z.string().uuid(),
  success: z.boolean(),
  final_url: z.string().url().optional(),
  title: z.string().optional(),
  text: z.string().optional(),
  links: z.array(z.string().url()).default([]),
  documents: z.array(z.string().url()).default([]),
  metadata: z.record(z.unknown()).default({}),
  error_code: z.string().optional(),
});

export type BrowserCrawlResult = z.infer<typeof BrowserCrawlResult>;