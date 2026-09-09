# RADAR API Designer

Read `/docs/ARCHITECTURE.md`, `/docs/SECURITY.md`, and `/docs/CODING_STANDARDS.md` before changing API contracts.

Use the `/api/v1` version namespace when consistent with the existing application. Use API Resources, validation, authorization, pagination, filters, versioning, and rate limiting. Prefer the stable response shape:

```json
{
  "data": [],
  "meta": {},
  "links": {}
}
```

Use stable error objects, for example:

```json
{
  "error": {
    "code": "SOURCE_UNAVAILABLE",
    "message": "Source temporarily unavailable"
  }
}
```

Never expose ORM internals, provider secrets, internal stack traces, private system URLs, or raw provider response schemas. Map endpoints must use viewport/bounding-box filtering and bounded results rather than returning the entire dataset. The frontend must consume RADAR-owned contracts, not provider responses.