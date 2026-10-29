import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  Modal,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';

export const EmployeeDirectoryScreen = ({ onNavigate }) => {
  const { token, apiUrl } = useAuth();
  const [employees, setEmployees] = useState([]);
  const [companies, setCompanies] = useState([]);
  const [selectedCompanyId, setSelectedCompanyId] = useState('all');
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [selectedEmp, setSelectedEmp] = useState(null);
  const [detailModalVisible, setDetailModalVisible] = useState(false);

  useEffect(() => {
    fetchMetadata();
    fetchEmployees();
  }, []);

  const fetchMetadata = async () => {
    try {
      const res = await fetch(`${apiUrl}/employees/metadata`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
      });
      const data = await res.json();
      if (data.companies) {
        setCompanies(data.companies);
      }
    } catch {
      // offline fallback
    }
  };

  const fetchEmployees = async (query = '', compId = selectedCompanyId) => {
    try {
      setLoading(true);
      let url = `${apiUrl}/employees?`;
      const params = [];
      if (query) params.push(`search=${encodeURIComponent(query)}`);
      if (compId && compId !== 'all') params.push(`company_id=${compId}`);
      url += params.join('&');

      const res = await fetch(url, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });
      const data = await res.json();
      if (data.employees) {
        setEmployees(data.employees);
      }
    } catch {
      // offline fallback
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (text) => {
    setSearch(text);
    fetchEmployees(text, selectedCompanyId);
  };

  const handleCompanySelect = (compId) => {
    setSelectedCompanyId(compId);
    fetchEmployees(search, compId);
  };

  const openDetails = (emp) => {
    setSelectedEmp(emp);
    setDetailModalVisible(true);
  };

  return (
    <View style={styles.container}>
      {/* Top Breadcrumb Bar */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => onNavigate('dashboard')}>
          <Ionicons name="arrow-back" size={16} color={colors.textSecondary} />
          <Text style={styles.backBtnText}>Dashboard</Text>
        </TouchableOpacity>
        <Text style={styles.topBarTitle}>Employee Directory ({employees.length})</Text>
      </View>

      {/* Search Input */}
      <View style={styles.searchWrapper}>
        <Ionicons name="search-outline" size={16} color={colors.textMuted} style={styles.searchIcon} />
        <TextInput
          style={styles.searchInput}
          value={search}
          onChangeText={handleSearch}
          placeholder="Search by name, ID, or phone..."
          placeholderTextColor={colors.textMuted}
        />
        {search.length > 0 && (
          <TouchableOpacity onPress={() => handleSearch('')}>
            <Ionicons name="close-circle" size={16} color={colors.textMuted} />
          </TouchableOpacity>
        )}
      </View>

      {/* Company Filter Tabs */}
      <View style={styles.companyTabsWrapper}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.companyTabsContent}>
          <TouchableOpacity
            style={[styles.compTab, selectedCompanyId === 'all' && styles.compTabActive]}
            onPress={() => handleCompanySelect('all')}
          >
            <Text style={[styles.compTabText, selectedCompanyId === 'all' && styles.compTabTextActive]}>
              All Companies
            </Text>
          </TouchableOpacity>
          {companies.map((c) => (
            <TouchableOpacity
              key={c.id}
              style={[styles.compTab, selectedCompanyId === c.id && styles.compTabActive]}
              onPress={() => handleCompanySelect(c.id)}
            >
              <Text style={[styles.compTabText, selectedCompanyId === c.id && styles.compTabTextActive]}>
                {c.code || c.name}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      </View>

      {/* Employee List */}
      {loading ? (
        <ActivityIndicator style={{ marginTop: 40 }} color={colors.primary} />
      ) : (
        <ScrollView contentContainerStyle={styles.listContent}>
          {employees.map((emp) => (
            <TouchableOpacity
              key={emp.id}
              style={styles.empCard}
              onPress={() => openDetails(emp)}
              activeOpacity={0.8}
            >
              <View style={styles.empCardRow}>
                <View style={styles.avatarCircle}>
                  <Text style={styles.avatarText}>{emp.full_name?.substring(0, 2)?.toUpperCase()}</Text>
                </View>
                <View style={styles.empInfo}>
                  <View style={styles.empNameRow}>
                    <Text style={styles.empName}>{emp.full_name}</Text>
                    <View style={styles.idBadge}>
                      <Text style={styles.idBadgeText}>{emp.employee_id}</Text>
                    </View>
                  </View>

                  <Text style={styles.empDesig}>
                    {emp.designation?.name || 'Staff'} • {emp.department?.name || 'General'}
                  </Text>

                  {emp.company && (
                    <Text style={styles.companyPill}>
                      🏢 {emp.company.name}
                    </Text>
                  )}

                  <View style={styles.metaRow}>
                    <Text style={styles.metaPhone}>+91 {emp.contact_number}</Text>
                    <Text style={styles.metaSalary}>Take-home: ₹{parseFloat(emp.take_home_salary || 0).toLocaleString('en-IN')}</Text>
                  </View>
                </View>
                <Ionicons name="chevron-forward" size={18} color={colors.textMuted} />
              </View>
            </TouchableOpacity>
          ))}

          {employees.length === 0 && (
            <View style={styles.emptyState}>
              <Ionicons name="people-outline" size={40} color={colors.textMuted} />
              <Text style={styles.emptyTitle}>No Employees Found</Text>
              <Text style={styles.emptyDesc}>Use the Web Portal to onboard employees with full statutory compliance.</Text>
            </View>
          )}
        </ScrollView>
      )}

      {/* Detail Modal */}
      <Modal
        visible={detailModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setDetailModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <View>
                <Text style={styles.modalName}>{selectedEmp?.full_name}</Text>
                <Text style={styles.modalId}>{selectedEmp?.employee_id} • {selectedEmp?.user_type?.toUpperCase()}</Text>
              </View>
              <TouchableOpacity onPress={() => setDetailModalVisible(false)}>
                <Ionicons name="close" size={20} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>

            <ScrollView style={{ maxHeight: 400 }} showsVerticalScrollIndicator={false}>
              <View style={styles.detailSection}>
                <Text style={styles.detailSectionTitle}>ORGANIZATION</Text>
                {selectedEmp?.company && (
                  <Text style={styles.detailRow}>Company: <Text style={styles.detailVal}>{selectedEmp.company.name}</Text></Text>
                )}
                <Text style={styles.detailRow}>Dept: <Text style={styles.detailVal}>{selectedEmp?.department?.name || 'N/A'}</Text></Text>
                <Text style={styles.detailRow}>Role: <Text style={styles.detailVal}>{selectedEmp?.designation?.name || 'N/A'}</Text></Text>
                <Text style={styles.detailRow}>Manager: <Text style={styles.detailVal}>{selectedEmp?.manager?.full_name || 'Self/Head'}</Text></Text>
              </View>

              <View style={styles.detailSection}>
                <Text style={styles.detailSectionTitle}>CONTACT & STATUTORY</Text>
                <Text style={styles.detailRow}>Mobile: <Text style={styles.detailVal}>+91 {selectedEmp?.contact_number}</Text></Text>
                <Text style={styles.detailRow}>Email: <Text style={styles.detailVal}>{selectedEmp?.user?.email || selectedEmp?.personal_email}</Text></Text>
                <Text style={styles.detailRow}>PAN: <Text style={styles.detailVal}>{selectedEmp?.pan_number || 'N/A'}</Text></Text>
                <Text style={styles.detailRow}>Aadhaar: <Text style={styles.detailVal}>{selectedEmp?.aadhar_number || 'N/A'}</Text></Text>
              </View>

              <View style={styles.detailSection}>
                <Text style={styles.detailSectionTitle}>MONTHLY COMPENSATION (INR)</Text>
                <View style={styles.salaryGrid}>
                  <Text style={styles.salaryCell}>Basic: ₹{parseFloat(selectedEmp?.basic_salary || 0).toLocaleString('en-IN')}</Text>
                  <Text style={styles.salaryCell}>Gross: ₹{parseFloat(selectedEmp?.gross_salary || 0).toLocaleString('en-IN')}</Text>
                  <Text style={styles.salaryCell}>PF: ₹{parseFloat(selectedEmp?.pf_amount || 0).toLocaleString('en-IN')}</Text>
                  <Text style={styles.salaryCell}>CTC: ₹{parseFloat(selectedEmp?.ctc_amount || 0).toLocaleString('en-IN')}</Text>
                </View>
                <View style={styles.takeHomeBox}>
                  <Text style={styles.takeHomeText}>Net Take-Home: ₹{parseFloat(selectedEmp?.take_home_salary || 0).toLocaleString('en-IN')}/mo</Text>
                </View>
              </View>
            </ScrollView>

            <TouchableOpacity style={styles.closeBtn} onPress={() => setDetailModalVisible(false)}>
              <Text style={styles.closeBtnText}>Close Profile</Text>
            </TouchableOpacity>
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
    padding: 16,
    paddingBottom: 10,
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
  topBarTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  searchWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    marginHorizontal: 16,
    marginBottom: 12,
    paddingHorizontal: 12,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    height: 42,
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 13,
    color: colors.textPrimary,
    fontWeight: '500',
  },
  listContent: {
    paddingHorizontal: 16,
    paddingBottom: 40,
    gap: 10,
  },
  empCard: {
    backgroundColor: colors.surface,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.border,
  },
  empCardRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  avatarCircle: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    color: colors.surface,
    fontWeight: '800',
    fontSize: 14,
  },
  empInfo: {
    flex: 1,
  },
  empNameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  empName: {
    fontSize: 14,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  idBadge: {
    backgroundColor: colors.primaryLight,
    paddingHorizontal: 6,
    paddingVertical: 1.5,
    borderRadius: 4,
  },
  idBadgeText: {
    fontSize: 9,
    fontWeight: '800',
    color: colors.primary,
  },
  empDesig: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 2,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 6,
    paddingTop: 6,
    borderTopWidth: 1,
    borderTopColor: colors.borderSubtle,
  },
  metaPhone: {
    fontSize: 10,
    color: colors.textMuted,
  },
  metaSalary: {
    fontSize: 10,
    fontWeight: '700',
    color: colors.success,
  },
  emptyState: {
    alignItems: 'center',
    paddingVertical: 60,
    gap: 10,
  },
  emptyTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  emptyDesc: {
    fontSize: 12,
    color: colors.textMuted,
    textAlign: 'center',
    paddingHorizontal: 30,
    lineHeight: 18,
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
    borderRadius: 24,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.border,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    paddingBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: colors.borderSubtle,
    marginBottom: 12,
  },
  modalName: {
    fontSize: 17,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  modalId: {
    fontSize: 11,
    fontFamily: 'monospace',
    color: colors.primary,
    marginTop: 2,
  },
  detailSection: {
    marginBottom: 14,
  },
  detailSectionTitle: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.textMuted,
    letterSpacing: 0.5,
    marginBottom: 4,
  },
  detailRow: {
    fontSize: 12,
    color: colors.textSecondary,
    marginBottom: 2,
  },
  detailVal: {
    fontWeight: '700',
    color: colors.textPrimary,
  },
  salaryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 4,
  },
  salaryCell: {
    fontSize: 11,
    fontWeight: '600',
    color: colors.textSecondary,
    backgroundColor: colors.surfaceSubtle,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
  },
  takeHomeBox: {
    backgroundColor: colors.successLight,
    padding: 10,
    borderRadius: 10,
    marginTop: 8,
    alignItems: 'center',
  },
  takeHomeText: {
    fontSize: 12,
    fontWeight: '800',
    color: colors.success,
  },
  closeBtn: {
    backgroundColor: colors.textPrimary,
    paddingVertical: 12,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 14,
  },
  closeBtnText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
  },
  companyTabsWrapper: {
    paddingHorizontal: 16,
    marginBottom: 8,
  },
  companyTabsContent: {
    gap: 8,
    paddingRight: 16,
  },
  compTab: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 10,
    backgroundColor: colors.surfaceSubtle,
    borderWidth: 1,
    borderColor: colors.borderSubtle,
  },
  compTabActive: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  compTabText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  compTabTextActive: {
    color: colors.surface,
  },
  companyPill: {
    fontSize: 10,
    fontWeight: '700',
    color: colors.primary,
    backgroundColor: colors.primaryLight,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
    alignSelf: 'flex-start',
    marginTop: 4,
  },
});
