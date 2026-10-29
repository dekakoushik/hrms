import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  RefreshControl,
  Linking,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';
import { getApiBaseUrl } from '../config/api';

export const ContactDirectoryScreen = ({ onNavigate }) => {
  const { token } = useAuth();
  const [contacts, setContacts] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [selectedDeptId, setSelectedDeptId] = useState(null);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchContacts = useCallback(async () => {
    try {
      const baseUrl = await getApiBaseUrl();
      let url = `${baseUrl}/mobile/contacts?search=${encodeURIComponent(search)}`;
      if (selectedDeptId) {
        url += `&department_id=${selectedDeptId}`;
      }

      const res = await fetch(url, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });

      if (res.ok) {
        const json = await res.json();
        setContacts(json.contacts || []);
        if (json.departments && departments.length === 0) {
          setDepartments(json.departments || []);
        }
      }
    } catch (e) {
      console.log('Error fetching contacts', e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, search, selectedDeptId, departments.length]);

  useEffect(() => {
    fetchContacts();
  }, [fetchContacts]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchContacts();
  };

  const handleCall = (phoneNumber) => {
    if (!phoneNumber) return;
    Linking.openURL(`tel:${phoneNumber}`);
  };

  const handleEmail = (email) => {
    if (!email) return;
    Linking.openURL(`mailto:${email}`);
  };

  return (
    <View style={styles.container}>
      {/* Top Navigation Bar */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => onNavigate('dashboard')}>
          <Ionicons name="arrow-back" size={20} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.topBarTitle}>Contact Directory</Text>
      </View>

      {/* Search Input */}
      <View style={styles.searchWrapper}>
        <View style={styles.searchBox}>
          <Ionicons name="search" size={16} color={colors.textMuted} />
          <TextInput
            style={styles.searchInput}
            placeholder="Search by name, ID, phone..."
            placeholderTextColor={colors.textMuted}
            value={search}
            onChangeText={setSearch}
          />
          {search.length > 0 && (
            <TouchableOpacity onPress={() => setSearch('')}>
              <Ionicons name="close-circle" size={16} color={colors.textMuted} />
            </TouchableOpacity>
          )}
        </View>
      </View>

      {/* Department Filter Pills */}
      <View style={styles.deptFilterRow}>
        <FlatList
          horizontal
          showsHorizontalScrollIndicator={false}
          data={[{ id: null, name: 'All Teams' }, ...departments]}
          keyExtractor={(item) => (item.id ? item.id.toString() : 'all')}
          contentContainerStyle={styles.deptPillsContainer}
          renderItem={({ item }) => {
            const isSelected = selectedDeptId === item.id;
            return (
              <TouchableOpacity
                style={[styles.deptPill, isSelected && styles.deptPillActive]}
                onPress={() => setSelectedDeptId(item.id)}
              >
                <Text style={[styles.deptPillText, isSelected && styles.deptPillTextActive]}>
                  {item.name}
                </Text>
              </TouchableOpacity>
            );
          }}
        />
      </View>

      {/* Contacts List */}
      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={contacts}
          keyExtractor={(item) => item.id.toString()}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />}
          contentContainerStyle={styles.listContent}
          renderItem={({ item }) => (
            <View style={styles.contactCard}>
              <View style={styles.cardHeader}>
                <View style={styles.avatar}>
                  <Text style={styles.avatarText}>
                    {item.full_name ? item.full_name.charAt(0).toUpperCase() : 'U'}
                  </Text>
                </View>
                <View style={styles.headerInfo}>
                  <Text style={styles.empName}>{item.full_name}</Text>
                  <Text style={styles.empRole}>
                    {item.designation?.name || 'Staff'} • {item.department?.name || 'General'}
                  </Text>
                  <Text style={styles.empId}>ID: {item.employee_id}</Text>
                </View>
              </View>

              <View style={styles.divider} />

              <View style={styles.contactActions}>
                {item.phone ? (
                  <TouchableOpacity
                    style={styles.actionBtnCall}
                    onPress={() => handleCall(item.phone)}
                  >
                    <Ionicons name="call" size={14} color="#0284c7" />
                    <Text style={styles.actionBtnCallText}>{item.phone}</Text>
                  </TouchableOpacity>
                ) : null}

                {item.work_email ? (
                  <TouchableOpacity
                    style={styles.actionBtnMail}
                    onPress={() => handleEmail(item.work_email)}
                  >
                    <Ionicons name="mail" size={14} color="#059669" />
                    <Text style={styles.actionBtnMailText}>Email</Text>
                  </TouchableOpacity>
                ) : null}
              </View>
            </View>
          )}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Ionicons name="people-outline" size={44} color={colors.textMuted} />
              <Text style={styles.emptyText}>No workplace contacts match your filter.</Text>
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
  searchWrapper: {
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 8,
    backgroundColor: '#fff',
  },
  searchBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: '#f1f5f9',
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: Platform.OS === 'ios' ? 10 : 6,
  },
  searchInput: {
    flex: 1,
    fontSize: 13,
    color: colors.textPrimary,
  },
  deptFilterRow: {
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: colors.borderLight,
    paddingBottom: 10,
  },
  deptPillsContainer: {
    paddingHorizontal: 16,
    gap: 8,
  },
  deptPill: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: '#f1f5f9',
    borderWidth: 1,
    borderColor: colors.border,
  },
  deptPillActive: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  deptPillText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  deptPillTextActive: {
    color: '#fff',
  },
  centerContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  listContent: {
    padding: 16,
    paddingBottom: 40,
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
  contactCard: {
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
    gap: 12,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#e0f2fe',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0284c7',
  },
  headerInfo: {
    flex: 1,
  },
  empName: {
    fontSize: 15,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  empRole: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textSecondary,
    marginTop: 2,
  },
  empId: {
    fontSize: 10,
    fontWeight: '700',
    color: colors.textMuted,
    fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
    marginTop: 2,
  },
  divider: {
    height: 1,
    backgroundColor: colors.borderLight,
    marginVertical: 12,
  },
  contactActions: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-start',
    gap: 10,
  },
  actionBtnCall: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: '#f0f9ff',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#bae6fd',
  },
  actionBtnCallText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#0284c7',
  },
  actionBtnMail: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: '#ecfdf5',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#a7f3d0',
  },
  actionBtnMailText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#059669',
  },
});
