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

export const ResignationScreen = ({ onNavigate }) => {
  const { token, user } = useAuth();
  const isAdmin = user?.role === 'superadmin' || user?.role === 'admin';
  const [resignations, setResignations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Modal State
  const [modalVisible, setModalVisible] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [requestedLwd, setRequestedLwd] = useState('');
  const [noticeDays, setNoticeDays] = useState('30');
  const [reason, setReason] = useState('');

  const fetchResignations = useCallback(async () => {
    try {
      const baseUrl = await getApiBaseUrl();
      const res = await fetch(`${baseUrl}/mobile/resignations`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });

      if (res.ok) {
        const json = await res.json();
        setResignations(json.resignations || []);
      }
    } catch (e) {
      console.log('Error fetching resignations', e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useEffect(() => {
    // Set default requested LWD to 30 days from now
    const d = new Date();
    d.setDate(d.getDate() + 30);
    setRequestedLwd(d.toISOString().substring(0, 10));

    fetchResignations();
  }, [fetchResignations]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchResignations();
  };

  const handleSubmit = async () => {
    if (!reason.trim()) {
      Alert.alert('Required', 'Please enter reason for resignation.');
      return;
    }

    setSubmitting(true);
    try {
      const baseUrl = await getApiBaseUrl();
      const res = await fetch(`${baseUrl}/mobile/resignations/submit`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          requested_last_working_day: requestedLwd,
          notice_period_days: parseInt(noticeDays, 10) || 30,
          reason: reason.trim(),
        }),
      });

      const json = await res.json();
      if (res.ok && json.success) {
        Alert.alert('Submitted', json.message);
        setModalVisible(false);
        setReason('');
        fetchResignations();
      } else {
        Alert.alert('Error', json.message || 'Could not submit resignation.');
      }
    } catch {
      Alert.alert('Network Error', 'Failed to reach server.');
    } finally {
      setSubmitting(false);
    }
  };

  const hasActive = resignations.some((r) => r.status === 'pending' || r.status === 'approved');

  return (
    <View style={styles.container}>
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => onNavigate('dashboard')}>
          <Ionicons name="arrow-back" size={20} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.topBarTitle}>Exit & Resignation</Text>
        {!hasActive && (
          <TouchableOpacity style={styles.applyBtn} onPress={() => setModalVisible(true)}>
            <Ionicons name="exit-outline" size={15} color="#fff" />
            <Text style={styles.applyBtnText}>Apply</Text>
          </TouchableOpacity>
        )}
      </View>

      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={resignations}
          keyExtractor={(item) => item.id.toString()}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />}
          contentContainerStyle={styles.listContent}
          renderItem={({ item }) => {
            const isApproved = item.status === 'approved';
            const isRejected = item.status === 'rejected';

            return (
              <View style={styles.card}>
                <View style={styles.cardHeader}>
                  <View>
                    <Text style={styles.empTitle}>
                      {isAdmin && item.employee ? `${item.employee.full_name} (${item.employee.employee_id})` : 'My Resignation Notice'}
                    </Text>
                    <Text style={styles.appliedDate}>Applied: {item.resignation_date}</Text>
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

                <View style={styles.divider} />

                <View style={styles.infoRow}>
                  <View style={styles.infoCol}>
                    <Text style={styles.infoLabel}>REQUESTED LWD</Text>
                    <Text style={styles.infoVal}>{item.requested_last_working_day}</Text>
                  </View>
                  <View style={styles.infoCol}>
                    <Text style={styles.infoLabel}>APPROVED LWD</Text>
                    <Text style={[styles.infoVal, { color: isApproved ? colors.success : colors.textPrimary }]}>
                      {item.approved_last_working_day || 'Under Review'}
                    </Text>
                  </View>
                  <View style={styles.infoCol}>
                    <Text style={styles.infoLabel}>NOTICE</Text>
                    <Text style={styles.infoVal}>{item.notice_period_days} Days</Text>
                  </View>
                </View>

                <Text style={styles.reasonText}>Reason: {item.reason}</Text>
                {item.action_remarks && (
                  <Text style={styles.remarksText}>Admin Remark: {item.action_remarks}</Text>
                )}
              </View>
            );
          }}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Ionicons name="checkmark-done-circle-outline" size={44} color={colors.textMuted} />
              <Text style={styles.emptyText}>No resignation or separation requests found.</Text>
            </View>
          }
        />
      )}

      {/* SUBMIT RESIGNATION MODAL */}
      <Modal visible={modalVisible} animationType="slide" transparent onRequestClose={() => setModalVisible(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Resignation Submission</Text>
              <TouchableOpacity onPress={() => setModalVisible(false)}>
                <Ionicons name="close" size={20} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>

            <ScrollView style={styles.modalBody}>
              <Text style={styles.inputLabel}>REQUESTED LAST WORKING DAY (YYYY-MM-DD)</Text>
              <TextInput style={styles.textInput} value={requestedLwd} onChangeText={setRequestedLwd} />

              <Text style={styles.inputLabel}>NOTICE PERIOD DAYS</Text>
              <TextInput style={styles.textInput} value={noticeDays} onChangeText={setNoticeDays} keyboardType="numeric" />

              <Text style={styles.inputLabel}>REASON FOR RESIGNATION</Text>
              <TextInput
                style={[styles.textInput, { height: 90, textAlignVertical: 'top' }]}
                value={reason}
                onChangeText={setReason}
                placeholder="Specify rationale for separation (career growth, higher education, relocation)..."
                multiline
              />

              <TouchableOpacity style={styles.submitBtn} onPress={handleSubmit} disabled={submitting}>
                {submitting ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <Text style={styles.submitBtnText}>Submit Resignation</Text>
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
    backgroundColor: '#ef4444',
    paddingHorizontal: 10,
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
    paddingVertical: 60,
  },
  emptyText: {
    fontSize: 13,
    color: colors.textMuted,
    marginTop: 10,
    fontWeight: '600',
  },
  card: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 12,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  empTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  appliedDate: {
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
  divider: {
    height: 1,
    backgroundColor: colors.borderLight,
    marginVertical: 12,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: colors.surfaceSubtle,
    padding: 10,
    borderRadius: 10,
  },
  infoCol: {
    alignItems: 'center',
    flex: 1,
  },
  infoLabel: {
    fontSize: 9,
    fontWeight: '800',
    color: colors.textMuted,
    marginBottom: 2,
  },
  infoVal: {
    fontSize: 11.5,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  reasonText: {
    fontSize: 12,
    color: colors.textPrimary,
    marginTop: 10,
  },
  remarksText: {
    fontSize: 11,
    color: colors.primary,
    fontWeight: '600',
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
  submitBtn: {
    backgroundColor: '#ef4444',
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
