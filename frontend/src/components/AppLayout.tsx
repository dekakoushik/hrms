'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import NotificationCenter from '@/components/NotificationCenter';
import {
  Users,
  LayoutDashboard,
  CalendarCheck,
  CreditCard,
  Building2,
  Settings,
  LogOut,
  Shield,
  Bell,
  Search,
  Briefcase,
  Menu,
  X,
  Calendar,
  FileText,
  LifeBuoy,
  BarChart3,
  Contact,
} from 'lucide-react';

interface AppLayoutProps {
  children: React.ReactNode;
  title?: React.ReactNode;
  subtitle?: string;
  action?: React.ReactNode;
}

export default function AppLayout({ children, title, subtitle, action }: AppLayoutProps) {
  const { user, logout } = useAuth();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const pathname = usePathname();
  const [currentSearch, setCurrentSearch] = useState('');

  React.useEffect(() => {
    if (typeof window !== 'undefined') {
      setCurrentSearch(window.location.search);
      const handleLocationChange = () => setCurrentSearch(window.location.search);
      window.addEventListener('popstate', handleLocationChange);
      return () => window.removeEventListener('popstate', handleLocationChange);
    }
  }, [pathname]);

  const isAdminOrSuper = user?.role === 'admin' || user?.role === 'superadmin';

  // Navigation Links tailored by Role
  const navItems = isAdminOrSuper
    ? [
        { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, badge: null },
        { name: 'Directory', href: '/directory', icon: Contact, badge: null },
        { name: 'Employees', href: '/employees', icon: Users, badge: null },
        { name: 'Departments', href: '/settings?tab=department', icon: Building2, badge: null },
        { name: 'Attendance', href: '/attendance', icon: CalendarCheck, badge: null },
        { name: 'Leaves & Holidays', href: '/leaves', icon: Calendar, badge: null },
        { name: 'Payroll', href: '/payroll', icon: CreditCard, badge: null },
        { name: 'Policies & Compliance', href: '/documents', icon: FileText, badge: null },
        { name: 'IT Helpdesk', href: '/tickets', icon: LifeBuoy, badge: null },
        { name: 'Reports Center', href: '/reports', icon: BarChart3, badge: null },
        { name: 'Settings', href: '/settings', icon: Settings, badge: null },
      ]
    : [
        { name: 'My Dashboard', href: '/dashboard', icon: LayoutDashboard, badge: null },
        { name: 'Directory', href: '/directory', icon: Contact, badge: null },
        { name: 'My Attendance', href: '/attendance', icon: CalendarCheck, badge: null },
        { name: 'Leaves & Holidays', href: '/leaves', icon: Calendar, badge: null },
        { name: 'My Payslips', href: '/payroll', icon: CreditCard, badge: null },
        { name: 'Policies & Compliance', href: '/documents', icon: FileText, badge: null },
        { name: 'IT Helpdesk', href: '/tickets', icon: LifeBuoy, badge: null },
      ];

  return (
    <div className="flex h-screen w-screen bg-slate-50 text-slate-800 antialiased overflow-hidden">
      {/* Mobile Drawer Overlay */}
      {mobileMenuOpen && (
        <div
          onClick={() => setMobileMenuOpen(false)}
          className="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-xs lg:hidden transition-opacity"
          aria-hidden="true"
        />
      )}

      {/* Sidebar - FIXED in Desktop View (h-screen, non-scrollable shell) */}
      <aside
        className={`fixed inset-y-0 left-0 z-50 w-72 sm:w-64 bg-white border-r border-slate-200 transform transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 flex flex-col justify-between shrink-0 h-screen ${
          mobileMenuOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'
        }`}
      >
        <div className="flex flex-col flex-1 min-h-0">
          {/* Brand Header - Fixed height */}
          <div className="flex h-16 items-center justify-between px-5 sm:px-6 border-b border-slate-100 shrink-0">
            <Link href="/dashboard" className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white font-bold shadow-sm shadow-blue-600/30">
                <Briefcase className="h-5 w-5" />
              </div>
              <div>
                <h1 className="text-base font-bold text-slate-900 tracking-tight leading-none">
                  HRMS Portal
                </h1>
                <span className="text-[11px] font-semibold text-blue-600 uppercase tracking-wider">
                  Enterprise Suite
                </span>
              </div>
            </Link>

            <button
              type="button"
              onClick={() => setMobileMenuOpen(false)}
              className="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 cursor-pointer"
            >
              <X className="h-5 w-5" />
            </button>
          </div>

          {/* Navigation Links (Scrollable only if viewport height is tiny) */}
          <div className="px-3 py-4 flex-1 overflow-y-auto">
            <div className="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
              Main Menu
            </div>
            <nav className="space-y-1">
              {navItems.map((item) => {
                const Icon = item.icon;
                const isDept = pathname === '/settings' && currentSearch.includes('tab=department');
                const isSettings = pathname === '/settings' && !isDept;
                const isDashboard = pathname === '/dashboard';
                const isEmployees = pathname.startsWith('/employees');
                const isAttendance = pathname.startsWith('/attendance');

                const isActive =
                  (item.href === '/dashboard' && pathname === '/dashboard') ||
                  (item.href === '/directory' && pathname === '/directory') ||
                  (item.href === '/employees' && isEmployees) ||
                  (item.href === '/settings?tab=department' && isDept) ||
                  (item.href === '/attendance' && isAttendance) ||
                  (item.href === '/leaves' && pathname.startsWith('/leaves')) ||
                  (item.href === '/payroll' && pathname.startsWith('/payroll')) ||
                  (item.href === '/documents' && pathname.startsWith('/documents')) ||
                  (item.href === '/tickets' && pathname.startsWith('/tickets')) ||
                  (item.href === '/reports' && pathname.startsWith('/reports')) ||
                  (item.href === '/settings' && isSettings);

                return (
                  <Link
                    key={item.name}
                    href={item.href}
                    onClick={() => setMobileMenuOpen(false)}
                    className={`flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-medium transition ${
                      isActive
                        ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100 shadow-xs'
                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <Icon
                        className={`h-4 w-4 shrink-0 ${
                          isActive ? 'text-blue-600' : 'text-slate-400'
                        }`}
                      />
                      <span>{item.name}</span>
                    </div>
                    {item.badge && (
                      <span
                        className={`rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                          item.badge === 'Active'
                            ? 'bg-emerald-100 text-emerald-700'
                            : 'bg-slate-100 text-slate-500'
                        }`}
                      >
                        {item.badge}
                      </span>
                    )}
                  </Link>
                );
              })}
            </nav>
          </div>
        </div>

        {/* User Card & Sign Out (Sticky at bottom of sidebar) */}
        <div className="border-t border-slate-100 p-4 bg-slate-50/50 shrink-0">
          <div className="flex items-center gap-3 p-2 rounded-xl bg-white border border-slate-200/80 shadow-xs mb-3">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700 font-bold text-sm">
              {user?.name?.[0]?.toUpperCase() || 'S'}
            </div>
            <div className="flex-1 min-w-0">
              <p className="truncate text-xs font-semibold text-slate-800">{user?.name || 'User'}</p>
              <div className="flex items-center gap-1 text-[11px] text-blue-600 font-medium">
                <Shield className="h-3 w-3" />
                <span className="capitalize">{user?.role || 'Staff'}</span>
              </div>
            </div>
          </div>

          <button
            onClick={() => logout()}
            className="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:text-red-600 hover:bg-red-50 hover:border-red-200 transition shadow-xs cursor-pointer"
          >
            <LogOut className="h-3.5 w-3.5" />
            Sign Out
          </button>
        </div>
      </aside>

      {/* Main Area: Fixed Top Header + Independent Scrollable Content Viewport */}
      <div className="flex flex-1 flex-col min-w-0 h-screen overflow-hidden">
        {/* Fixed Header */}
        <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={() => setMobileMenuOpen(true)}
              className="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none cursor-pointer"
            >
              <Menu className="h-5 w-5" />
            </button>

            {/* Title / Breadcrumbs or Search */}
            {title ? (
              typeof title === 'string' ? (
                <div>
                  <h1 className="text-base font-bold text-slate-900 leading-tight">{title}</h1>
                  {subtitle && <p className="text-xs text-slate-500 hidden sm:block">{subtitle}</p>}
                </div>
              ) : (
                <div>{title}</div>
              )
            ) : (
              <div className="relative hidden sm:block">
                <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input
                  type="text"
                  placeholder="Search employees, records..."
                  className="w-48 md:w-64 lg:w-80 rounded-lg border border-slate-200 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
            )}
          </div>

          {/* Right Header Area */}
          <div className="flex items-center gap-3 sm:gap-4">
            {action && <div>{action}</div>}

            <NotificationCenter />

            <div className="h-5 w-px bg-slate-200 hidden sm:block" />

            <div className="flex items-center gap-2">
              <div className="text-right hidden sm:block">
                <p className="text-xs font-semibold text-slate-900 leading-none">{user?.name}</p>
                <p className="text-[10px] text-slate-500 mt-0.5">{user?.email}</p>
              </div>
              <span className="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 border border-blue-200 uppercase">
                {user?.role || 'Staff'}
              </span>
            </div>
          </div>
        </header>

        {/* Scrollable Main Content - ONLY this viewport scrolls! Header & Sidebar remain firmly fixed */}
        <main className="flex-1 min-h-0 overflow-y-auto">
          {children}
        </main>
      </div>
    </div>
  );
}
