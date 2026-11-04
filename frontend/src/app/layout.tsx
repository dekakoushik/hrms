import type { Metadata } from 'next';
import { Google_Sans } from 'next/font/google';
import './globals.css';
import { AuthProvider } from '@/context/AuthContext';

const googleSans = Google_Sans({
  subsets: ['latin'],
  weight: ['400', '500', '600', '700'],
  display: 'swap',
  variable: '--font-google-sans',
});

export const metadata: Metadata = {
  title: 'HRMS Portal - Enterprise Workforce Management',
  description: 'Enterprise HR Management System built with Laravel & Next.js',
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className={googleSans.variable}>
      <body className={`${googleSans.className} min-h-screen bg-slate-50 text-slate-900 antialiased`}>
        <AuthProvider>{children}</AuthProvider>
      </body>
    </html>
  );
}
