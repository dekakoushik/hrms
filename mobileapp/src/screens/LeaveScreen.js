import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  TextInput,
  Modal,
  Alert,
  ActivityIndicator,
  RefreshControl,
  ScrollView,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';
import { getApiBaseUrl } from '../config/api';

export const LeaveScreen = ({ onNavigate }) => {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [leaveTypes, setLeaveTypes] = useState([]);
  const [leaveBalances, setLeaveBalances] = useState([]);
  const [leaveRequests, setLeaveRequests] = useState([]);

  // Modal State
  const [modalVisible, setModalVisible] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [selectedTypeId, setSelectedTypeId] = useState(null);
  const [startDate, setStartDate] = useState(new Date().toISOString().substring(0, 10));
  const [endDate, setEndDate] = useState(new Date().toISOString().substring(0, 10));
  const [isHalfDay, setIsHalfDay] = useState(false);
  const [reason, setReason] = useState('');

  const fetchLeaveData = useCallback(async () => {
    try {
      const baseUrl = await getApiBaseUrl();
      const res = await fetch(`${baseUrl}/mobile/leaves`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });

      if (res.ok) {
        const json = await res.json();
        setLeaveTypes(json.leave_types || []);
        setLeaveBalances(json.employee?.leave_balances || []);
        setLeaveRequests(json.leave_requests || []);
        if (json.leave_types?.length > 0 && !selectedTypeId) {
          setSelectedTypeId(json.leave_types[0].id);
        }
      }
    } catch (e) {
      console.log('Error fetching leaves:', e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, selectedTypeId]);

  useEffect(() => {
    fetchLeaveData();
  }, [fetchLeaveData]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchLeaveData();
  };

  const handleApply = async () => {
    if (!reason.trim()) {
      Alert.alert('Required', 'Please enter reason for leave.');
      return;
    }

    setSubmitting(true);
    try {
      const baseUrl = await getApiBaseUrl();
      const res = await fetch(`${baseUrl}/mobile/leaves/apply`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          leave_type_id: selectedTypeId,
          start_date: startDate,
          end_date: endDate,
          is_half_day: isHalfDay,
          reason: reason.trim(),
        }),
      });

      const json = await res.json();
      if (res.ok && json.success) {
        Alert.alert('Success', json.message);
        setModalVisible(false);
        setReason('');
        fetchLeaveData();
      } else {
        Alert.alert('Application Error', json.message || 'Could not submit leave.');
      }
    } catch {
      Alert.alert('Error', 'Network connection failed.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.container}>
      {/* Top Header */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => onNavigate('dashboard')}>
          <Ionicons name="arrow-back" size={20} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.topBarTitle}>Leave Manager</Text>
        <TouchableOpacity style={styles.applyBtn} onPress={() => setModalVisible(true)}>
          <Ionicons name="add" size={16} color="#fff" />
          <Text style={styles.applyBtnText}>Apply</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={leaveRequests}
          keyExtractor={(item) => item.id.toString()}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />}
          contentContainerStyle={styles.listContent}
          ListHeaderComponent={
            <>
              {/* Leave Balances Grid */}
              <Text style={styles.sectionTitle}>MY LEAVE BALANCES (ANNUAL)</Text>
              <View style={styles.balanceGrid}>
                {leaveBalances.map((bal) => (
                  <View key={bal.id} style={styles.balanceCard}>
                    <Text style={styles.balanceCode}>{bal.leave_type?.code || 'LV'}</Text>
                    <Text style={styles.balanceDays}>{bal.remaining_days}</Text>
                    <Text style={styles.balanceTotal}>/ {bal.total_allocated} Days Rem.</Text>
                  </View>
                ))}
              </View>

              <Text style={[styles.sectionTitle, { marginTop: 14 }]}>LEAVE APPLICATION HISTORY</Text>
            </>
          }
          renderItem={({ item }) => {
            const isApproved = item.status === 'approved';
            const isRejected = item.status === 'rejected';

            return (
              <View style={styles.leaveCard}>
                <View style={styles.leaveTopRow}>
                  <View>
                    <Text style={styles.leaveTypeTitle}>{item.leave_type?.name || 'Leave'}</Text>
                    <Text style={styles.leaveDates}>
                      {item.start_date} {item.end_date !== item.start_date ? `to ${item.end_date}` : ''} ({item.days_count}d)
                    </Text>
                  </View>
                  <View
                    style={[
                      styles.statusBadge,
                      isApproved ? styles.badgeApproved : isRejected ? styles.badgeRejected : styles.badgePending,
                    ]}
                  >
                    <Text
                      style={[
                        styles.statusBadgeText,
                        isApproved ? styles.textApproved : isRejected ? styles.textRejected : styles.textPending,
                      ]}
                    >
                      {item.status.toUpperCase()}
                    </Text>
                  </View>
                </View>
                <Text style={styles.leaveReason}>{item.reason}</Text>
              </View>
            );
          }}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Ionicons name="airplane-outline" size={40} color={colors.textMuted} />
              <Text style={styles.emptyText}>No leave applications submitted.</Text>
            </View>
          }
        />
      )}

      {/* APPLY LEAVE MODAL */}
      <Modal visible={modalVisible} animationType="slide" transparent onRequestClose={() => setModalVisible(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Apply for Leave</Text>
              <TouchableOpacity onPress={() => setModalVisible(false)}>
                <Ionicons name="close" size={20} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>

            <ScrollView style={styles.modalBody}>
              <Text style={styles.inputLabel}>SELECT LEAVE TYPE</Text>
              <View style={styles.typeSelector}>
                {leaveTypes.map((type) => (
                  <TouchableOpacity
                    key={type.id}
                    style={[styles.typeOption, selectedTypeId === type.id && styles.typeOptionSelected]}
                    onPress={() => setSelectedTypeId(type.id)}
                  >
                    <Text style={[styles.typeOptionText, selectedTypeId === type.id && styles.typeOptionTextSelected]}>
                      {type.code}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>

              <Text style={styles.inputLabel}>START DATE (YYYY-MM-DD)</Text>
              <TextInput style={styles.textInput} value={startDate} onChangeText={setStartDate} placeholder="2026-10-09" />

              <Text style={styles.inputLabel}>END DATE (YYYY-MM-DD)</Text>
              <TextInput style={styles.textInput} value={endDate} onChangeText={setEndDate} placeholder="2026-10-10" />

              <TouchableOpacity style={styles.halfDayRow} onPress={() => setIsHalfDay(!isHalfDay)}>
                <Ionicons
                  name={isHalfDay ? 'checkbox' : 'square-outline'}
                  size={20}
                  color={isHalfDay ? colors.primary : colors.textSecondary}
                />
                <Text style={styles.halfDayLabel}>Half Day Leave (0.5 day deduction)</Text>
              </TouchableOpacity>

              <Text style={styles.inputLabel}>REASON FOR LEAVE</Text>
              <TextInput
                style={[styles.textInput, { height: 75, textAlignVertical: 'top' }]}
                value={reason}
                onChangeText={setReason}
                placeholder="Reason (medical, personal, family)..."
                multiline
              />

              <TouchableOpacity style={styles.submitBtn} onPress={handleApply} disabled={submitting}>
                {submitting ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <Text style={styles.submitBtnText}>Submit Application</Text>
                )}
              </TouchableOpacity>
            </ScrollView>
          </View>
        </View>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 14,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  backBtn: {
    padding: 4,
  },
  topBarTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  applyBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.primary,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
  },
  applyBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#fff',
  },
  listContent: {
    padding: 16,
    paddingBottom: 40,
  },
  centerContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 40,
  },
  emptyText: {
    fontSize: 12,
    color: colors.textMuted,
    marginTop: 8,
  },
  sectionTitle: {
    fontSize: 11,
    fontWeight: '800',
    color: colors.textSecondary,
    letterSpacing: 0.8,
    marginBottom: 8,
  },
  balanceGrid: {
    flexDirection: 'row',
    gap: 8,
  },
  balanceCard: {
    flex: 1,
    backgroundColor: '#fff',
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
  },
  balanceCode: {
    fontSize: 11,
    fontWeight: '800',
    color: colors.primary,
  },
  balanceDays: {
    fontSize: 20,
    fontWeight: '800',
    color: colors.textPrimary,
    marginTop: 2,
  },
  balanceTotal: {
    fontSize: 10,
    color: colors.textSecondary,
    marginTop: 1,
  },
  leaveCard: {
    backgroundColor: '#fff',
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 10,
  },
  leaveTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  leaveTypeTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  leaveDates: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 2,
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  badgeApproved: {
    backgroundColor: colors.successLight,
  },
  badgeRejected: {
    backgroundColor: '#fee2e2',
  },
  badgePending: {
    backgroundColor: '#fef3c7',
  },
  statusBadgeText: {
    fontSize: 10,
    fontWeight: '800',
  },
  textApproved: {
    color: colors.success,
  },
  textRejected: {
    color: '#ef4444',
  },
  textPending: {
    color: '#d97706',
  },
  leaveReason: {
    fontSize: 11.5,
    color: colors.textPrimary,
    marginTop: 4,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalCard: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 20,
    maxHeight: '85%',
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingBottom: 14,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  modalTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  modalBody: {
    marginTop: 14,
  },
  inputLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.textSecondary,
    marginBottom: 6,
    marginTop: 10,
  },
  typeSelector: {
    flexDirection: 'row',
    gap: 8,
  },
  typeOption: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    backgroundColor: colors.surfaceSubtle,
  },
  typeOptionSelected: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryLight,
  },
  typeOptionText: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  typeOptionTextSelected: {
    color: colors.primary,
  },
  textInput: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 13,
    color: colors.textPrimary,
    backgroundColor: colors.surfaceSubtle,
  },
  halfDayRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 12,
  },
  halfDayLabel: {
    fontSize: 12,
    color: colors.textPrimary,
    fontWeight: '600',
  },
  submitBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 13,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 20,
    marginBottom: 30,
  },
  submitBtnText: {
    fontSize: 14,
    fontWeight: '800',
    color: '#fff',
  },
});
