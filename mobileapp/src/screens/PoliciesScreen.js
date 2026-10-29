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
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as FileSystem from 'expo-file-system';
import * as Sharing from 'expo-sharing';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';
import { getApiBaseUrl } from '../config/api';

export const PoliciesScreen = ({ onNavigate }) => {
  const { token } = useAuth();
  const [folders, setFolders] = useState([]);
  const [documents, setDocuments] = useState([]);
  const [selectedFolderId, setSelectedFolderId] = useState(null);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [downloadingId, setDownloadingId] = useState(null);

  const fetchPolicies = useCallback(async () => {
    try {
      const baseUrl = await getApiBaseUrl();
      let url = `${baseUrl}/mobile/policies?search=${encodeURIComponent(search)}`;
      if (selectedFolderId) {
        url += `&folder_id=${selectedFolderId}`;
      }

      const res = await fetch(url, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });

      if (res.ok) {
        const json = await res.json();
        setFolders(json.folders || []);
        setDocuments(json.documents || []);
      }
    } catch (e) {
      console.log('Error fetching policies', e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, search, selectedFolderId]);

  useEffect(() => {
    fetchPolicies();
  }, [fetchPolicies]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchPolicies();
  };

  const handleDownloadDoc = async (doc) => {
    try {
      setDownloadingId(doc.id);
      const baseUrl = await getApiBaseUrl();
      // Server storage download url
      const downloadUrl = `${baseUrl.replace('/api', '')}/policies/documents/${doc.id}/download`;

      const targetPath = `${FileSystem.documentDirectory}${doc.file_name}`;

      const downloadRes = await FileSystem.downloadAsync(downloadUrl, targetPath, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (downloadRes.status === 200) {
        const isSharingAvailable = await Sharing.isAvailableAsync();
        if (isSharingAvailable) {
          await Sharing.shareAsync(downloadRes.uri, {
            dialogTitle: `Download ${doc.file_name}`,
          });
        } else {
          Alert.alert('Downloaded', `File saved to ${doc.file_name}`);
        }
      } else {
        Alert.alert('Download Error', 'Could not complete download from server.');
      }
    } catch (err) {
      console.log('Error downloading policy document:', err);
      Alert.alert('Error', 'An error occurred while downloading the document.');
    } finally {
      setDownloadingId(null);
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => onNavigate('dashboard')}>
          <Ionicons name="arrow-back" size={20} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.topBarTitle}>HR & Workplace Policies</Text>
      </View>

      <View style={styles.searchWrapper}>
        <View style={styles.searchBox}>
          <Ionicons name="search" size={16} color={colors.textMuted} />
          <TextInput
            style={styles.searchInput}
            placeholder="Search policies, compliance..."
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

      <View style={styles.folderRow}>
        <FlatList
          horizontal
          showsHorizontalScrollIndicator={false}
          data={[{ id: null, name: 'All Folders' }, ...folders]}
          keyExtractor={(item) => (item.id ? item.id.toString() : 'all')}
          contentContainerStyle={styles.folderPillsContainer}
          renderItem={({ item }) => {
            const isSelected = selectedFolderId === item.id;
            return (
              <TouchableOpacity
                style={[styles.folderPill, isSelected && styles.folderPillActive]}
                onPress={() => setSelectedFolderId(item.id)}
              >
                <Ionicons
                  name={isSelected ? 'folder-open' : 'folder'}
                  size={14}
                  color={isSelected ? '#fff' : colors.primary}
                />
                <Text style={[styles.folderPillText, isSelected && styles.folderPillTextActive]}>
                  {item.name}
                </Text>
              </TouchableOpacity>
            );
          }}
        />
      </View>

      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={documents}
          keyExtractor={(item) => item.id.toString()}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />}
          contentContainerStyle={styles.listContent}
          renderItem={({ item }) => {
            const isDownloading = downloadingId === item.id;
            return (
              <View style={styles.docCard}>
                <View style={styles.cardHeader}>
                  <View style={styles.docIconBox}>
                    <Ionicons name="document-text" size={22} color={colors.primary} />
                  </View>
                  <View style={styles.docInfo}>
                    <Text style={styles.docTitle}>{item.title}</Text>
                    <Text style={styles.docFolder}>
                      📁 {item.folder?.name || 'General'} • v{item.version || '1.0'}
                    </Text>
                    <Text style={styles.docMeta}>
                      {item.file_name} ({item.file_size || 'PDF'})
                    </Text>
                  </View>
                </View>

                {item.description ? (
                  <Text style={styles.docDesc}>{item.description}</Text>
                ) : null}

                <View style={styles.cardFooter}>
                  <Text style={styles.docDate}>
                    Updated {new Date(item.updated_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })}
                  </Text>
                  <TouchableOpacity
                    style={[styles.downloadBtn, isDownloading && { opacity: 0.7 }]}
                    disabled={isDownloading}
                    onPress={() => handleDownloadDoc(item)}
                  >
                    {isDownloading ? (
                      <ActivityIndicator size="small" color="#fff" />
                    ) : (
                      <>
                        <Ionicons name="download-outline" size={14} color="#fff" />
                        <Text style={styles.downloadBtnText}>Download</Text>
                      </>
                    )}
                  </TouchableOpacity>
                </View>
              </View>
            );
          }}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Ionicons name="document-outline" size={44} color={colors.textMuted} />
              <Text style={styles.emptyText}>No policy documents found.</Text>
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
    paddingVertical: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 13,
    color: colors.textPrimary,
  },
  folderRow: {
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: colors.borderLight,
    paddingBottom: 10,
  },
  folderPillsContainer: {
    paddingHorizontal: 16,
    gap: 8,
  },
  folderPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: '#f1f5f9',
    borderWidth: 1,
    borderColor: colors.border,
  },
  folderPillActive: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  folderPillText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  folderPillTextActive: {
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
  docCard: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 12,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
  },
  docIconBox: {
    width: 40,
    height: 40,
    borderRadius: 10,
    backgroundColor: '#e0f2fe',
    alignItems: 'center',
    justifyContent: 'center',
  },
  docInfo: {
    flex: 1,
  },
  docTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  docFolder: {
    fontSize: 11,
    fontWeight: '600',
    color: colors.textSecondary,
    marginTop: 2,
  },
  docMeta: {
    fontSize: 10,
    color: colors.textMuted,
    marginTop: 2,
  },
  docDesc: {
    fontSize: 11,
    color: colors.textSecondary,
    backgroundColor: '#f8fafc',
    padding: 8,
    borderRadius: 8,
    marginTop: 10,
  },
  cardFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 12,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: colors.borderLight,
  },
  docDate: {
    fontSize: 10,
    color: colors.textMuted,
  },
  downloadBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: colors.success,
  },
  downloadBtnText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#fff',
  },
});
