import { chromium } from "playwright";
import Fastify from "fastify";
import { BrowserCrawlRequest, BrowserCrawlResult } from "./contract.js";
import { validatePublicUrl } from "./ssrf.js";
import { readFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";

const app = Fastify({ logger: true });

app.get("/health", async () => ({ service: "radar-browser-worker", status: "ok" }));

app.post("/v1/crawl", async (request, reply) => {
  const parsed = BrowserCrawlRequest.safeParse(request.body);
  if (!parsed.success) return reply.code(400).send({
    error: "INVALID_REQUEST",
    issues: parsed.error.issues.map((issue) => ({ path: issue.path, code: issue.code })),
  });

  const input = parsed.data;
  try {
    const fixtureMode = input.mode === "fixture";
    if (fixtureMode && process.env.BROWSER_FIXTURES_ENABLED !== "true") {
      return reply.code(403).send({ request_id: input.request_id, success: false, error_code: "FIXTURES_DISABLED" });
    }
    if (fixtureMode && !input.fixture) {
      return reply.code(400).send({ request_id: input.request_id, success: false, error_code: "FIXTURE_REQUIRED" });
    }
    const url = fixtureMode ? null : await validatePublicUrl(input.url);
    const browser = await chromium.launch({ headless: true });
    try {
      const page = await browser.newPage();
      await page.route("**/*", async (route) => {
        try {
          await validatePublicUrl(route.request().url());
          await route.continue();
        } catch {
          await route.abort("blockedbyclient");
        }
      });
      if (fixtureMode) {
        const fixturePath = fileURLToPath(new URL(`../fixtures/${input.fixture}.html`, import.meta.url));
        await page.setContent(await readFile(fixturePath, "utf8"), { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(100);
      } else {
        await page.goto(url!.toString(), { waitUntil: "domcontentloaded", timeout: input.timeout_ms });
        await validatePublicUrl(page.url());
      }
      const result = BrowserCrawlResult.parse({
        request_id: input.request_id,
        success: true,
        final_url: fixtureMode ? `https://radar.test/fixtures/${input.fixture}` : page.url(),
        title: await page.title(),
        text: await page.locator("body").innerText({ timeout: input.timeout_ms }),
        links: await page.locator("a[href]").evaluateAll((nodes) => nodes.map((node) => (node as HTMLAnchorElement).href).filter(Boolean)),
        documents: await page.locator("a[href]").evaluateAll((nodes) => nodes.map((node) => (node as HTMLAnchorElement).href).filter((href) => /\.(pdf|docx?|xlsx?|csv)(\?|$)/i.test(href))),
        metadata: {},
      });
      return result;
    } finally {
      await browser.close();
    }
  } catch (error) {
    return reply.code(422).send({
      request_id: input.request_id,
      success: false,
      links: [],
      documents: [],
      metadata: {},
      error_code: error instanceof Error ? error.message : "CRAWL_FAILED",
    });
  }
});

app.listen({ port: Number(process.env.PORT ?? 3001), host: "0.0.0.0" });