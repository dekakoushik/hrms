import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  Modal,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';

export const SettingsManagerScreen = ({ onNavigate }) => {
  const { token, apiUrl } = useAuth();
  const [activeTab, setActiveTab] = useState('shifts'); // 'shifts' | 'smtp'

  // Shifts state
  const [shifts, setShifts] = useState([]);
  const [loadingShifts, setLoadingShifts] = useState(true);
  const [shiftModalVisible, setShiftModalVisible] = useState(false);
  const [newShiftName, setNewShiftName] = useState('');
  const [newShiftCode, setNewShiftCode] = useState('');
  const [newShiftStart, setNewShiftStart] = useState('09:30');
  const [newShiftEnd, setNewShiftEnd] = useState('18:30');
  const [newShiftGrace, setNewShiftGrace] = useState('15');
  const [newShiftNight, setNewShiftNight] = useState(false);
  const [savingShift, setSavingShift] = useState(false);

  // SMTP state
  const [smtp, setSmtp] = useState(null);
  const [loadingSmtp, setLoadingSmtp] = useState(true);
  const [mailHost, setMailHost] = useState('');
  const [mailPort, setMailPort] = useState('587');
  const [mailUsername, setMailUsername] = useState('');
  const [mailPassword, setMailPassword] = useState('');
  const [mailEncryption, setMailEncryption] = useState('tls');
  const [mailFromAddress, setMailFromAddress] = useState('');
  const [mailFromName, setMailFromName] = useState('');
  const [savingSmtp, setSavingSmtp] = useState(false);

  useEffect(() => {
    fetchShifts();
    fetchSmtp();
  }, []);

  const fetchShifts = async () => {
    try {
      setLoadingShifts(true);
      const res = await fetch(`${apiUrl}/settings/shifts`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });
      const data = await res.json();
      if (data.shifts) setShifts(data.shifts);
    } catch {
      // offline fallback
    } finally {
      setLoadingShifts(false);
    }
  };

  const fetchSmtp = async () => {
    try {
      setLoadingSmtp(true);
      const res = await fetch(`${apiUrl}/settings/smtp`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });
      const data = await res.json();
      if (data.smtp) {
        setSmtp(data.smtp);
        setMailHost(data.smtp.mail_host || '');
        setMailPort(String(data.smtp.mail_port || 587));
        setMailUsername(data.smtp.mail_username || '');
        setMailEncryption(data.smtp.mail_encryption || 'tls');
        setMailFromAddress(data.smtp.mail_from_address || '');
        setMailFromName(data.smtp.mail_from_name || '');
      }
    } catch {
      // offline fallback
    } finally {
      setLoadingSmtp(false);
    }
  };

  const handleCreateShift = async () => {
    if (!newShiftName || !newShiftCode || !newShiftStart || !newShiftEnd) {
      Alert.alert('Required', 'Please fill all required shift fields.');
      return;
    }

    setSavingShift(true);
    try {
      const res = await fetch(`${apiUrl}/settings/shifts`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          name: newShiftName.trim(),
          code: newShiftCode.trim().toUpperCase(),
          start_time: newShiftStart.trim(),
          end_time: newShiftEnd.trim(),
          grace_period_minutes: parseInt(newShiftGrace, 10) || 15,
          half_day_hours: 4,
          full_day_hours: 8,
          is_night_shift: newShiftNight,
        }),
      });
      const data = await res.json();
      if (res.ok) {
        Alert.alert('Success', 'Work shift created successfully!');
        setShiftModalVisible(false);
        setNewShiftName('');
        setNewShiftCode('');
        fetchShifts();
      } else {
        Alert.alert('Error', data.message || 'Failed to create shift');
      }
    } catch (e) {
      Alert.alert('Network Error', e.message);
    } finally {
      setSavingShift(false);
    }
  };

  const handleSaveSmtp = async () => {
    if (!mailHost || !mailPort || !mailFromAddress || !mailFromName) {
      Alert.alert('Required', 'Please enter required SMTP settings.');
      return;
    }

    setSavingSmtp(true);
    try {
      const res = await fetch(`${apiUrl}/settings/smtp`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          mail_mailer: 'smtp',
          mail_host: mailHost.trim(),
          mail_port: parseInt(mailPort, 10) || 587,
          mail_username: mailUsername.trim(),
          mail_password: mailPassword.trim() || undefined,
          mail_encryption: mailEncryption,
          mail_from_address: mailFromAddress.trim(),
          mail_from_name: mailFromName.trim(),
          is_active: true,
        }),
      });
      const data = await res.json();
      if (res.ok) {
        Alert.alert('Success', 'SMTP configuration updated successfully!');
        fetchSmtp();
      } else {
        Alert.alert('Error', data.message || 'Failed to save SMTP');
      }
    } catch (e) {
      Alert.alert('Network Error', e.message);
    } finally {
      setSavingSmtp(false);
    }
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {/* Breadcrumb Navigation */}
      <View style={styles.breadcrumbRow}>
        <TouchableOpacity style={styles.backBtn} onPress={() => onNavigate('dashboard')}>
          <Ionicons name="arrow-back" size={16} color={colors.textSecondary} />
          <Text style={styles.backBtnText}>Dashboard</Text>
        </TouchableOpacity>
        <Text style={styles.breadcrumbCurrent}>Settings Module</Text>
      </View>

      {/* Segmented Control Tabs */}
      <View style={styles.tabContainer}>
        <TouchableOpacity
          style={[styles.tabButton, activeTab === 'shifts' && styles.tabButtonActive]}
          onPress={() => setActiveTab('shifts')}
        >
          <Ionicons
            name="time-outline"
            size={16}
            color={activeTab === 'shifts' ? colors.primary : colors.textSecondary}
          />
          <Text
            style={[styles.tabButtonText, activeTab === 'shifts' && styles.tabButtonTextActive]}
          >
            Shift Manager
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.tabButton, activeTab === 'smtp' && styles.tabButtonActive]}
          onPress={() => setActiveTab('smtp')}
        >
          <Ionicons
            name="mail-outline"
            size={16}
            color={activeTab === 'smtp' ? colors.primary : colors.textSecondary}
          />
          <Text
            style={[styles.tabButtonText, activeTab === 'smtp' && styles.tabButtonTextActive]}
          >
            SMTP Mail Gateway
          </Text>
        </TouchableOpacity>
      </View>

      {/* TAB 1: SHIFT MANAGER */}
      {activeTab === 'shifts' && (
        <View style={styles.tabContent}>
          <View style={styles.sectionHeaderRow}>
            <div>
              <Text style={styles.sectionTitle}>Work Shifts ({shifts.length})</Text>
              <Text style={styles.sectionSubtitle}>Standard Indian workplace hours & grace period</Text>
            </div>
            <TouchableOpacity
              style={styles.addBtn}
              onPress={() => setShiftModalVisible(true)}
            >
              <Ionicons name="add-circle" size={16} color={colors.surface} />
              <Text style={styles.addBtnText}>Add Shift</Text>
            </TouchableOpacity>
          </View>

          {loadingShifts ? (
            <ActivityIndicator style={{ marginTop: 20 }} color={colors.primary} />
          ) : (
            <View style={styles.shiftsList}>
              {shifts.map((shift) => (
                <View key={shift.id} style={styles.shiftCard}>
                  <View style={styles.shiftCardTop}>
                    <View style={styles.shiftCodeBadge}>
                      <Text style={styles.shiftCodeText}>{shift.code}</Text>
                    </View>
                    {shift.is_night_shift ? (
                      <View style={styles.nightBadge}>
                        <Ionicons name="moon" size={10} color="#7E22CE" />
                        <Text style={styles.nightBadgeText}>Night Shift</Text>
                      </View>
                    ) : (
                      <View style={styles.dayBadge}>
                        <Ionicons name="sunny" size={10} color="#D97706" />
                        <Text style={styles.dayBadgeText}>Day Shift</Text>
                      </View>
                    )}
                  </View>

                  <Text style={styles.shiftName}>{shift.name}</Text>
                  <Text style={styles.shiftTime}>
                    {shift.start_time.substring(0, 5)} - {shift.end_time.substring(0, 5)}
                  </Text>

                  <View style={styles.shiftMetaRow}>
                    <Text style={styles.shiftMetaText}>Grace: {shift.grace_period_minutes}m</Text>
                    <Text style={styles.shiftMetaText}>•</Text>
                    <Text style={styles.shiftMetaText}>Full: {shift.full_day_hours}h</Text>
                    <Text style={styles.shiftMetaText}>•</Text>
                    <Text style={styles.shiftMetaText}>Half: {shift.half_day_hours}h</Text>
                  </View>
                </View>
              ))}
            </View>
          )}
        </View>
      )}

      {/* TAB 2: SMTP CONFIGURATION */}
      {activeTab === 'smtp' && (
        <View style={styles.tabContent}>
          <View style={styles.sectionHeaderRow}>
            <div>
              <Text style={styles.sectionTitle}>SMTP Configuration</Text>
              <Text style={styles.sectionSubtitle}>Outbound mail gateway for alerts & payslips</Text>
            </div>
            <View style={styles.gatewayActiveBadge}>
              <Text style={styles.gatewayActiveText}>PORT {mailPort}</Text>
            </View>
          </View>

          {loadingSmtp ? (
            <ActivityIndicator style={{ marginTop: 20 }} color={colors.primary} />
          ) : (
            <View style={styles.card}>
              <View style={styles.inputGroup}>
                <Text style={styles.inputLabel}>SMTP HOST *</Text>
                <TextInput
                  style={styles.input}
                  value={mailHost}
                  onChangeText={setMailHost}
                  placeholder="smtp.gmail.com"
                  autoCapitalize="none"
                />
              </View>

              <View style={styles.twoColRow}>
                <View style={[styles.inputGroup, { flex: 1 }]}>
                  <Text style={styles.inputLabel}>PORT *</Text>
                  <TextInput
                    style={styles.input}
                    value={mailPort}
                    onChangeText={setMailPort}
                    placeholder="587"
                    keyboardType="numeric"
                  />
                </View>
                <View style={[styles.inputGroup, { flex: 1 }]}>
                  <Text style={styles.inputLabel}>ENCRYPTION *</Text>
                  <TextInput
                    style={styles.input}
                    value={mailEncryption}
                    onChangeText={setMailEncryption}
                    placeholder="tls"
                    autoCapitalize="none"
                  />
                </View>
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.inputLabel}>AUTH USERNAME / EMAIL</Text>
                <TextInput
                  style={styles.input}
                  value={mailUsername}
                  onChangeText={setMailUsername}
                  placeholder="notifications@hrms.in"
                  autoCapitalize="none"
                />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.inputLabel}>AUTH PASSWORD / APP KEY</Text>
                <TextInput
                  style={styles.input}
                  value={mailPassword}
                  onChangeText={setMailPassword}
                  placeholder="Leave blank to keep existing password"
                  secureTextEntry
                />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.inputLabel}>FROM EMAIL ADDRESS *</Text>
                <TextInput
                  style={styles.input}
                  value={mailFromAddress}
                  onChangeText={setMailFromAddress}
                  placeholder="no-reply@hrms.in"
                  autoCapitalize="none"
                  keyboardType="email-address"
                />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.inputLabel}>FROM DISPLAY NAME *</Text>
                <TextInput
                  style={styles.input}
                  value={mailFromName}
                  onChangeText={setMailFromName}
                  placeholder="Enterprise HRMS India"
                />
              </View>

              <TouchableOpacity
                style={[styles.saveBtn, savingSmtp && styles.saveBtnDisabled]}
                onPress={handleSaveSmtp}
                disabled={savingSmtp}
              >
                {savingSmtp ? (
                  <ActivityIndicator color={colors.surface} />
                ) : (
                  <>
                    <Ionicons name="save-outline" size={16} color={colors.surface} />
                    <Text style={styles.saveBtnText}>Save SMTP Configuration</Text>
                  </>
                )}
              </TouchableOpacity>
            </View>
          )}
        </View>
      )}

      {/* Modal: Create Shift */}
      <Modal
        visible={shiftModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setShiftModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Add Work Shift</Text>
              <TouchableOpacity onPress={() => setShiftModalVisible(false)}>
                <Ionicons name="close" size={20} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>SHIFT NAME *</Text>
              <TextInput
                style={styles.input}
                value={newShiftName}
                onChangeText={setNewShiftName}
                placeholder="e.g. Afternoon Support Shift"
              />
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>SHIFT CODE *</Text>
              <TextInput
                style={styles.input}
                value={newShiftCode}
                onChangeText={setNewShiftCode}
                placeholder="e.g. AFT-01"
                autoCapitalize="characters"
              />
            </View>

            <View style={styles.twoColRow}>
              <View style={[styles.inputGroup, { flex: 1 }]}>
                <Text style={styles.inputLabel}>START (HH:MM) *</Text>
                <TextInput
                  style={styles.input}
                  value={newShiftStart}
                  onChangeText={setNewShiftStart}
                  placeholder="09:30"
                />
              </View>
              <View style={[styles.inputGroup, { flex: 1 }]}>
                <Text style={styles.inputLabel}>END (HH:MM) *</Text>
                <TextInput
                  style={styles.input}
                  value={newShiftEnd}
                  onChangeText={setNewShiftEnd}
                  placeholder="18:30"
                />
              </View>
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>GRACE PERIOD (MINUTES)</Text>
              <TextInput
                style={styles.input}
                value={newShiftGrace}
                onChangeText={setNewShiftGrace}
                placeholder="15"
                keyboardType="numeric"
              />
            </View>

            <TouchableOpacity
              style={styles.checkboxRow}
              onPress={() => setNewShiftNight(!newShiftNight)}
            >
              <Ionicons
                name={newShiftNight ? 'checkbox' : 'square-outline'}
                size={20}
                color={newShiftNight ? colors.primary : colors.textMuted}
              />
              <Text style={styles.checkboxLabel}>Overnight shift (crosses midnight)</Text>
            </TouchableOpacity>

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={styles.cancelBtn}
                onPress={() => setShiftModalVisible(false)}
              >
                <Text style={styles.cancelBtnText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={styles.confirmBtn}
                onPress={handleCreateShift}
                disabled={savingShift}
              >
                {savingShift ? (
                  <ActivityIndicator color={colors.surface} />
                ) : (
                  <Text style={styles.confirmBtnText}>Create Shift</Text>
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
    paddingBottom: 50,
  },
  breadcrumbRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  backBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingVertical: 6,
    paddingHorizontal: 10,
    backgroundColor: colors.surface,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
  },
  backBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  breadcrumbCurrent: {
    fontSize: 11,
    color: colors.textMuted,
    fontWeight: '600',
  },
  tabContainer: {
    flexDirection: 'row',
    backgroundColor: colors.surface,
    padding: 4,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 16,
  },
  tabButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    borderRadius: 10,
    gap: 6,
  },
  tabButtonActive: {
    backgroundColor: colors.primaryLight,
  },
  tabButtonText: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  tabButtonTextActive: {
    color: colors.primary,
  },
  tabContent: {
    gap: 14,
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  sectionSubtitle: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 2,
  },
  addBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: colors.primary,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 10,
  },
  addBtnText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.surface,
  },
  shiftsList: {
    gap: 12,
  },
  shiftCard: {
    backgroundColor: colors.surface,
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
  },
  shiftCardTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  shiftCodeBadge: {
    backgroundColor: colors.surfaceSubtle,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  shiftCodeText: {
    fontSize: 11,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  nightBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#FAF5FF',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  nightBadgeText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#7E22CE',
  },
  dayBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#FFFBEB',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  dayBadgeText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#D97706',
  },
  shiftName: {
    fontSize: 15,
    fontWeight: '800',
    color: colors.textPrimary,
    marginTop: 2,
  },
  shiftTime: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.primary,
    marginTop: 4,
  },
  shiftMetaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 8,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: colors.borderSubtle,
  },
  shiftMetaText: {
    fontSize: 11,
    color: colors.textSecondary,
    fontWeight: '500',
  },
  gatewayActiveBadge: {
    backgroundColor: colors.primaryLight,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
  },
  gatewayActiveText: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.primary,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 22,
    padding: 18,
    borderWidth: 1,
    borderColor: colors.border,
  },
  inputGroup: {
    marginBottom: 12,
  },
  inputLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.textSecondary,
    letterSpacing: 0.5,
    marginBottom: 6,
  },
  input: {
    backgroundColor: colors.surfaceSubtle,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 12,
    paddingHorizontal: 12,
    height: 44,
    fontSize: 13,
    color: colors.textPrimary,
    fontWeight: '500',
  },
  twoColRow: {
    flexDirection: 'row',
    gap: 10,
  },
  saveBtn: {
    backgroundColor: colors.primary,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    height: 46,
    borderRadius: 12,
    gap: 8,
    marginTop: 6,
  },
  saveBtnDisabled: {
    opacity: 0.7,
  },
  saveBtnText: {
    fontSize: 13,
    fontWeight: '800',
    color: colors.surface,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.4)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalCard: {
    width: '100%',
    backgroundColor: colors.surface,
    borderRadius: 22,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.border,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 14,
  },
  modalTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  checkboxRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 16,
    marginTop: 4,
  },
  checkboxLabel: {
    fontSize: 12,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  modalActions: {
    flexDirection: 'row',
    justifyContent: 'flex-end',
    gap: 10,
  },
  cancelBtn: {
    paddingVertical: 10,
    paddingHorizontal: 14,
    borderRadius: 10,
  },
  cancelBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  confirmBtn: {
    paddingVertical: 10,
    paddingHorizontal: 18,
    borderRadius: 10,
    backgroundColor: colors.primary,
  },
  confirmBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.surface,
  },
});
