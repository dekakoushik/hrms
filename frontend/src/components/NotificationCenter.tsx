'use client';

import React, { useState, useEffect, useRef } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { apiRequest } from '@/lib/api';
import { AppNotification, NotificationResponse } from '@/types/notification';
import {
  Bell,
  CheckCheck,
  Trash2,
  Calendar,
  CheckCircle2,
  XCircle,
  Clock,
  CreditCard,
  FileText,
  Shield,
  ExternalLink,
  X,
  AlertCircle,
  Inbox,
  ChevronRight,
} from 'lucide-react';

export default function NotificationCenter() {
  const { user, token } = useAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [notifications, setNotifications] = useState<AppNotification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  const fetchNotifications = async () => {
    if (!token) return;
    try {
      const res = await apiRequest<NotificationResponse>('/notifications', {}, token);
      setNotifications(res.notifications || []);
      setUnreadCount(res.unread_count || 0);
    } catch (err) {
      console.error('Failed to load notifications', err);
    }
  };

  useEffect(() => {
    fetchNotifications();
    // Refresh periodically every 45s
    const interval = setInterval(fetchNotifications, 45000);
    return () => clearInterval(interval);
  }, [token]);

  // Handle outside click to close dropdown
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    };
    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [isOpen]);

  const handleMarkAsRead = async (id: number) => {
    if (!token) return;
    try {
      await apiRequest(`/notifications/${id}/read`, { method: 'POST' }, token);
      setNotifications((prev) =>
        prev.map((n) => (n.id === id ? { ...n, is_read: true } : n))
      );
      setUnreadCount((prev) => Math.max(0, prev - 1));
    } catch (err) {
      console.error('Error marking as read', err);
    }
  };

  const handleMarkAllAsRead = async () => {
    if (!token) return;
    try {
      await apiRequest('/notifications/read-all', { method: 'POST' }, token);
      setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
      setUnreadCount(0);
    } catch (err) {
      console.error('Error marking all as read', err);
    }
  };

  const handleClearAll = async () => {
    if (!token) return;
    try {
      await apiRequest('/notifications/clear-all', { method: 'DELETE' }, token);
      setNotifications([]);
      setUnreadCount(0);
    } catch (err) {
      console.error('Error clearing notifications', err);
    }
  };

  // Helper icon renderer
  const renderIcon = (type: string, iconName?: string | null) => {
    switch (iconName || type) {
      case 'check-circle':
      case 'leave_approved':
        return <CheckCircle2 className="w-4 h-4 text-emerald-600" />;
      case 'x-circle':
      case 'leave_rejected':
        return <XCircle className="w-4 h-4 text-red-600" />;
      case 'calendar':
      case 'leave_applied':
        return <Calendar className="w-4 h-4 text-blue-600" />;
      case 'clock':
      case 'attendance_regularized':
        return <Clock className="w-4 h-4 text-amber-600" />;
      case 'credit-card':
      case 'payroll':
        return <CreditCard className="w-4 h-4 text-purple-600" />;
      case 'file-text':
      case 'document_published':
        return <FileText className="w-4 h-4 text-indigo-600" />;
      default:
        return <Bell className="w-4 h-4 text-blue-600" />;
    }
  };

  const formatTimeAgo = (dateStr: string) => {
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
  };

  return (
    <div className="relative" ref={dropdownRef}>
      {/* Bell Button */}
      <button
        type="button"
        onClick={() => {
          setIsOpen(!isOpen);
          if (!isOpen) fetchNotifications();
        }}
        className={`relative rounded-xl p-2.5 transition cursor-pointer flex items-center justify-center ${
          isOpen
            ? 'bg-blue-50 text-blue-600 ring-2 ring-blue-500/20'
            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
        }`}
        title="Notification Center"
      >
        <Bell className="h-4 w-4 sm:h-5 sm:w-5" />
        {unreadCount > 0 && (
          <span className="absolute top-1.5 right-1.5 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white shadow-xs animate-pulse">
            {unreadCount > 9 ? '9+' : unreadCount}
          </span>
        )}
      </button>

      {/* Dropdown Panel */}
      {isOpen && (
        <div className="absolute right-0 mt-2.5 w-80 sm:w-96 rounded-2xl bg-white shadow-2xl border border-slate-200 z-50 overflow-hidden transform opacity-100 scale-100 transition duration-150 ease-out origin-top-right">
          {/* Header */}
          <div className="px-4 py-3 bg-gradient-to-r from-slate-900 to-indigo-950 text-white flex items-center justify-between">
            <div className="flex items-center space-x-2">
              <span className="font-bold text-xs uppercase tracking-wider">Notifications</span>
              {unreadCount > 0 && (
                <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-500/30 text-blue-200 border border-blue-400/30">
                  {unreadCount} new
                </span>
              )}
            </div>

            <div className="flex items-center space-x-2 text-xs">
              {unreadCount > 0 && (
                <button
                  type="button"
                  onClick={handleMarkAllAsRead}
                  className="text-[11px] text-blue-300 hover:text-white flex items-center gap-1 transition"
                  title="Mark all as read"
                >
                  <CheckCheck className="w-3.5 h-3.5" />
                  <span className="hidden sm:inline">Read All</span>
                </button>
              )}
              {notifications.length > 0 && (
                <button
                  type="button"
                  onClick={handleClearAll}
                  className="text-[11px] text-slate-400 hover:text-red-300 transition"
                  title="Clear all notifications"
                >
                  <Trash2 className="w-3.5 h-3.5" />
                </button>
              )}
            </div>
          </div>

          {/* List View */}
          <div className="max-h-[380px] overflow-y-auto divide-y divide-slate-100 bg-white">
            {notifications.length === 0 ? (
              <div className="py-12 px-4 text-center">
                <Inbox className="w-10 h-10 text-slate-300 mx-auto mb-2" />
                <p className="text-xs font-semibold text-slate-700">All caught up!</p>
                <p className="text-[11px] text-slate-400 mt-0.5">
                  No notifications or pending approvals at this time.
                </p>
              </div>
            ) : (
              notifications.map((item) => (
                <div
                  key={item.id}
                  onClick={() => {
                    if (!item.is_read) handleMarkAsRead(item.id);
                  }}
                  className={`p-3.5 flex items-start gap-3 transition cursor-pointer hover:bg-slate-50 ${
                    item.is_read ? 'bg-white opacity-80' : 'bg-blue-50/40'
                  }`}
                >
                  {/* Icon badge */}
                  <div
                    className={`w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 border ${
                      item.is_read
                        ? 'bg-slate-50 border-slate-200 text-slate-500'
                        : 'bg-white border-blue-200 shadow-2xs'
                    }`}
                  >
                    {renderIcon(item.type, item.icon)}
                  </div>

                  {/* Body Content */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-1 mb-0.5">
                      <h4
                        className={`text-xs truncate ${
                          item.is_read ? 'font-medium text-slate-700' : 'font-bold text-slate-900'
                        }`}
                      >
                        {item.title}
                      </h4>
                      <span className="text-[10px] text-slate-400 flex-shrink-0">
                        {formatTimeAgo(item.created_at)}
                      </span>
                    </div>

                    <p className="text-[11px] text-slate-600 leading-relaxed line-clamp-2">
                      {item.message}
                    </p>

                    {item.action_url && (
                      <Link
                        href={item.action_url}
                        onClick={() => {
                          if (!item.is_read) handleMarkAsRead(item.id);
                          setIsOpen(false);
                        }}
                        className="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 hover:text-blue-700 mt-1.5"
                      >
                        <span>View details</span>
                        <ChevronRight className="w-3 h-3" />
                      </Link>
                    )}
                  </div>

                  {/* Read indicator dot */}
                  {!item.is_read && (
                    <div className="w-2 h-2 rounded-full bg-blue-600 flex-shrink-0 mt-1.5" />
                  )}
                </div>
              ))
            )}
          </div>

          {/* Footer */}
          <div className="px-4 py-2 bg-slate-50 border-t border-slate-100 text-center">
            <span className="text-[10px] text-slate-400 font-medium">
              Enterprise Notification Service &bull; Real-time updates
            </span>
          </div>
        </div>
      )}
    </div>
  );
}

