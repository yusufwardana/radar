import dns from "node:dns/promises";

const blockedHostnames = new Set(["localhost", "metadata.google.internal", "host.docker.internal"]);

export async function validatePublicUrl(rawUrl: string): Promise<URL> {
  const url = new URL(rawUrl);
  if (!(["http:", "https:"] as string[]).includes(url.protocol)) {
    throw new Error("UNSUPPORTED_SCHEME");
  }
  if (url.username || url.password || blockedHostnames.has(url.hostname.toLowerCase())) {
    throw new Error("BLOCKED_DESTINATION");
  }
  const records = await dns.lookup(url.hostname, { all: true, verbatim: true });
  if (records.length === 0 || records.some(({ address }) => isPrivateAddress(address))) {
    throw new Error("BLOCKED_DESTINATION");
  }
  return url;
}

function isPrivateAddress(address: string): boolean {
  if (address === "::1" || address === "0.0.0.0" || address === "::") return true;
  const normalized = address.toLowerCase();
  if (normalized.startsWith("127.") || normalized.startsWith("10.") || normalized.startsWith("192.168.")) return true;
  const octets = normalized.split(".").map(Number);
  if (octets.length === 4 && octets[0] === 172 && octets[1] >= 16 && octets[1] <= 31) return true;
  if (normalized.startsWith("169.254.")) return true;
  return normalized.startsWith("fc") || normalized.startsWith("fd") || normalized.startsWith("fe80:");
}