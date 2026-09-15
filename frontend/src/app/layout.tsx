import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "RADAR — Public Intelligence",
  description: "Live public intelligence and change monitoring.",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
