'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import { apiRequest, AuthResponse } from '@/lib/api';
import {
  Lock,
  Mail,
  ShieldCheck,
  ArrowRight,
  AlertCircle,
  Loader2,
  Building2,
  CheckCircle2,
  KeyRound,
  Eye,
  EyeOff,
  UserCheck,
  Sparkles,
  Quote,
  HeartHandshake,
  Award,
} from 'lucide-react';

export default function LoginPage() {
  const [loginIdentifier, setLoginIdentifier] = useState('superadmin@hrms.local');
  const [password, setPassword] = useState('SuperAdmin@12345');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      if (params.get('expired')) {
        setError('Your session has expired. Please sign in again.');
      }
    }
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const data = await apiRequest<AuthResponse>('/login', {
        method: 'POST',
        body: JSON.stringify({ login: loginIdentifier, password }),
      });

      login(data.token, data.user);
      router.push('/dashboard');
    } catch (err: unknown) {
      if (err instanceof Error) {
        setError(err.message);
      } else {
        setError('Login failed. Please check your credentials.');
      }
    } finally {
      setLoading(false);
    }
  };

  const fillSuperadminDemo = () => {
    setLoginIdentifier('superadmin@hrms.local');
    setPassword('SuperAdmin@12345');
  };

  const fillEmployeeDemo = () => {
    setLoginIdentifier('EMP-2026-0001');
    setPassword('Password@123');
  };

  return (
    <div className="flex min-h-screen bg-slate-50 text-slate-800">
      {/* Left Form Panel - Full width on Mobile, fixed proportion on Tablet/Desktop */}
      <div className="flex flex-1 flex-col justify-between px-4 sm:px-8 md:px-12 lg:px-16 py-8 sm:py-12 bg-white lg:max-w-xl xl:max-w-2xl border-r border-slate-200/80 shadow-xs z-10">
        {/* Top Branding Bar */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-blue-600 text-white font-bold shadow-md shadow-blue-600/20">
              <Building2 className="h-5 w-5 sm:h-6 sm:w-6" />
            </div>
            <div>
              <h1 className="text-lg sm:text-xl font-bold tracking-tight text-slate-900 leading-none">
                HRMS Enterprise
              </h1>
              <p className="mt-1 text-[11px] sm:text-xs font-medium text-slate-500">
                Workforce Management
              </p>
            </div>
          </div>
          <span className="inline-flex items-center rounded-full bg-blue-50 border border-blue-200/70 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700">
            v1.0
          </span>
        </div>

        {/* Center Content */}
        <div className="my-8 sm:my-10 mx-auto w-full max-w-md">
          <div className="mb-6 sm:mb-8">
            <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
              Sign In to Your Workspace
            </h2>
            <p className="mt-2 text-xs sm:text-sm text-slate-500 leading-relaxed">
              Enter your corporate credentials to access the human resource management system.
            </p>
          </div>

          <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
            {error && (
              <div className="mb-4 sm:mb-5 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-3 sm:p-3.5 text-xs sm:text-sm text-red-700">
                <AlertCircle className="h-5 w-5 shrink-0 text-red-500 mt-0.5" />
                <span className="leading-snug">{error}</span>
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                  Email Address or Employee ID
                </label>
                <div className="relative mt-1.5">
                  <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <UserCheck className="h-4 w-4" />
                  </div>
                  <input
                    type="text"
                    required
                    value={loginIdentifier}
                    onChange={(e) => setLoginIdentifier(e.target.value)}
                    placeholder="name@company.com or EMP-2026-0001"
                    className="block w-full rounded-lg border border-slate-300 bg-slate-50/50 py-2.5 pl-10 pr-3.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                  Password
                </label>
                <div className="relative mt-1.5">
                  <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <Lock className="h-4 w-4" />
                  </div>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    required
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="••••••••••••"
                    className="block w-full rounded-lg border border-slate-300 bg-slate-50/50 py-2.5 pl-10 pr-10 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
                  >
                    {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
              </div>

              <div className="pt-2">
                <button
                  type="submit"
                  disabled={loading}
                  className="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 sm:py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:opacity-70"
                >
                  {loading ? (
                    <>
                      <Loader2 className="h-4 w-4 animate-spin" />
                      Authenticating...
                    </>
                  ) : (
                    <>
                      Sign In to Account
                      <ArrowRight className="h-4 w-4" />
                    </>
                  )}
                </button>
              </div>
            </form>

            <div className="mt-5 border-t border-slate-100 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
              <button
                type="button"
                onClick={fillSuperadminDemo}
                className="flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
              >
                <KeyRound className="h-3.5 w-3.5 text-blue-600" />
                Auto-fill Superadmin
              </button>
              <button
                type="button"
                onClick={fillEmployeeDemo}
                className="flex items-center justify-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50/50 px-3 py-2 text-[11px] font-medium text-blue-700 transition hover:bg-blue-100"
              >
                <UserCheck className="h-3.5 w-3.5 text-blue-600" />
                Auto-fill Employee ID
              </button>
            </div>
          </div>
        </div>

        {/* Mobile/Tablet friendly footer badge */}
        <div className="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500 pt-4 border-t border-slate-100">
          <div className="flex items-center gap-2">
            <ShieldCheck className="h-4 w-4 text-emerald-600 shrink-0" />
            <span>Secure Sanctum Token Session</span>
          </div>
          <span>© 2026 HRMS Enterprise</span>
        </div>
      </div>

      {/* Right Feature Panel - Hidden on Mobile, Visible on Desktop (lg+) */}
      <div className="relative hidden w-0 flex-1 lg:flex flex-col justify-between p-12 xl:p-16 bg-gradient-to-br from-slate-900 via-indigo-950 to-blue-950 text-white overflow-hidden">
        <div className="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-blue-500/15 blur-3xl pointer-events-none" />
        <div className="absolute -bottom-24 -left-24 h-96 w-96 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none" />

        {/* Top Welcome Tag */}
        <div className="relative z-10 max-w-xl">
          <span className="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/20 px-3.5 py-1 text-xs font-semibold text-blue-200 backdrop-blur-md">
            <Sparkles className="w-3.5 h-3.5 text-blue-300" />
            <span>Welcome to Your Workplace</span>
          </span>
          <h1 className="mt-6 text-3xl xl:text-4xl font-extrabold tracking-tight text-white leading-tight">
            Empowering People, Inspiring Excellence
          </h1>
          <p className="mt-4 text-sm xl:text-base text-slate-300 leading-relaxed">
            Every great achievement begins with the dedication of people working together with purpose, integrity, and passion.
          </p>
        </div>

        {/* Inspirational Quote Card */}
        <div className="relative z-10 max-w-xl rounded-2xl border border-white/15 bg-white/10 p-6 xl:p-7 backdrop-blur-xl shadow-2xl space-y-4">
          <div className="flex items-center justify-between">
            <div className="w-9 h-9 rounded-xl bg-blue-500/20 border border-blue-400/30 flex items-center justify-center text-blue-300">
              <Quote className="w-5 h-5" />
            </div>
            <span className="text-xs font-medium text-blue-200/80 uppercase tracking-widest">
              Daily Inspiration
            </span>
          </div>

          <blockquote className="text-base sm:text-lg font-medium text-white italic leading-relaxed">
            &ldquo;Coming together is a beginning, staying together is progress, and working together is success.&rdquo;
          </blockquote>

          <div className="flex items-center justify-between pt-3 border-t border-white/10 text-xs">
            <span className="font-semibold text-blue-200">Henry Ford</span>
            <span className="text-slate-400 flex items-center gap-1">
              <HeartHandshake className="w-3.5 h-3.5 text-blue-300" />
              Culture of Collaboration
            </span>
          </div>
        </div>

        {/* Bottom Core Values Highlights */}
        <div className="relative z-10 grid grid-cols-2 gap-4 max-w-xl">
          <div className="rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-md">
            <div className="flex items-center gap-2 text-emerald-400 text-xs font-semibold">
              <Award className="h-4 w-4" />
              <span>Trust & Transparency</span>
            </div>
            <p className="mt-1 text-xs text-slate-300">Open communication and mutual respect across every department.</p>
          </div>

          <div className="rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-md">
            <div className="flex items-center gap-2 text-blue-400 text-xs font-semibold">
              <HeartHandshake className="h-4 w-4" />
              <span>Employee Well-Being</span>
            </div>
            <p className="mt-1 text-xs text-slate-300">A thriving work environment centered on growth and support.</p>
          </div>
        </div>

        <div className="relative z-10 text-xs text-slate-400">
          Have a wonderful and productive day at work &bull; HRMS Enterprise Portal
        </div>
      </div>
    </div>
  );
}
