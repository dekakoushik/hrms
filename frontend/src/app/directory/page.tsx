'use client';

import React, { useState, useEffect } from 'react';
import AppLayout from '@/components/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { apiRequest } from '@/lib/api';
import { DirectoryEmployee, DirectoryResponse } from '@/types/directory';
import {
  Search,
  Mail,
  Phone,
  Building2,
  Briefcase,
  MapPin,
  Clock,
  User,
  Users,
  Copy,
  Check,
  Filter,
  Sparkles,
  ExternalLink,
  Shield,
  Layers,
  Calendar,
} from 'lucide-react';

export default function DirectoryPage() {
  const { user, token } = useAuth();
  const [employees, setEmployees] = useState<DirectoryEmployee[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [departmentFilter, setDepartmentFilter] = useState('all');
  const [departments, setDepartments] = useState<{ id: number; name: string }[]>([]);
  const [copiedField, setCopiedField] = useState<string | null>(null);
  const [selectedEmployee, setSelectedEmployee] = useState<DirectoryEmployee | null>(null);

  const fetchDirectory = async () => {
    if (!token) return;
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (searchQuery.trim()) params.append('search', searchQuery.trim());
      if (departmentFilter !== 'all') params.append('department_id', departmentFilter);

      const url = `/directory${params.toString() ? `?${params.toString()}` : ''}`;
      const res = await apiRequest<DirectoryResponse>(url, {}, token || undefined);
      setEmployees(res.employees || []);

      // Extract unique departments for filter
      const deptMap = new Map<number, string>();
      (res.employees || []).forEach((emp) => {
        if (emp.department?.id && emp.department?.name) {
          deptMap.set(emp.department.id, emp.department.name);
        }
      });
      setDepartments(Array.from(deptMap.entries()).map(([id, name]) => ({ id, name })));
    } catch (err) {
      console.error('Failed to load employee directory', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDirectory();
  }, [departmentFilter, token]);

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    fetchDirectory();
  };

  const copyToClipboard = (text: string, fieldId: string) => {
    navigator.clipboard.writeText(text);
    setCopiedField(fieldId);
    setTimeout(() => setCopiedField(null), 2500);
  };

  return (
    <AppLayout
      title="Company Directory"
      subtitle="Find colleagues, contact numbers, official emails, designations, and departmental teams"
    >
      <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
        {/* Hero Notice Banner */}
        <div className="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl p-6 text-white shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div className="space-y-1">
            <div className="flex items-center space-x-2">
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-300 border border-blue-400/30">
                Staff Directory
              </span>
              <span className="text-xs text-slate-400">All Authorized Personnel</span>
            </div>
            <h2 className="text-lg font-bold tracking-tight">Internal Corporate Phonebook & Email Directory</h2>
            <p className="text-xs text-slate-300 max-w-2xl leading-relaxed">
              Instantly look up team members by Name, Employee ID (e.g. EMP-2026-0001), Department, or Contact details for seamless cross-department collaboration.
            </p>
          </div>
          <div className="flex items-center gap-3">
            <div className="text-right hidden sm:block">
              <div className="text-2xl font-bold">{employees.length}</div>
              <div className="text-xs text-blue-200">Active Staff</div>
            </div>
            <div className="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center border border-white/20">
              <Users className="w-6 h-6 text-blue-300" />
            </div>
          </div>
        </div>

        {/* Search & Filter Toolbar */}
        <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
          <form onSubmit={handleSearchSubmit} className="relative w-full sm:w-96">
            <Search className="w-4 h-4 absolute left-3.5 top-3 text-slate-400" />
            <input
              type="text"
              placeholder="Search by Employee ID (EMP-...), Name, or Email..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-10 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white text-slate-800 transition"
            />
          </form>

          <div className="flex items-center gap-2 w-full sm:w-auto">
            <Filter className="w-3.5 h-3.5 text-slate-400 shrink-0" />
            <select
              value={departmentFilter}
              onChange={(e) => setDepartmentFilter(e.target.value)}
              className="px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full sm:w-auto"
            >
              <option value="all">All Departments</option>
              {departments.map((d) => (
                <option key={d.id} value={d.id}>
                  {d.name}
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Directory Cards Grid */}
        {loading ? (
          <div className="bg-white rounded-2xl border border-slate-200 p-16 text-center text-slate-400 text-xs">
            <div className="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
            Loading team directory...
          </div>
        ) : employees.length === 0 ? (
          <div className="bg-white rounded-2xl border border-slate-200 p-16 text-center text-slate-500">
            <Users className="w-12 h-12 text-slate-200 mx-auto mb-3" />
            <h3 className="text-sm font-bold text-slate-700">No employees found</h3>
            <p className="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
              No staff members matched your search query &quot;{searchQuery}&quot;. Check the spelling or Employee ID.
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            {employees.map((emp) => {
              const fullName = `${emp.first_name} ${emp.last_name}`;
              return (
                <div
                  key={emp.id}
                  className="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs hover:shadow-md transition flex flex-col justify-between group"
                >
                  <div>
                    {/* Header: Avatar, Name & ID */}
                    <div className="flex items-start justify-between gap-3 mb-3.5">
                      <div className="flex items-center space-x-3 min-w-0">
                        <div className="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white font-bold text-sm flex items-center justify-center shrink-0 shadow-xs">
                          {emp.first_name[0]?.toUpperCase()}{emp.last_name[0]?.toUpperCase() || ''}
                        </div>
                        <div className="min-w-0">
                          <h3 className="text-sm font-bold text-slate-900 truncate group-hover:text-blue-600 transition">
                            {fullName}
                          </h3>
                          <p className="text-xs text-slate-500 truncate">
                            {emp.designation?.title || 'Staff Member'}
                          </p>
                        </div>
                      </div>

                      {/* Employee ID Badge */}
                      <button
                        onClick={() => copyToClipboard(emp.employee_id, `id-${emp.id}`)}
                        className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition shrink-0"
                        title="Click to copy Employee ID"
                      >
                        {copiedField === `id-${emp.id}` ? (
                          <Check className="w-3 h-3 text-emerald-600 mr-1" />
                        ) : null}
                        <span>{emp.employee_id}</span>
                      </button>
                    </div>

                    {/* Department & Campus Pills */}
                    <div className="flex flex-wrap items-center gap-1.5 mb-4 text-[11px]">
                      {emp.department && (
                        <span className="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium">
                          <Building2 className="w-3 h-3 mr-1 text-slate-400" />
                          {emp.department.name}
                        </span>
                      )}
                      {emp.location && (
                        <span className="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">
                          <MapPin className="w-3 h-3 mr-1 text-slate-400" />
                          {emp.location.name}
                        </span>
                      )}
                    </div>

                    {/* Contact Channels */}
                    <div className="space-y-2 pt-3 border-t border-slate-100 text-xs">
                      {/* Email */}
                      <div className="flex items-center justify-between group/mail">
                        <a
                          href={`mailto:${emp.email}`}
                          className="flex items-center text-slate-600 hover:text-blue-600 truncate min-w-0"
                          title="Send Email"
                        >
                          <Mail className="w-3.5 h-3.5 mr-2 text-slate-400 shrink-0" />
                          <span className="truncate">{emp.email}</span>
                        </a>
                        <button
                          onClick={() => copyToClipboard(emp.email, `mail-${emp.id}`)}
                          className="text-slate-400 hover:text-slate-600 p-1 rounded ml-1"
                          title="Copy Email"
                        >
                          {copiedField === `mail-${emp.id}` ? (
                            <Check className="w-3 h-3 text-emerald-600" />
                          ) : (
                            <Copy className="w-3 h-3" />
                          )}
                        </button>
                      </div>

                      {/* Phone */}
                      {emp.phone ? (
                        <div className="flex items-center justify-between group/phone">
                          <a
                            href={`tel:${emp.phone}`}
                            className="flex items-center text-slate-600 hover:text-blue-600 truncate min-w-0"
                            title="Call Phone"
                          >
                            <Phone className="w-3.5 h-3.5 mr-2 text-slate-400 shrink-0" />
                            <span className="truncate">{emp.phone}</span>
                          </a>
                          <button
                            onClick={() => copyToClipboard(emp.phone || '', `phone-${emp.id}`)}
                            className="text-slate-400 hover:text-slate-600 p-1 rounded ml-1"
                            title="Copy Phone"
                          >
                            {copiedField === `phone-${emp.id}` ? (
                              <Check className="w-3 h-3 text-emerald-600" />
                            ) : (
                              <Copy className="w-3 h-3" />
                            )}
                          </button>
                        </div>
                      ) : (
                        <div className="flex items-center text-slate-400 text-[11px]">
                          <Phone className="w-3.5 h-3.5 mr-2 text-slate-300 shrink-0" />
                          <span>No phone listed</span>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Footer Meta */}
                  <div className="pt-3.5 mt-3.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                    <span className="flex items-center">
                      <Clock className="w-3 h-3 mr-1 text-slate-300" />
                      {emp.shift?.name || 'Standard Shift'}
                    </span>
                    <span className="capitalize">{emp.employment_type || 'Full-Time'}</span>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </AppLayout>
  );
}

