import assert from "node:assert/strict";
import test from "node:test";
import { validatePublicUrl } from "../src/ssrf.js";

test("rejects non-http schemes", async () => {
  await assert.rejects(() => validatePublicUrl("file:///etc/passwd"), /UNSUPPORTED_SCHEME/);
});

test("rejects localhost", async () => {
  await assert.rejects(() => validatePublicUrl("http://localhost:8080"), /BLOCKED_DESTINATION/);
});

test("rejects loopback IPv4", async () => {
  await assert.rejects(() => validatePublicUrl("http://127.0.0.1:8080"), /BLOCKED_DESTINATION/);
});

test("rejects cloud metadata hostname", async () => {
  await assert.rejects(() => validatePublicUrl("http://metadata.google.internal"), /BLOCKED_DESTINATION/);
});