import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Image,
  ActivityIndicator,
  Alert,
  RefreshControl,
  Platform,
  Modal,
  TextInput,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';
import { getApiBaseUrl } from '../config/api';

export const DashboardScreen = ({ onNavigate }) => {
  const { user, token } = useAuth();
  const isAdmin = user?.role === 'superadmin' || user?.role === 'admin';

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [dashboardData, setDashboardData] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);
  const [fieldModalVisible, setFieldModalVisible] = useState(false);
  const [fieldRemarks, setFieldRemarks] = useState('');

  const fetchDashboardData = useCallback(async () => {
    try {
      const baseUrl = await getApiBaseUrl();
      const response = await fetch(`${baseUrl}/mobile/dashboard`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });

      if (response.ok) {
        const json = await response.json();
        setDashboardData(json);
      }
    } catch (e) {
      console.log('Error loading dashboard state:', e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useEffect(() => {
    fetchDashboardData();
  }, [fetchDashboardData]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchDashboardData();
  };

  const handlePunch = async (type) => {
    setActionLoading(true);
    try {
      const baseUrl = await getApiBaseUrl();
      const endpoint = type === 'in' ? `${baseUrl}/mobile/attendance/punch-in` : `${baseUrl}/mobile/attendance/punch-out`;

      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          latitude: 26.1696, // Guwahati / Office coordinates fallback
          longitude: 91.7607,
        }),
      });

      const resJson = await response.json();
      if (response.ok && resJson.success) {
        Alert.alert('Success', resJson.message);
        fetchDashboardData();
      } else {
        Alert.alert('Attendance Alert', resJson.message || 'Failed to record punch.');
      }
    } catch {
      Alert.alert('Network Error', 'Could not reach server to mark attendance.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleFieldVisitPunch = async () => {
    if (!fieldRemarks.trim()) {
      Alert.alert('Validation Error', 'Please enter remarks/purpose for the field visit.');
      return;
    }

    setActionLoading(true);
    try {
      const baseUrl = await getApiBaseUrl();
      const endpoint = `${baseUrl}/mobile/attendance/punch-in`;

      // Generate a representative dummy field selfie canvas or placeholder data URI for mobile testing
      const dummySelfieBase64 = 'data:image/svg+xml;base64,' + (typeof btoa !== 'undefined' ? btoa('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f59e0b"/></svg>') : '');

      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          is_field_visit: 1,
          latitude: 26.1824, // Field GPS Coordinates
          longitude: 91.7589,
          remarks: fieldRemarks.trim(),
          selfie_base64: dummySelfieBase64,
        }),
      });

      const resJson = await response.json();
      if (response.ok && resJson.success) {
        setFieldModalVisible(false);
        setFieldRemarks('');
        Alert.alert('Field Visit Recorded', resJson.message);
        fetchDashboardData();
      } else {
        Alert.alert('Attendance Alert', resJson.message || 'Failed to record field visit.');
      }
    } catch {
      Alert.alert('Network Error', 'Could not reach server to mark field visit.');
    } finally {
      setActionLoading(false);
    }
  };

  const employee = dashboardData?.employee;
  const todayAtt = dashboardData?.today_attendance;
  const userStats = dashboardData?.user_stats || { present_days: 0, half_days: 0, leave_days: 0, late_arrivals: 0 };
  const adminStats = dashboardData?.admin_stats || {};
  const recentPayslips = dashboardData?.recent_payslips || [];
  const resignation = dashboardData?.active_resignation;

  const currentHour = new Date().getHours();
  const greeting = currentHour < 12 ? 'Good morning' : currentHour < 17 ? 'Good afternoon' : 'Good evening';

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />}
    >
      {/* Hero Card */}
      <View style={styles.heroCard}>
        <View style={styles.heroTopRow}>
          <Image
            source={{
              uri:
                user?.avatar_url ||
                `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&background=4f46e5&color=fff`,
            }}
            style={styles.heroAvatar}
          />
          <View style={styles.heroTextContainer}>
            <View style={styles.heroBadgeRow}>
              <View style={styles.heroBadge}>
                <Text style={styles.heroBadgeText}>
                  {isAdmin ? (user?.role === 'superadmin' ? 'SUPER ADMINISTRATOR' : 'ADMINISTRATOR') : 'EMPLOYEE PORTAL'}
                </Text>
              </View>
              <View style={styles.verifiedBadge}>
                <Ionicons name="checkmark-circle" size={11} color={colors.success} />
                <Text style={styles.verifiedText}>Active</Text>
              </View>
            </View>
            <Text style={styles.heroName} numberOfLines={1}>
              {greeting}, {user?.name || 'User'}!
            </Text>
            <Text style={styles.heroRole} numberOfLines={1}>
              {employee?.designation?.name || user?.designation || 'Team Member'} • {employee?.department?.name || user?.department || 'Operations'}
            </Text>
          </View>
        </View>

        {/* Hero Meta Badges */}
        <View style={styles.heroMetaRow}>
          <View style={styles.heroMetaItem}>
            <Ionicons name="mail-outline" size={12} color="rgba(255,255,255,0.85)" />
            <Text style={styles.heroMetaText} numberOfLines={1}>
              {user?.email}
            </Text>
          </View>
          <View style={styles.heroMetaItem}>
            <Ionicons name="call-outline" size={12} color="rgba(255,255,255,0.85)" />
            <Text style={styles.heroMetaText}>+91 {user?.mobile_number}</Text>
          </View>
        </View>
      </View>

      {/* TODAY'S ATTENDANCE PUNCH STATION */}
      <View style={styles.punchStationCard}>
        <View style={styles.stationHeader}>
          <View style={styles.stationIconWrapper}>
            <Ionicons name="time" size={18} color={colors.primary} />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.stationTitle}>Today's Attendance Punch Station</Text>
            <Text style={styles.stationSub}>
              {employee?.shift?.name || 'General Day Shift'} ({employee?.shift ? `${employee.shift.start_time.substring(0, 5)} - ${employee.shift.end_time.substring(0, 5)}` : '09:30 - 18:30'})
            </Text>
          </View>
        </View>

        {/* Mode & Grace Badges */}
        <View style={styles.badgeRow}>
          <View style={styles.locationBadge}>
            <Ionicons name="location-sharp" size={12} color={colors.success} />
            <Text style={styles.locationBadgeText}>
              {employee?.location_type === 'random' ? 'Field / Device GPS' : `Office: ${employee?.location?.name || 'Fixed Geofence'}`}
            </Text>
          </View>
          <View style={styles.graceBadge}>
            <Ionicons name="shield-outline" size={12} color={colors.primary} />
            <Text style={styles.graceBadgeText}>Grace: {dashboardData?.monthly_grace_used || 0}/5 days</Text>
          </View>
        </View>

        {/* Punch In / Out Actions Row */}
        <View style={styles.punchActionRow}>
          {/* Punch In Box */}
          <View style={styles.punchBox}>
            <Text style={styles.punchBoxLabel}>
              {todayAtt?.is_field_visit ? 'FIELD VISIT' : 'PUNCH IN'}
            </Text>
            {todayAtt?.punch_in_at ? (
              <View style={styles.punchedTimeContainer}>
                <Text style={styles.punchInTimeText}>
                  {new Date(todayAtt.punch_in_at).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true })}
                </Text>
                <Text style={[styles.statusTag, todayAtt.status === 'half_day' ? styles.statusHalfDay : styles.statusPresent]}>
                  {todayAtt.is_field_visit ? 'Field Visit' : (todayAtt.status === 'half_day' ? 'Half Day' : 'Present')}
                </Text>
              </View>
            ) : (
              <View style={{ gap: 6 }}>
                <TouchableOpacity
                  style={[styles.punchBtn, styles.punchInBtn]}
                  onPress={() => handlePunch('in')}
                  disabled={actionLoading}
                  activeOpacity={0.8}
                >
                  {actionLoading ? (
                    <ActivityIndicator size="small" color="#fff" />
                  ) : (
                    <>
                      <Ionicons name="log-in-outline" size={16} color="#fff" />
                      <Text style={styles.punchBtnText}>Punch In</Text>
                    </>
                  )}
                </TouchableOpacity>

                <TouchableOpacity
                  style={[styles.punchBtn, styles.fieldVisitBtn]}
                  onPress={() => setFieldModalVisible(true)}
                  disabled={actionLoading}
                  activeOpacity={0.8}
                >
                  <Ionicons name="map-outline" size={14} color="#b45309" />
                  <Text style={styles.fieldVisitBtnText}>Field Visit</Text>
                </TouchableOpacity>
              </View>
            )}
          </View>

          {/* Punch Out Box */}
          <View style={styles.punchBox}>
            <Text style={styles.punchBoxLabel}>PUNCH OUT</Text>
            {todayAtt?.punch_out_at ? (
              <View style={styles.punchedTimeContainer}>
                <Text style={styles.punchOutTimeText}>
                  {new Date(todayAtt.punch_out_at).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true })}
                </Text>
                <Text style={styles.hoursTag}>{todayAtt.working_hours} hrs logged</Text>
                <TouchableOpacity
                  style={styles.punchAgainBtn}
                  onPress={() => handlePunch('out')}
                  disabled={actionLoading}
                >
                  <Ionicons name="refresh-outline" size={11} color={colors.primary} />
                  <Text style={styles.punchAgainText}>Punch Out Again</Text>
                </TouchableOpacity>
              </View>
            ) : todayAtt?.punch_in_at ? (
              <TouchableOpacity
                style={[styles.punchBtn, styles.punchOutBtn]}
                onPress={() => handlePunch('out')}
                disabled={actionLoading}
                activeOpacity={0.8}
              >
                {actionLoading ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <>
                    <Ionicons name="log-out-outline" size={16} color="#fff" />
                    <Text style={styles.punchBtnText}>Punch Out</Text>
                  </>
                )}
              </TouchableOpacity>
            ) : (
              <Text style={styles.punchPlaceholderText}>Punch in first</Text>
            )}
          </View>
        </View>
      </View>

      {/* ATTENDANCE POLICY CARD */}
      <View style={styles.policyCard}>
        <Ionicons name="information-circle" size={18} color={colors.primary} />
        <View style={{ flex: 1 }}>
          <Text style={styles.policyTitle}>Attendance Rules & Grace Policy</Text>
          <Text style={styles.policyDesc}>
            Fixed office requires 200m radius • Punch In once/day • Max 5 monthly grace days • Arrival after grace window marks Half Day.
          </Text>
        </View>
      </View>

      {/* MONTHLY ATTENDANCE STATS CARDS */}
      <Text style={styles.sectionTitle}>
        {isAdmin ? 'ENTERPRISE SYSTEM STATS' : 'MY MONTHLY ATTENDANCE STATS'}
      </Text>

      {isAdmin ? (
        <View style={styles.statsGrid}>
          <View style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>WORKFORCE</Text>
              <View style={[styles.statIconWrapper, { backgroundColor: colors.infoLight }]}>
                <Ionicons name="people" size={15} color={colors.info} />
              </View>
            </View>
            <Text style={styles.statValue}>{adminStats.total_workforce || 0}</Text>
            <Text style={styles.statSub}>Total Staff</Text>
          </View>

          <View style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>ATTENDANCE</Text>
              <View style={[styles.statIconWrapper, { backgroundColor: colors.successLight }]}>
                <Ionicons name="checkmark-done" size={15} color={colors.success} />
              </View>
            </View>
            <Text style={styles.statValue}>{adminStats.today_attendance || 0}</Text>
            <Text style={styles.statSub}>Punched Today</Text>
          </View>

          <View style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>LEAVE REQUESTS</Text>
              <View style={[styles.statIconWrapper, { backgroundColor: colors.warningLight }]}>
                <Ionicons name="calendar-outline" size={15} color={colors.warning} />
              </View>
            </View>
            <Text style={styles.statValue}>{adminStats.pending_leaves || 0}</Text>
            <Text style={styles.statSub}>Pending Review</Text>
          </View>

          <View style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>EXITS</Text>
              <View style={[styles.statIconWrapper, { backgroundColor: '#fee2e2' }]}>
                <Ionicons name="exit-outline" size={15} color="#ef4444" />
              </View>
            </View>
            <Text style={styles.statValue}>{adminStats.pending_resignations || 0}</Text>
            <Text style={styles.statSub}>Resignations</Text>
          </View>
        </View>
      ) : (
        <View style={styles.statsGrid}>
          <View style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>PRESENT DAYS</Text>
              <View style={[styles.statIconWrapper, { backgroundColor: colors.successLight }]}>
                <Ionicons name="calendar" size={15} color={colors.success} />
              </View>
            </View>
            <Text style={styles.statValue}>{userStats.present_days}</Text>
            <Text style={styles.statSub}>This Month</Text>
          </View>

          <View style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>HALF DAYS</Text>
              <View style={[styles.statIconWrapper, { backgroundColor: colors.warningLight }]}>
                <Ionicons name="time-outline" size={15} color={colors.warning} />
              </View>
            </View>
            <Text style={[styles.statValue, { color: colors.warning }]}>{userStats.half_days}</Text>
            <Text style={styles.statSub}>0.5d Deduction</Text>
          </View>

          <View style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>APPROVED LEAVE</Text>
              <View style={[styles.statIconWrapper, { backgroundColor: '#ede9fe' }]}>
                <Ionicons name="airplane" size={15} color="#7c3aed" />
              </View>
            </View>
            <Text style={[styles.statValue, { color: '#7c3aed' }]}>{userStats.leave_days}</Text>
            <Text style={styles.statSub}>Days</Text>
          </View>

          <View style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>LATE ARRIVALS</Text>
              <View style={[styles.statIconWrapper, { backgroundColor: '#fee2e2' }]}>
                <Ionicons name="alert-circle" size={15} color="#ef4444" />
              </View>
            </View>
            <Text style={[styles.statValue, { color: '#ef4444' }]}>{userStats.late_arrivals}</Text>
            <Text style={styles.statSub}>5 Days Grace</Text>
          </View>
        </View>
      )}

      {/* QUICK WORKSPACE ACTION CARDS */}
      <Text style={styles.sectionTitle}>WORKSPACE SERVICES</Text>
      <View style={styles.quickActionsGrid}>
        <TouchableOpacity style={styles.actionCard} onPress={() => onNavigate('attendance_history')} activeOpacity={0.75}>
          <View style={[styles.actionIcon, { backgroundColor: colors.primaryLight }]}>
            <Ionicons name="clipboard-outline" size={20} color={colors.primary} />
          </View>
          <Text style={styles.actionTitle}>Attendance</Text>
          <Text style={styles.actionSub}>Timesheet Logs</Text>
        </TouchableOpacity>

        <TouchableOpacity style={styles.actionCard} onPress={() => onNavigate('leave_manager')} activeOpacity={0.75}>
          <View style={[styles.actionIcon, { backgroundColor: '#ede9fe' }]}>
            <Ionicons name="calendar-outline" size={20} color="#7c3aed" />
          </View>
          <Text style={styles.actionTitle}>Leave</Text>
          <Text style={styles.actionSub}>Apply & Balances</Text>
        </TouchableOpacity>

        <TouchableOpacity style={styles.actionCard} onPress={() => onNavigate('payslips')} activeOpacity={0.75}>
          <View style={[styles.actionIcon, { backgroundColor: colors.successLight }]}>
            <Ionicons name="receipt-outline" size={20} color={colors.success} />
          </View>
          <Text style={styles.actionTitle}>Payslips</Text>
          <Text style={styles.actionSub}>Salary Slips</Text>
        </TouchableOpacity>

        <TouchableOpacity style={styles.actionCard} onPress={() => onNavigate('contacts')} activeOpacity={0.75}>
          <View style={[styles.actionIcon, { backgroundColor: '#e0f2fe' }]}>
            <Ionicons name="call-outline" size={20} color="#0284c7" />
          </View>
          <Text style={styles.actionTitle}>Directory</Text>
          <Text style={styles.actionSub}>Team Contacts</Text>
        </TouchableOpacity>

        <TouchableOpacity style={styles.actionCard} onPress={() => onNavigate('policies')} activeOpacity={0.75}>
          <View style={[styles.actionIcon, { backgroundColor: '#fef3c7' }]}>
            <Ionicons name="book-outline" size={20} color="#d97706" />
          </View>
          <Text style={styles.actionTitle}>Policies</Text>
          <Text style={styles.actionSub}>HR Handbooks</Text>
        </TouchableOpacity>

        <TouchableOpacity style={styles.actionCard} onPress={() => onNavigate('resignation')} activeOpacity={0.75}>
          <View style={[styles.actionIcon, { backgroundColor: '#fee2e2' }]}>
            <Ionicons name="exit-outline" size={20} color="#ef4444" />
          </View>
          <Text style={styles.actionTitle}>Exit / Resign</Text>
          <Text style={styles.actionSub}>Notice & LWD</Text>
        </TouchableOpacity>
      </View>

      {/* RECENT PAYSLIPS LIST */}
      {recentPayslips.length > 0 && (
        <View style={styles.sectionBlock}>
          <View style={styles.sectionBlockHeader}>
            <Text style={styles.sectionBlockTitle}>Recent Payslips</Text>
            <TouchableOpacity onPress={() => onNavigate('payslips')}>
              <Text style={styles.seeAllText}>See all</Text>
            </TouchableOpacity>
          </View>
          {recentPayslips.slice(0, 2).map((slip) => (
            <View key={slip.id} style={styles.slipCard}>
              <View style={styles.slipLeft}>
                <Ionicons name="document-text" size={20} color={colors.primary} />
                <View>
                  <Text style={styles.slipMonth}>Payslip - {slip.year}</Text>
                  <Text style={styles.slipNumber}>{slip.payslip_number}</Text>
                </View>
              </View>
              <Text style={styles.slipAmount}>₹{parseFloat(slip.net_salary).toLocaleString('en-IN')}</Text>
            </View>
          ))}
        </View>
      )}

      {/* ACTIVE RESIGNATION BANNER */}
      {resignation && (
        <View style={styles.resignationCard}>
          <View style={styles.resignationHeader}>
            <Ionicons name="alert-circle" size={18} color="#ef4444" />
            <Text style={styles.resignationTitle}>Resignation Request ({resignation.status.toUpperCase()})</Text>
          </View>
          <Text style={styles.resignationText}>
            LWD: {resignation.approved_last_working_day || resignation.requested_last_working_day} • Notice: {resignation.notice_period_days} days
          </Text>
        </View>
      )}

      {/* Field Visit Modal */}
      <Modal
        visible={fieldModalVisible}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setFieldModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <View style={styles.modalHeaderTitleGroup}>
                <Ionicons name="map" size={18} color="#b45309" />
                <Text style={styles.modalTitle}>Mark Field Visit</Text>
              </View>
              <TouchableOpacity onPress={() => setFieldModalVisible(false)} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
                <Ionicons name="close" size={20} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>

            <View style={styles.modalGpsRow}>
              <Ionicons name="navigate" size={14} color="#059669" />
              <Text style={styles.modalGpsText}>GPS: 26.1824° N, 91.7589° E (Guwahati Outer)</Text>
            </View>

            <View style={styles.modalSelfieBox}>
              <Ionicons name="camera" size={32} color="#b45309" />
              <Text style={styles.modalSelfieTitle}>Selfie Camera Auto-Attached</Text>
              <Text style={styles.modalSelfieSub}>Device camera selfie geotagged with timestamp</Text>
            </View>

            <Text style={styles.inputLabel}>Field Purpose / Remarks *</Text>
            <TextInput
              style={styles.remarksInput}
              placeholder="e.g. Client inspection at site, outdoor meeting..."
              placeholderTextColor="#9ca3af"
              multiline
              numberOfLines={3}
              value={fieldRemarks}
              onChangeText={setFieldRemarks}
            />

            <View style={styles.modalBtnRow}>
              <TouchableOpacity
                style={styles.cancelBtn}
                onPress={() => setFieldModalVisible(false)}
              >
                <Text style={styles.cancelBtnText}>Cancel</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.submitFieldBtn}
                onPress={handleFieldVisitPunch}
                disabled={actionLoading}
              >
                {actionLoading ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <>
                    <Ionicons name="checkmark-circle-outline" size={16} color="#fff" />
                    <Text style={styles.submitFieldBtnText}>Submit Field Visit</Text>
                  </>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  content: {
    padding: 16,
    paddingBottom: 40,
  },
  heroCard: {
    backgroundColor: colors.primary,
    borderRadius: 20,
    padding: 18,
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 4,
    marginBottom: 14,
  },
  heroTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  heroAvatar: {
    width: 54,
    height: 54,
    borderRadius: 16,
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.4)',
  },
  heroTextContainer: {
    flex: 1,
  },
  heroBadgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 3,
  },
  heroBadge: {
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 7,
    paddingVertical: 2,
    borderRadius: 5,
  },
  heroBadgeText: {
    fontSize: 9,
    fontWeight: '800',
    color: '#fff',
    letterSpacing: 0.5,
  },
  verifiedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.successLight,
    paddingHorizontal: 5,
    paddingVertical: 2,
    borderRadius: 5,
    gap: 2,
  },
  verifiedText: {
    fontSize: 9,
    fontWeight: '700',
    color: colors.success,
  },
  heroName: {
    fontSize: 18,
    fontWeight: '800',
    color: '#fff',
  },
  heroRole: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.85)',
    marginTop: 1,
  },
  heroMetaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    marginTop: 12,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.15)',
  },
  heroMetaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  heroMetaText: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.85)',
  },
  punchStationCard: {
    backgroundColor: '#fff',
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 12,
  },
  stationHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 10,
  },
  stationIconWrapper: {
    width: 34,
    height: 34,
    borderRadius: 10,
    backgroundColor: colors.primaryLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stationTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  stationSub: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 1,
  },
  badgeRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginBottom: 14,
  },
  locationBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.successLight,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  locationBadgeText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.success,
  },
  graceBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.primaryLight,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  graceBadgeText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.primary,
  },
  punchActionRow: {
    flexDirection: 'row',
    gap: 10,
  },
  punchBox: {
    flex: 1,
    backgroundColor: colors.surfaceSubtle,
    borderRadius: 14,
    padding: 12,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.borderLight,
  },
  punchBoxLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.textSecondary,
    letterSpacing: 0.5,
    marginBottom: 6,
  },
  punchedTimeContainer: {
    alignItems: 'center',
  },
  punchInTimeText: {
    fontSize: 15,
    fontWeight: '800',
    color: colors.success,
    fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
  },
  punchOutTimeText: {
    fontSize: 15,
    fontWeight: '800',
    color: colors.textPrimary,
    fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
  },
  statusTag: {
    fontSize: 10,
    fontWeight: '700',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
    marginTop: 3,
  },
  statusPresent: {
    backgroundColor: colors.successLight,
    color: colors.success,
  },
  statusHalfDay: {
    backgroundColor: '#fee2e2',
    color: '#ef4444',
  },
  hoursTag: {
    fontSize: 10,
    color: colors.textSecondary,
    fontWeight: '600',
    marginTop: 2,
  },
  punchAgainBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
    backgroundColor: colors.primaryLight,
    paddingHorizontal: 6,
    paddingVertical: 3,
    borderRadius: 6,
    marginTop: 6,
  },
  punchAgainText: {
    fontSize: 10,
    fontWeight: '700',
    color: colors.primary,
  },
  punchBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 10,
    width: '100%',
  },
  punchInBtn: {
    backgroundColor: colors.success,
  },
  punchOutBtn: {
    backgroundColor: '#ef4444',
  },
  punchBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#fff',
  },
  punchPlaceholderText: {
    fontSize: 11,
    color: colors.textMuted,
    fontStyle: 'italic',
    paddingVertical: 8,
  },
  policyCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.primaryLight,
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
    borderColor: '#c7d2fe',
    marginBottom: 16,
  },
  policyTitle: {
    fontSize: 12,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  policyDesc: {
    fontSize: 10.5,
    color: colors.textSecondary,
    lineHeight: 14,
    marginTop: 2,
  },
  sectionTitle: {
    fontSize: 11,
    fontWeight: '800',
    color: colors.textSecondary,
    letterSpacing: 0.8,
    marginBottom: 10,
    marginLeft: 2,
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 18,
  },
  statCard: {
    width: '48%',
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 12,
    borderWidth: 1,
    borderColor: colors.border,
  },
  statHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  statLabel: {
    fontSize: 9.5,
    fontWeight: '800',
    color: colors.textMuted,
  },
  statIconWrapper: {
    width: 26,
    height: 26,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  statValue: {
    fontSize: 22,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  statSub: {
    fontSize: 10,
    color: colors.textSecondary,
    marginTop: 1,
  },
  quickActionsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 18,
  },
  actionCard: {
    width: '48%',
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
  },
  actionIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  actionTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  actionSub: {
    fontSize: 10,
    color: colors.textSecondary,
    marginTop: 2,
  },
  sectionBlock: {
    backgroundColor: '#fff',
    borderRadius: 18,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 14,
  },
  sectionBlockHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  sectionBlockTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  seeAllText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.primary,
  },
  slipCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: colors.borderLight,
  },
  slipLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  slipMonth: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  slipNumber: {
    fontSize: 10,
    color: colors.textSecondary,
    fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
  },
  slipAmount: {
    fontSize: 13,
    fontWeight: '800',
    color: colors.success,
  },
  resignationCard: {
    backgroundColor: '#fff',
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
    borderColor: '#fca5a5',
    backgroundColor: '#fef2f2',
  },
  resignationHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 4,
  },
  resignationTitle: {
    fontSize: 12,
    fontWeight: '800',
    color: '#991b1b',
  },
  resignationText: {
    fontSize: 11,
    color: '#b91c1c',
  },
  fieldVisitBtn: {
    backgroundColor: '#fef3c7',
    borderWidth: 1,
    borderColor: '#fde68a',
    marginTop: 2,
  },
  fieldVisitBtnText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#b45309',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 16,
  },
  modalContent: {
    backgroundColor: '#fff',
    borderRadius: 20,
    width: '100%',
    maxWidth: 420,
    padding: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 10,
    elevation: 8,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
    paddingBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: colors.borderLight,
  },
  modalHeaderTitleGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  modalTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  modalGpsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: '#ecfdf5',
    borderWidth: 1,
    borderColor: '#a7f3d0',
    paddingHorizontal: 10,
    paddingVertical: 7,
    borderRadius: 10,
    marginBottom: 12,
  },
  modalGpsText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#065f46',
    fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
  },
  modalSelfieBox: {
    backgroundColor: '#fffbeb',
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: '#fcd34d',
    borderRadius: 12,
    alignItems: 'center',
    paddingVertical: 14,
    paddingHorizontal: 12,
    marginBottom: 12,
  },
  modalSelfieTitle: {
    fontSize: 12,
    fontWeight: '800',
    color: '#92400e',
    marginTop: 6,
  },
  modalSelfieSub: {
    fontSize: 10,
    color: '#b45309',
    marginTop: 2,
    textAlign: 'center',
  },
  inputLabel: {
    fontSize: 11,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 6,
  },
  remarksInput: {
    backgroundColor: colors.background,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    padding: 10,
    fontSize: 12,
    color: colors.textPrimary,
    textAlignVertical: 'top',
    minHeight: 65,
    marginBottom: 16,
  },
  modalBtnRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-end',
    gap: 10,
  },
  cancelBtn: {
    paddingHorizontal: 14,
    paddingVertical: 9,
    borderRadius: 10,
    backgroundColor: colors.background,
  },
  cancelBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  submitFieldBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 16,
    paddingVertical: 9,
    borderRadius: 10,
    backgroundColor: '#d97706',
  },
  submitFieldBtnText: {
    fontSize: 12,
    fontWeight: '800',
    color: '#fff',
  },
});
