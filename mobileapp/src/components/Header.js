import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Image,
  Modal,
  Pressable,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';

export const Header = ({ onNavigate, currentScreen }) => {
  const { user, logout } = useAuth();
  const [menuVisible, setMenuVisible] = useState(false);

  return (
    <View style={styles.header}>
      {/* Brand & India Badge */}
      <TouchableOpacity 
        style={styles.brandContainer} 
        onPress={() => onNavigate('dashboard')}
        activeOpacity={0.7}
      >
        <View style={styles.brandIconWrapper}>
          <Ionicons name="shield-checkmark" size={20} color={colors.surface} />
        </View>
        <View>
          <View style={styles.brandTitleRow}>
            <Text style={styles.brandTitle}>HRMS</Text>
            <View style={styles.indiaBadge}>
              <Text style={styles.indiaBadgeText}>INDIA</Text>
            </View>
          </View>
          <Text style={styles.brandSubtitle}>Enterprise Suite</Text>
        </View>
      </TouchableOpacity>

      {/* Right Controls: Settings & Profile Dropdown */}
      <View style={styles.rightControls}>
        <TouchableOpacity
          style={styles.profileBtn}
          onPress={() => setMenuVisible(true)}
          activeOpacity={0.8}
        >
          <Image
            source={{ uri: user?.avatar_url || 'https://ui-avatars.com/api/?name=Super+Admin&background=4f46e5&color=fff' }}
            style={styles.avatar}
          />
          <View style={styles.dropdownCaret}>
            <Ionicons name="chevron-down" size={12} color={colors.textSecondary} />
          </View>
        </TouchableOpacity>
      </View>

      {/* Settings Dropdown Modal */}
      <Modal
        visible={menuVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setMenuVisible(false)}
      >
        <Pressable
          style={styles.modalOverlay}
          onPress={() => setMenuVisible(false)}
        >
          <View style={styles.dropdownCard}>
            {/* User Meta Header */}
            <View style={styles.dropdownHeader}>
              <Text style={styles.signedInLabel}>Signed in as</Text>
              <Text style={styles.userNameText} numberOfLines={1}>
                {user?.name || 'User'}
              </Text>
              <Text style={styles.userEmailText} numberOfLines={1}>
                {user?.email}
              </Text>
              <View style={styles.userRoleBadge}>
                <Text style={styles.userRoleText}>
                  {user?.role === 'superadmin' ? 'SUPER ADMINISTRATOR' : user?.role === 'admin' ? 'ADMINISTRATOR' : 'EMPLOYEE'}
                </Text>
              </View>
            </View>

            {/* Menu Options */}
            <View style={styles.menuItems}>
              <Text style={styles.menuSectionTitle}>NAVIGATION & SERVICES</Text>

              {/* DASHBOARD */}
              <TouchableOpacity
                style={[
                  styles.menuItem,
                  currentScreen === 'dashboard' && styles.menuItemActive,
                ]}
                onPress={() => {
                  setMenuVisible(false);
                  onNavigate('dashboard');
                }}
              >
                <View style={[styles.menuItemIcon, { backgroundColor: colors.surfaceSubtle }]}>
                  <Ionicons name="grid-outline" size={16} color={colors.textSecondary} />
                </View>
                <View style={styles.menuItemTextContainer}>
                  <Text style={styles.menuItemTitle}>Dashboard</Text>
                  <Text style={styles.menuItemSubtitle}>Overview & Punch Station</Text>
                </View>
              </TouchableOpacity>

              {/* ATTENDANCE HISTORY */}
              <TouchableOpacity
                style={[
                  styles.menuItem,
                  currentScreen === 'attendance_history' && styles.menuItemActive,
                ]}
                onPress={() => {
                  setMenuVisible(false);
                  onNavigate('attendance_history');
                }}
              >
                <View style={[styles.menuItemIcon, { backgroundColor: '#E0E7FF' }]}>
                  <Ionicons name="clipboard-outline" size={16} color={colors.primary} />
                </View>
                <View style={styles.menuItemTextContainer}>
                  <Text style={styles.menuItemTitle}>Attendance History</Text>
                  <Text style={styles.menuItemSubtitle}>Daily timesheet records</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
              </TouchableOpacity>

              {/* LEAVE MANAGER */}
              <TouchableOpacity
                style={[
                  styles.menuItem,
                  currentScreen === 'leave_manager' && styles.menuItemActive,
                ]}
                onPress={() => {
                  setMenuVisible(false);
                  onNavigate('leave_manager');
                }}
              >
                <View style={[styles.menuItemIcon, { backgroundColor: '#EDE9FE' }]}>
                  <Ionicons name="calendar-outline" size={16} color="#7C3AED" />
                </View>
                <View style={styles.menuItemTextContainer}>
                  <Text style={styles.menuItemTitle}>Leave Manager</Text>
                  <Text style={styles.menuItemSubtitle}>Apply & check balances</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
              </TouchableOpacity>

              {/* CONTACT DIRECTORY */}
              <TouchableOpacity
                style={[
                  styles.menuItem,
                  currentScreen === 'contacts' && styles.menuItemActive,
                ]}
                onPress={() => {
                  setMenuVisible(false);
                  onNavigate('contacts');
                }}
              >
                <View style={[styles.menuItemIcon, { backgroundColor: '#E0F2FE' }]}>
                  <Ionicons name="call-outline" size={16} color="#0284C7" />
                </View>
                <View style={styles.menuItemTextContainer}>
                  <Text style={styles.menuItemTitle}>Contact Directory</Text>
                  <Text style={styles.menuItemSubtitle}>Team phones & emails</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
              </TouchableOpacity>

              {/* HR & POLICIES */}
              <TouchableOpacity
                style={[
                  styles.menuItem,
                  currentScreen === 'policies' && styles.menuItemActive,
                ]}
                onPress={() => {
                  setMenuVisible(false);
                  onNavigate('policies');
                }}
              >
                <View style={[styles.menuItemIcon, { backgroundColor: '#FEF3C7' }]}>
                  <Ionicons name="book-outline" size={16} color="#D97706" />
                </View>
                <View style={styles.menuItemTextContainer}>
                  <Text style={styles.menuItemTitle}>HR & Policies</Text>
                  <Text style={styles.menuItemSubtitle}>Company handbook & rules</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
              </TouchableOpacity>

              {/* PAYSLIPS */}
              <TouchableOpacity
                style={[
                  styles.menuItem,
                  currentScreen === 'payslips' && styles.menuItemActive,
                ]}
                onPress={() => {
                  setMenuVisible(false);
                  onNavigate('payslips');
                }}
              >
                <View style={[styles.menuItemIcon, { backgroundColor: '#DCFCE7' }]}>
                  <Ionicons name="receipt-outline" size={16} color={colors.success} />
                </View>
                <View style={styles.menuItemTextContainer}>
                  <Text style={styles.menuItemTitle}>My Payslips</Text>
                  <Text style={styles.menuItemSubtitle}>Salary statement slips</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
              </TouchableOpacity>

              {/* RESIGNATION / EXIT */}
              <TouchableOpacity
                style={[
                  styles.menuItem,
                  currentScreen === 'resignation' && styles.menuItemActive,
                ]}
                onPress={() => {
                  setMenuVisible(false);
                  onNavigate('resignation');
                }}
              >
                <View style={[styles.menuItemIcon, { backgroundColor: '#FEE2E2' }]}>
                  <Ionicons name="exit-outline" size={16} color="#EF4444" />
                </View>
                <View style={styles.menuItemTextContainer}>
                  <Text style={styles.menuItemTitle}>Exit & Resignation</Text>
                  <Text style={styles.menuItemSubtitle}>Notice period & LWD</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
              </TouchableOpacity>

              {/* PROFILE */}
              <TouchableOpacity
                style={[
                  styles.menuItem,
                  currentScreen === 'profile' && styles.menuItemActive,
                ]}
                onPress={() => {
                  setMenuVisible(false);
                  onNavigate('profile');
                }}
              >
                <View style={[styles.menuItemIcon, { backgroundColor: colors.primaryLight }]}>
                  <Ionicons name="person" size={16} color={colors.primary} />
                </View>
                <View style={styles.menuItemTextContainer}>
                  <Text style={styles.menuItemTitle}>Profile Settings</Text>
                  <Text style={styles.menuItemSubtitle}>
                    {user?.role === 'superadmin' || user?.role === 'admin' ? 'Edit details & security' : 'View profile & password'}
                  </Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
              </TouchableOpacity>

              {/* ADMIN ONLY SECTIONS */}
              {(user?.role === 'superadmin' || user?.role === 'admin') && (
                <>
                  <Text style={[styles.menuSectionTitle, { marginTop: 10 }]}>ADMINISTRATION</Text>

                  {/* EMPLOYEES DIRECTORY */}
                  <TouchableOpacity
                    style={[
                      styles.menuItem,
                      currentScreen === 'employees' && styles.menuItemActive,
                    ]}
                    onPress={() => {
                      setMenuVisible(false);
                      onNavigate('employees');
                    }}
                  >
                    <View style={[styles.menuItemIcon, { backgroundColor: '#E0E7FF' }]}>
                      <Ionicons name="people-outline" size={16} color={colors.primary} />
                    </View>
                    <View style={styles.menuItemTextContainer}>
                      <Text style={styles.menuItemTitle}>Employees Master</Text>
                      <Text style={styles.menuItemSubtitle}>Directory & onboarding</Text>
                    </View>
                    <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
                  </TouchableOpacity>

                  {/* SHIFT & SMTP SETTINGS */}
                  <TouchableOpacity
                    style={[
                      styles.menuItem,
                      currentScreen === 'settings_manager' && styles.menuItemActive,
                    ]}
                    onPress={() => {
                      setMenuVisible(false);
                      onNavigate('settings_manager');
                    }}
                  >
                    <View style={[styles.menuItemIcon, { backgroundColor: '#FEF3C7' }]}>
                      <Ionicons name="options-outline" size={16} color="#D97706" />
                    </View>
                    <View style={styles.menuItemTextContainer}>
                      <Text style={styles.menuItemTitle}>Shifts & SMTP</Text>
                      <Text style={styles.menuItemSubtitle}>Work hours & mail gateway</Text>
                    </View>
                    <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
                  </TouchableOpacity>
                </>
              )}
            </View>

            {/* Sign Out */}
            <View style={styles.dropdownFooter}>
              <TouchableOpacity
                style={styles.signOutBtn}
                onPress={() => {
                  setMenuVisible(false);
                  logout();
                }}
              >
                <Ionicons name="log-out-outline" size={18} color={colors.danger} />
                <Text style={styles.signOutText}>Sign Out</Text>
              </TouchableOpacity>
            </View>
          </View>
        </Pressable>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  header: {
    height: 52,
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 14,
  },
  brandContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  brandIconWrapper: {
    width: 30,
    height: 30,
    borderRadius: 8,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  brandTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  brandTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: colors.textPrimary,
    letterSpacing: -0.2,
  },
  indiaBadge: {
    backgroundColor: colors.successLight,
    paddingHorizontal: 4,
    paddingVertical: 1,
    borderRadius: 3,
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  indiaBadgeText: {
    fontSize: 8.5,
    fontWeight: '700',
    color: colors.success,
  },
  brandSubtitle: {
    fontSize: 9.5,
    color: colors.textSecondary,
    fontWeight: '500',
  },
  rightControls: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  profileBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 2,
    borderRadius: 20,
    backgroundColor: colors.surfaceSubtle,
    borderWidth: 1,
    borderColor: colors.border,
    gap: 3,
  },
  avatar: {
    width: 28,
    height: 28,
    borderRadius: 14,
  },
  dropdownCaret: {
    paddingRight: 4,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.4)',
    justifyContent: 'flex-start',
    alignItems: 'flex-end',
    paddingTop: 56,
    paddingRight: 14,
  },
  dropdownCard: {
    width: 260,
    backgroundColor: colors.surface,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.border,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.12,
    shadowRadius: 16,
    elevation: 8,
    overflow: 'hidden',
  },
  dropdownHeader: {
    padding: 12,
    backgroundColor: colors.surfaceSubtle,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  signedInLabel: {
    fontSize: 9,
    fontWeight: '700',
    color: colors.textMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  userNameText: {
    fontSize: 13,
    fontWeight: '800',
    color: colors.textPrimary,
    marginTop: 1,
  },
  userEmailText: {
    fontSize: 12,
    color: colors.textSecondary,
    marginTop: 1,
  },
  userRoleBadge: {
    alignSelf: 'flex-start',
    backgroundColor: colors.primaryLight,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
    marginTop: 8,
  },
  userRoleText: {
    fontSize: 9,
    fontWeight: '800',
    color: colors.primary,
    letterSpacing: 0.5,
  },
  menuItems: {
    paddingVertical: 10,
    paddingHorizontal: 8,
  },
  menuSectionTitle: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.textMuted,
    marginLeft: 8,
    marginBottom: 6,
    letterSpacing: 0.5,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 10,
    borderRadius: 12,
    gap: 12,
  },
  menuItemActive: {
    backgroundColor: colors.primaryLight,
  },
  menuItemIcon: {
    width: 32,
    height: 32,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  menuItemTextContainer: {
    flex: 1,
  },
  menuItemTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  menuItemSubtitle: {
    fontSize: 11,
    color: colors.textMuted,
    marginTop: 1,
  },
  dropdownFooter: {
    borderTopWidth: 1,
    borderTopColor: colors.border,
    padding: 8,
  },
  signOutBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    padding: 10,
    borderRadius: 12,
  },
  signOutText: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.danger,
  },
});
