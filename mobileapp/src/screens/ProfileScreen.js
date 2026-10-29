import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  TouchableOpacity,
  Image,
  Alert,
  ActivityIndicator,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';

export const ProfileScreen = ({ onNavigate }) => {
  const { user, updateProfile, changePassword } = useAuth();
  const isAdmin = user?.role === 'superadmin' || user?.role === 'admin';

  // Profile Fields State
  const [name, setName] = useState(user?.name || '');
  const [email, setEmail] = useState(user?.email || '');
  const [mobileNumber, setMobileNumber] = useState(user?.mobile_number || '');
  const [designation, setDesignation] = useState(user?.designation || '');
  const [department, setDepartment] = useState(user?.department || '');
  const [city, setCity] = useState(user?.city || '');
  const [state, setState] = useState(user?.state || '');
  const [pincode, setPincode] = useState(user?.pincode || '');
  const [address, setAddress] = useState(user?.address || '');
  const [bio, setBio] = useState(user?.bio || '');
  const [savingProfile, setSavingProfile] = useState(false);

  // Password Fields State
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [savingPassword, setSavingPassword] = useState(false);

  const handleUpdateProfile = async () => {
    if (!isAdmin) {
      Alert.alert(
        'View-Only Access',
        'Official employee records are maintained by Human Resources. You cannot edit these details directly.'
      );
      return;
    }

    if (!name.trim()) {
      Alert.alert('Required', 'Please enter your full name.');
      return;
    }
    if (!email.trim()) {
      Alert.alert('Required', 'Please enter your email address.');
      return;
    }
    if (!mobileNumber.trim()) {
      Alert.alert('Required', 'Please enter your Indian mobile number.');
      return;
    }

    setSavingProfile(true);
    try {
      await updateProfile({
        name: name.trim(),
        email: email.trim(),
        mobile_number: mobileNumber.trim(),
        designation: designation.trim(),
        department: department.trim(),
        city: city.trim(),
        state: state.trim(),
        pincode: pincode.trim(),
        address: address.trim(),
        bio: bio.trim(),
      });
      Alert.alert('Success', 'Profile details updated successfully!');
    } catch (e) {
      Alert.alert('Update Failed', e.message);
    } finally {
      setSavingProfile(false);
    }
  };

  const handleChangePassword = async () => {
    if (!currentPassword) {
      Alert.alert('Required', 'Please enter your current password.');
      return;
    }
    if (newPassword.length < 8) {
      Alert.alert('Validation Error', 'New password must be at least 8 characters long.');
      return;
    }
    if (newPassword !== confirmPassword) {
      Alert.alert('Mismatch', 'Confirm password does not match new password.');
      return;
    }

    setSavingPassword(true);
    try {
      await changePassword(currentPassword, newPassword, confirmPassword);
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      Alert.alert('Security Notice', 'Your account password has been updated successfully!');
    } catch (e) {
      Alert.alert('Password Error', e.message);
    } finally {
      setSavingPassword(false);
    }
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {/* Header Bar */}
      <View style={styles.topNav}>
        <TouchableOpacity style={styles.backBtn} onPress={() => onNavigate('dashboard')}>
          <Ionicons name="arrow-back" size={20} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.topNavTitle}>
          {isAdmin ? 'Superadmin Profile Settings' : 'My Employee Profile'}
        </Text>
      </View>

      {/* Role Notice */}
      {!isAdmin && (
        <View style={styles.noticeCard}>
          <Ionicons name="lock-closed" size={18} color="#d97706" />
          <View style={{ flex: 1 }}>
            <Text style={styles.noticeTitle}>View-Only Profile Record</Text>
            <Text style={styles.noticeText}>
              Official employee identity, salary and branch records can only be updated by Superadmin/HR. You may update your account password below.
            </Text>
          </View>
        </View>
      )}

      {/* Profile Overview Card */}
      <View style={styles.profileHeaderCard}>
        <Image
          source={{
            uri:
              user?.avatar_url ||
              `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&background=4f46e5&color=fff`,
          }}
          style={styles.avatarImg}
        />
        <View style={styles.headerInfo}>
          <Text style={styles.headerName}>{user?.name}</Text>
          <Text style={styles.headerRole}>
            {user?.designation || 'Team Member'} • {user?.department || 'Operations'}
          </Text>
          <View style={styles.roleBadge}>
            <Text style={styles.roleBadgeText}>
              {isAdmin ? (user?.role === 'superadmin' ? 'SUPER ADMINISTRATOR' : 'ADMINISTRATOR') : 'ACTIVE EMPLOYEE'}
            </Text>
          </View>
        </View>
      </View>

      {/* Form: Profile Information */}
      <View style={styles.formCard}>
        <View style={styles.formCardHeader}>
          <Ionicons name="person-circle-outline" size={20} color={colors.primary} />
          <Text style={styles.formCardTitle}>
            {isAdmin ? 'Personal & Official Details' : 'Official Registered Information'}
          </Text>
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.fieldLabel}>FULL NAME</Text>
          <TextInput
            style={[styles.input, !isAdmin && styles.inputDisabled]}
            value={name}
            onChangeText={setName}
            editable={isAdmin}
          />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.fieldLabel}>OFFICIAL EMAIL</Text>
          <TextInput
            style={[styles.input, !isAdmin && styles.inputDisabled]}
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            editable={isAdmin}
          />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.fieldLabel}>INDIAN MOBILE (+91)</Text>
          <TextInput
            style={[styles.input, !isAdmin && styles.inputDisabled]}
            value={mobileNumber}
            onChangeText={setMobileNumber}
            keyboardType="phone-pad"
            editable={isAdmin}
          />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.fieldLabel}>DESIGNATION</Text>
          <TextInput
            style={[styles.input, !isAdmin && styles.inputDisabled]}
            value={designation}
            onChangeText={setDesignation}
            editable={isAdmin}
          />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.fieldLabel}>DEPARTMENT</Text>
          <TextInput
            style={[styles.input, !isAdmin && styles.inputDisabled]}
            value={department}
            onChangeText={setDepartment}
            editable={isAdmin}
          />
        </View>

        <View style={styles.rowTwoCols}>
          <View style={[styles.fieldGroup, { flex: 1 }]}>
            <Text style={styles.fieldLabel}>CITY</Text>
            <TextInput
              style={[styles.input, !isAdmin && styles.inputDisabled]}
              value={city}
              onChangeText={setCity}
              editable={isAdmin}
            />
          </View>
          <View style={[styles.fieldGroup, { flex: 1 }]}>
            <Text style={styles.fieldLabel}>STATE</Text>
            <TextInput
              style={[styles.input, !isAdmin && styles.inputDisabled]}
              value={state}
              onChangeText={setState}
              editable={isAdmin}
            />
          </View>
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.fieldLabel}>ADDRESS</Text>
          <TextInput
            style={[styles.input, !isAdmin && styles.inputDisabled]}
            value={address}
            onChangeText={setAddress}
            editable={isAdmin}
          />
        </View>

        {isAdmin && (
          <TouchableOpacity style={styles.saveBtn} onPress={handleUpdateProfile} disabled={savingProfile}>
            {savingProfile ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.saveBtnText}>Save Profile Changes</Text>
            )}
          </TouchableOpacity>
        )}
      </View>

      {/* Form: Password Change */}
      <View style={styles.formCard}>
        <View style={styles.formCardHeader}>
          <Ionicons name="key-outline" size={20} color={colors.warning} />
          <Text style={styles.formCardTitle}>Account Security & Password</Text>
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.fieldLabel}>CURRENT PASSWORD *</Text>
          <TextInput
            style={styles.input}
            value={currentPassword}
            onChangeText={setCurrentPassword}
            secureTextEntry
            placeholder="••••••••"
          />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.fieldLabel}>NEW PASSWORD (MIN 8 CHARS) *</Text>
          <TextInput
            style={styles.input}
            value={newPassword}
            onChangeText={setNewPassword}
            secureTextEntry
            placeholder="••••••••"
          />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.fieldLabel}>CONFIRM NEW PASSWORD *</Text>
          <TextInput
            style={styles.input}
            value={confirmPassword}
            onChangeText={setConfirmPassword}
            secureTextEntry
            placeholder="••••••••"
          />
        </View>

        <TouchableOpacity style={styles.pwdBtn} onPress={handleChangePassword} disabled={savingPassword}>
          {savingPassword ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.pwdBtnText}>Update Account Password</Text>
          )}
        </TouchableOpacity>
      </View>
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
  topNav: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 14,
  },
  backBtn: {
    padding: 4,
  },
  topNavTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  noticeCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: '#fffbeb',
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
    borderColor: '#fde68a',
    marginBottom: 14,
  },
  noticeTitle: {
    fontSize: 12,
    fontWeight: '800',
    color: '#92400e',
  },
  noticeText: {
    fontSize: 10.5,
    color: '#78350f',
    marginTop: 2,
    lineHeight: 14,
  },
  profileHeaderCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    backgroundColor: '#fff',
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 14,
  },
  avatarImg: {
    width: 60,
    height: 60,
    borderRadius: 18,
    borderWidth: 2,
    borderColor: colors.primaryLight,
  },
  headerInfo: {
    flex: 1,
  },
  headerName: {
    fontSize: 16,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  headerRole: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 1,
  },
  roleBadge: {
    backgroundColor: colors.primaryLight,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
    alignSelf: 'flex-start',
    marginTop: 6,
  },
  roleBadgeText: {
    fontSize: 9,
    fontWeight: '800',
    color: colors.primary,
    letterSpacing: 0.5,
  },
  formCard: {
    backgroundColor: '#fff',
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 14,
  },
  formCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: colors.borderLight,
    marginBottom: 12,
  },
  formCardTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  fieldGroup: {
    marginBottom: 12,
  },
  rowTwoCols: {
    flexDirection: 'row',
    gap: 10,
  },
  fieldLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.textSecondary,
    marginBottom: 5,
    letterSpacing: 0.5,
  },
  input: {
    backgroundColor: colors.surfaceSubtle,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 9,
    fontSize: 13,
    color: colors.textPrimary,
  },
  inputDisabled: {
    backgroundColor: '#f1f5f9',
    color: '#64748b',
  },
  saveBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 12,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 6,
  },
  saveBtnText: {
    fontSize: 13,
    fontWeight: '800',
    color: '#fff',
  },
  pwdBtn: {
    backgroundColor: colors.textPrimary,
    paddingVertical: 12,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 6,
  },
  pwdBtnText: {
    fontSize: 13,
    fontWeight: '800',
    color: '#fff',
  },
});
