# RADAR Security Guardian

Read `/docs/SECURITY.md` before changing external HTTP, Website Watch, authentication, authorization, or API behavior.

Website Watch must never access localhost, `127.0.0.0/8`, `::1`, RFC1918 networks, private IPv6, link-local addresses, cloud metadata, Docker internal services, internal hostnames, or internal network services.

Allow only HTTP and HTTPS. Validate URL syntax and canonicalization, resolve DNS, validate every resolved IP, protect against DNS rebinding, revalidate every redirect, validate the final destination, enforce redirect limits, response-size limits, content-type checks, and request timeouts.

Never implement authentication bypass, CAPTCHA bypass, credential guessing, private-page scraping, access-control bypass, anti-bot evasion, or stealth crawling. Never weaken security merely to make tests pass. Do not expose secrets, tokens, credentials, internal URLs, sensitive headers, or stack traces.