import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';
import { getApiBaseUrl } from '../config/api';

export const AttendanceScreen = ({ onNavigate }) => {
  const { token, user } = useAuth();
  const isAdmin = user?.role === 'superadmin' || user?.role === 'admin';
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchAttendance = useCallback(async () => {
    try {
      const baseUrl = await getApiBaseUrl();
      const res = await fetch(`${baseUrl}/mobile/attendance/history`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });

      if (res.ok) {
        const json = await res.json();
        setLogs(json.attendances || []);
      }
    } catch (e) {
      console.log('Attendance history fetch error', e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useEffect(() => {
    fetchAttendance();
  }, [fetchAttendance]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchAttendance();
  };

  const renderItem = ({ item }) => {
    const isHalfDay = item.status === 'half_day';
    const isPresent = item.status === 'present';

    return (
      <View style={styles.logCard}>
        <View style={styles.logTopRow}>
          <View>
            <Text style={styles.dateText}>
              {new Date(item.attendance_date).toLocaleDateString('en-IN', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
              })}
            </Text>
            {isAdmin && item.employee && (
              <Text style={styles.empText}>
                {item.employee.full_name} ({item.employee.employee_id})
              </Text>
            )}
          </View>
          <View
            style={[
              styles.statusBadge,
              isHalfDay ? styles.badgeHalfDay : isPresent ? styles.badgePresent : styles.badgeDefault,
            ]}
          >
            <Text
              style={[
                styles.statusBadgeText,
                isHalfDay ? styles.textHalfDay : isPresent ? styles.textPresent : styles.textDefault,
              ]}
            >
              {item.status ? item.status.replace('_', ' ').toUpperCase() : 'PRESENT'}
            </Text>
          </View>
        </View>

        <View style={styles.timesRow}>
          <View style={styles.timeBox}>
            <Text style={styles.timeLabel}>PUNCH IN</Text>
            <Text style={styles.timeVal}>
              {item.punch_in_at
                ? new Date(item.punch_in_at).toLocaleTimeString('en-IN', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true,
                  })
                : '—'}
            </Text>
          </View>

          <View style={styles.timeBox}>
            <Text style={styles.timeLabel}>PUNCH OUT</Text>
            <Text style={styles.timeVal}>
              {item.punch_out_at
                ? new Date(item.punch_out_at).toLocaleTimeString('en-IN', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true,
                  })
                : '—'}
            </Text>
          </View>

          <View style={styles.timeBox}>
            <Text style={styles.timeLabel}>HOURS</Text>
            <Text style={styles.timeVal}>{item.working_hours ? `${item.working_hours}h` : '—'}</Text>
          </View>
        </View>

        {item.used_grace_period && (
          <View style={styles.graceAlert}>
            <Ionicons name="shield-outline" size={11} color={colors.warning} />
            <Text style={styles.graceAlertText}>Grace period applied (within 5-day monthly quota)</Text>
          </View>
        )}
      </View>
    );
  };

  return (
    <View style={styles.container}>
      {/* Header bar */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => onNavigate('dashboard')}>
          <Ionicons name="arrow-back" size={20} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.topBarTitle}>{isAdmin ? 'All Attendance Logs' : 'My Attendance History'}</Text>
      </View>

      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={logs}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Ionicons name="calendar-outline" size={44} color={colors.textMuted} />
              <Text style={styles.emptyText}>No attendance records found.</Text>
            </View>
          }
        />
      )}
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
    gap: 12,
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
  logCard: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 10,
  },
  logTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  dateText: {
    fontSize: 13,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  empText: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 2,
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  badgePresent: {
    backgroundColor: colors.successLight,
  },
  badgeHalfDay: {
    backgroundColor: '#fee2e2',
  },
  badgeDefault: {
    backgroundColor: colors.surfaceSubtle,
  },
  statusBadgeText: {
    fontSize: 10,
    fontWeight: '800',
  },
  textPresent: {
    color: colors.success,
  },
  textHalfDay: {
    color: '#ef4444',
  },
  textDefault: {
    color: colors.textSecondary,
  },
  timesRow: {
    flexDirection: 'row',
    backgroundColor: colors.surfaceSubtle,
    borderRadius: 10,
    padding: 10,
    justifyContent: 'space-between',
  },
  timeBox: {
    alignItems: 'center',
    flex: 1,
  },
  timeLabel: {
    fontSize: 9,
    fontWeight: '800',
    color: colors.textMuted,
    marginBottom: 2,
  },
  timeVal: {
    fontSize: 12,
    fontWeight: '800',
    color: colors.textPrimary,
    fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
  },
  graceAlert: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 8,
  },
  graceAlertText: {
    fontSize: 10,
    color: colors.warning,
    fontWeight: '600',
  },
});
