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
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';
import * as FileSystem from 'expo-file-system';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';
import { getApiBaseUrl } from '../config/api';
import { generatePayslipHtml } from '../utils/payslipPdfGenerator';

export const PayslipsScreen = ({ onNavigate }) => {
  const { token } = useAuth();
  const [payslips, setPayslips] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [downloadingId, setDownloadingId] = useState(null);

  const fetchPayslips = useCallback(async () => {
    try {
      const baseUrl = await getApiBaseUrl();
      const res = await fetch(`${baseUrl}/mobile/payslips`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });

      if (res.ok) {
        const json = await res.json();
        setPayslips(json.payslips || []);
      }
    } catch (e) {
      console.log('Error fetching payslips', e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useEffect(() => {
    fetchPayslips();
  }, [fetchPayslips]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchPayslips();
  };

  const handleDownloadPayslip = async (slip) => {
    try {
      setDownloadingId(slip.id);
      const baseUrl = await getApiBaseUrl();

      // Fetch comprehensive payslip breakdown
      const res = await fetch(`${baseUrl}/mobile/payslips/${slip.id}`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });

      let fullSlip = slip;
      if (res.ok) {
        const json = await res.json();
        if (json.payslip) fullSlip = json.payslip;
      }

      // Generate HTML string
      const html = generatePayslipHtml(fullSlip);

      // Render into PDF file on disk
      const { uri } = await Print.printToFileAsync({
        html,
        base64: false,
      });

      // Move / Save with descriptive name
      const fileName = `Payslip_${fullSlip.payslip_number || slip.id}_${fullSlip.month}_${fullSlip.year}.pdf`;
      const targetUri = `${FileSystem.documentDirectory}${fileName}`;
      await FileSystem.copyAsync({ from: uri, to: targetUri });

      // Check sharing / saving capability
      const isSharingAvailable = await Sharing.isAvailableAsync();
      if (isSharingAvailable) {
        await Sharing.shareAsync(targetUri, {
          mimeType: 'application/pdf',
          dialogTitle: `Download ${fileName}`,
          UTI: 'com.adobe.pdf',
        });
      } else {
        Alert.alert('Payslip Downloaded', `Saved to application storage: ${fileName}`);
      }
    } catch (err) {
      console.log('Error generating/downloading payslip PDF:', err);
      Alert.alert('Download Error', 'Could not generate payslip PDF. Please try again.');
    } finally {
      setDownloadingId(null);
    }
  };

  const handlePrintPayslip = async (slip) => {
    try {
      const baseUrl = await getApiBaseUrl();
      const res = await fetch(`${baseUrl}/mobile/payslips/${slip.id}`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });

      let fullSlip = slip;
      if (res.ok) {
        const json = await res.json();
        if (json.payslip) fullSlip = json.payslip;
      }

      const html = generatePayslipHtml(fullSlip);
      await Print.printAsync({ html });
    } catch (err) {
      Alert.alert('Print Error', 'Could not open print preview.');
    }
  };

  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  return (
    <View style={styles.container}>
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => onNavigate('dashboard')}>
          <Ionicons name="arrow-back" size={20} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.topBarTitle}>My Payslips</Text>
      </View>

      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={payslips}
          keyExtractor={(item) => item.id.toString()}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />}
          contentContainerStyle={styles.listContent}
          renderItem={({ item }) => {
            const isProcessing = downloadingId === item.id;
            return (
              <View style={styles.slipCard}>
                <View style={styles.cardHeader}>
                  <View>
                    <Text style={styles.monthYear}>
                      {monthNames[item.month - 1] || 'Month'} {item.year}
                    </Text>
                    <Text style={styles.slipNo}>{item.payslip_number}</Text>
                  </View>
                  <View style={styles.generatedBadge}>
                    <Text style={styles.generatedBadgeText}>GENERATED</Text>
                  </View>
                </View>

                <View style={styles.divider} />

                <View style={styles.statsRow}>
                  <View style={styles.statCol}>
                    <Text style={styles.statLabel}>GROSS</Text>
                    <Text style={styles.statVal}>₹{parseFloat(item.gross_salary || 0).toLocaleString('en-IN')}</Text>
                  </View>
                  <View style={styles.statCol}>
                    <Text style={styles.statLabel}>LEAVE DED.</Text>
                    <Text style={[styles.statVal, { color: '#ef4444' }]}>
                      -₹{parseFloat(item.leave_deduction_amount || 0).toLocaleString('en-IN')}
                    </Text>
                  </View>
                  <View style={styles.statCol}>
                    <Text style={styles.statLabel}>NET PAY</Text>
                    <Text style={[styles.statVal, { color: colors.success, fontWeight: '800' }]}>
                      ₹{parseFloat(item.net_salary || 0).toLocaleString('en-IN')}
                    </Text>
                  </View>
                </View>

                {item.total_deductible_days > 0 && (
                  <Text style={styles.deductibleNote}>
                    * Includes deduction for {item.total_deductible_days} days of leave/half days.
                  </Text>
                )}

                {/* Action Download & Print Row */}
                <View style={styles.actionRow}>
                  <TouchableOpacity
                    style={styles.printBtn}
                    onPress={() => handlePrintPayslip(item)}
                  >
                    <Ionicons name="print-outline" size={16} color={colors.textSecondary} />
                    <Text style={styles.printBtnText}>Preview</Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={[styles.downloadBtn, isProcessing && { opacity: 0.7 }]}
                    disabled={isProcessing}
                    onPress={() => handleDownloadPayslip(item)}
                  >
                    {isProcessing ? (
                      <ActivityIndicator size="small" color="#fff" />
                    ) : (
                      <>
                        <Ionicons name="download-outline" size={16} color="#fff" />
                        <Text style={styles.downloadBtnText}>Download PDF</Text>
                      </>
                    )}
                  </TouchableOpacity>
                </View>
              </View>
            );
          }}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Ionicons name="receipt-outline" size={44} color={colors.textMuted} />
              <Text style={styles.emptyText}>No salary payslips generated yet.</Text>
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
  slipCard: {
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
  monthYear: {
    fontSize: 15,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  slipNo: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 1,
    fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
  },
  generatedBadge: {
    backgroundColor: colors.successLight,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  generatedBadgeText: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.success,
  },
  divider: {
    height: 1,
    backgroundColor: colors.borderLight,
    marginVertical: 12,
  },
  statsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  statCol: {
    alignItems: 'center',
    flex: 1,
  },
  statLabel: {
    fontSize: 9,
    fontWeight: '800',
    color: colors.textMuted,
    marginBottom: 2,
  },
  statVal: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  deductibleNote: {
    fontSize: 10,
    color: colors.textMuted,
    marginTop: 10,
    fontStyle: 'italic',
  },
  actionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-end',
    gap: 10,
    marginTop: 14,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: colors.borderLight,
  },
  printBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 10,
    backgroundColor: '#f1f5f9',
    borderWidth: 1,
    borderColor: colors.border,
  },
  printBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  downloadBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 10,
    backgroundColor: colors.primary,
  },
  downloadBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#fff',
  },
});
