import React, { useState } from 'react';
import {
  SafeAreaView,
  StyleSheet,
  View,
  ActivityIndicator,
  StatusBar,
  Platform,
} from 'react-native';
import { StatusBar as ExpoStatusBar } from 'expo-status-bar';
import { AuthProvider, useAuth } from './src/context/AuthContext';
import { LoginScreen } from './src/screens/LoginScreen';
import { DashboardScreen } from './src/screens/DashboardScreen';
import { ProfileScreen } from './src/screens/ProfileScreen';
import { SettingsManagerScreen } from './src/screens/SettingsManagerScreen';
import { EmployeeDirectoryScreen } from './src/screens/EmployeeDirectoryScreen';
import { AttendanceScreen } from './src/screens/AttendanceScreen';
import { LeaveScreen } from './src/screens/LeaveScreen';
import { PayslipsScreen } from './src/screens/PayslipsScreen';
import { ResignationScreen } from './src/screens/ResignationScreen';
import { ContactDirectoryScreen } from './src/screens/ContactDirectoryScreen';
import { PoliciesScreen } from './src/screens/PoliciesScreen';
import { Header } from './src/components/Header';
import { colors } from './src/theme/colors';

function AppContent() {
  const { user, token, isLoading } = useAuth();
  const [currentScreen, setCurrentScreen] = useState('dashboard');

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  if (!token || !user) {
    return <LoginScreen />;
  }

  return (
    <View style={styles.appContainer}>
      <Header onNavigate={setCurrentScreen} currentScreen={currentScreen} />
      <View style={styles.screenWrapper}>
        {currentScreen === 'profile' ? (
          <ProfileScreen onNavigate={setCurrentScreen} />
        ) : currentScreen === 'settings_manager' ? (
          <SettingsManagerScreen onNavigate={setCurrentScreen} />
        ) : currentScreen === 'employees' ? (
          <EmployeeDirectoryScreen onNavigate={setCurrentScreen} />
        ) : currentScreen === 'attendance_history' ? (
          <AttendanceScreen onNavigate={setCurrentScreen} />
        ) : currentScreen === 'leave_manager' ? (
          <LeaveScreen onNavigate={setCurrentScreen} />
        ) : currentScreen === 'payslips' ? (
          <PayslipsScreen onNavigate={setCurrentScreen} />
        ) : currentScreen === 'contacts' ? (
          <ContactDirectoryScreen onNavigate={setCurrentScreen} />
        ) : currentScreen === 'policies' ? (
          <PoliciesScreen onNavigate={setCurrentScreen} />
        ) : currentScreen === 'resignation' ? (
          <ResignationScreen onNavigate={setCurrentScreen} />
        ) : (
          <DashboardScreen onNavigate={setCurrentScreen} />
        )}
      </View>
    </View>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <SafeAreaView style={styles.safeArea}>
        <ExpoStatusBar style="dark" backgroundColor={colors.surface} />
        <AppContent />
      </SafeAreaView>
    </AuthProvider>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: colors.surface,
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
  },
  appContainer: {
    flex: 1,
    backgroundColor: colors.background,
  },
  screenWrapper: {
    flex: 1,
  },
  loadingContainer: {
    flex: 1,
    backgroundColor: colors.background,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
