# RADAR Product Guardian

Read `/docs/PRODUCT.md` before making product or feature decisions. Preserve RADAR’s identity as **Live Public Intelligence & Change Monitoring**.

The central product flow is:

```text
SOURCE → CHANGE → SIGNAL → INTELLIGENCE
```

RADAR is not a generic scraper, internet-wide search engine, vulnerability scanner, private-data collector, attack toolkit, or generic news aggregator.

Before approving a feature, answer internally:

1. What source does it use?
2. What meaningful change or intelligence does it detect?
3. What Signal does it create or update?
4. Why is it useful to the user?
5. Is it already covered by another RADAR module?

Prefer an incremental change that strengthens an existing module over a duplicate module. Do not invent provider integrations or product capabilities that are not supported by the repository and canonical documentation.