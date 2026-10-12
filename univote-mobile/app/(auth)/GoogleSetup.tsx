import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Image,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useAuth } from '@/context/AuthContext';
import { Colors } from '@/constants/Colors';
import { api } from '@/services/api';
import FaceVerificationModal from '@/components/FaceVerificationModal';

function PasswordStrength({ password }: { password: string }) {
  if (!password) return null;
  const score = password.length < 6 ? 1 : password.length < 10 ? 2 : 3;
  const labels = ['', 'Weak', 'Fair', 'Strong'];
  const barColors = ['', Colors.error, Colors.warning, Colors.success];

  return (
    <View style={styles.strengthRow}>
      {[1, 2, 3].map((item) => (
        <View
          key={item}
          style={[
            styles.strengthBar,
            { backgroundColor: item <= score ? barColors[score] : '#e0e0e0' },
          ]}
        />
      ))}
      <Text style={[styles.strengthLabel, { color: barColors[score] }]}>
        {labels[score]}
      </Text>
    </View>
  );
}

export default function GoogleSetupScreen() {
  const { user, completeProfileSetup, finishRegistration, logout } = useAuth();
  const router = useRouter();

  // If user profile is already saved, default to face enrollment step (step 2)
  const [step, setStep] = useState<number>(user?.is_profile_completed ? 2 : 1);

  // App-exclusive password
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);

  // Profile details
  const [fullName, setFullName] = useState('');
  const [age, setAge] = useState('');
  const [sex, setSex] = useState('Male');
  const [course, setCourse] = useState('');
  const [yearLevel, setYearLevel] = useState('1st Year');
  const [organizationId, setOrganizationId] = useState<number | undefined>();

  // Organizations
  const [organizations, setOrganizations] = useState<{ id: number; name: string; code: string }[]>([]);
  const [organizationsLoading, setOrganizationsLoading] = useState(true);
  const [organizationsError, setOrganizationsError] = useState('');

  // States
  const [saving, setSaving] = useState(false);
  const [faceModalVisible, setFaceModalVisible] = useState(false);

  const loadOrganizations = async () => {
    setOrganizationsLoading(true);
    setOrganizationsError('');
    try {
      const response = await api.getOrganizations();
      if (response.data && Array.isArray(response.data)) {
        setOrganizations(response.data);
      } else {
        setOrganizationsError('Organizations are unavailable. Please contact an administrator.');
      }
    } catch {
      setOrganizationsError('Failed to load organizations. Please check your connection.');
    } finally {
      setOrganizationsLoading(false);
    }
  };

  useEffect(() => {
    loadOrganizations();
  }, []);

  const handleSaveAndProceedToFaceScan = async () => {
    // 1. Password validation
    if (!password || !confirmPassword) {
      Alert.alert('Missing Password', 'Please create and confirm your app-exclusive password.');
      return;
    }
    if (password.length < 6) {
      Alert.alert('Weak Password', 'App password must be at least 6 characters long.');
      return;
    }
    if (password !== confirmPassword) {
      Alert.alert('Password Mismatch', 'Passwords do not match. Please verify and try again.');
      return;
    }

    // 2. Profile validation
    if (!fullName.trim()) {
      Alert.alert('Missing Field', 'Please enter your full name.');
      return;
    }
    if (!age.trim() || isNaN(Number(age)) || Number(age) < 15 || Number(age) > 100) {
      Alert.alert('Invalid Age', 'Please enter a valid age between 15 and 100.');
      return;
    }
    if (!course.trim()) {
      Alert.alert('Missing Field', 'Please enter your course (e.g. BS Information Technology).');
      return;
    }
    if (!organizationId) {
      Alert.alert('Missing Field', 'Please select your student organization.');
      return;
    }

    setSaving(true);
    const result = await completeProfileSetup({
      password,
      fullName: fullName.trim(),
      age: Number(age),
      sex,
      course: course.trim(),
      yearLevel,
      organizationId,
    });
    setSaving(false);

    if (!result.success) {
      Alert.alert('Setup Failed', result.error || 'Could not save profile details.');
      return;
    }

    // Advance to face enrollment step
    setStep(2);
    setFaceModalVisible(true);
  };

  const handleFaceEnrollSuccess = async () => {
    try {
      await finishRegistration();
      setFaceModalVisible(false);
      Alert.alert(
        'Account Activated!',
        'Your registration and face scan have been completed successfully. Welcome to UniVote!',
        [
          {
            text: 'Enter Dashboard',
            onPress: () => router.replace('/(tabs)/(voter)/VoterDashboard'),
          },
        ]
      );
    } catch {
      setFaceModalVisible(false);
      router.replace('/(tabs)/(voter)/VoterDashboard');
    }
  };

  const handleFaceModalClose = () => {
    Alert.alert(
      'Face Scan Required',
      'Facial verification registration is mandatory to activate your account and access the voting dashboard.',
      [
        { text: 'Complete Face Scan', style: 'default', onPress: () => setFaceModalVisible(true) },
        {
          text: 'Log Out',
          style: 'destructive',
          onPress: async () => {
            setFaceModalVisible(false);
            await logout();
            router.replace('/(auth)/VoterLogin');
          },
        },
      ]
    );
  };

  const handleLogout = async () => {
    Alert.alert(
      'Cancel Registration',
      'Are you sure you want to log out and complete setup later?',
      [
        { text: 'Stay', style: 'cancel' },
        {
          text: 'Log Out',
          style: 'destructive',
          onPress: async () => {
            await logout();
            router.replace('/(auth)/VoterLogin');
          },
        },
      ]
    );
  };

  const pwMismatch = confirmPassword.length > 0 && password !== confirmPassword;

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView
        contentContainerStyle={styles.scroll}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.logoArea}>
          <Image
            source={require('../../assets/univote-logo.jpg')}
            style={styles.logoImage}
          />
          <Text style={styles.appName}>UniVote</Text>
          <Text style={styles.appTagline}>Campus Voting & Election System</Text>
        </View>

        <View style={styles.card}>
          <View style={styles.badgeRow}>
            <View style={styles.badge}>
              <Ionicons name="sparkles" size={13} color={Colors.primaryLight} />
              <Text style={styles.badgeText}>First-Time Google Setup</Text>
            </View>
          </View>

          {step === 1 ? (
            <>
              <Text style={styles.title}>Account Registration</Text>
              <Text style={styles.subtitle}>
                Please create your app password and fill in your student details before face verification.
              </Text>

              {/* Connected Google Account pill */}
              <View style={styles.accountPill}>
                <Ionicons name="logo-google" size={18} color="#EA4335" />
                <View style={{ flex: 1, marginLeft: 10 }}>
                  <Text style={styles.accountLabel}>Connected Google Email</Text>
                  <Text style={styles.accountEmail} numberOfLines={1}>
                    {user?.email || 'Logged in with Google'}
                  </Text>
                </View>
                <Ionicons name="checkmark-circle" size={18} color={Colors.success} />
              </View>

              {/* Section 1: App Exclusive Password */}
              <View style={styles.sectionHeader}>
                <Ionicons name="key-outline" size={16} color={Colors.primaryLight} />
                <Text style={styles.sectionTitle}>1. Create App-Exclusive Password</Text>
              </View>
              <Text style={styles.sectionDesc}>
                Use this password to log in directly with your Gmail address on any device.
              </Text>

              <View style={styles.field}>
                <Text style={styles.label}>App-Exclusive Password</Text>
                <View style={styles.pwRow}>
                  <TextInput
                    style={[styles.input, { flex: 1 }]}
                    placeholder="Create a password (min. 6 characters)"
                    placeholderTextColor={Colors.textMuted}
                    value={password}
                    onChangeText={setPassword}
                    secureTextEntry={!showPassword}
                  />
                  <TouchableOpacity
                    style={styles.eyeBtn}
                    onPress={() => setShowPassword((v) => !v)}
                  >
                    <Ionicons
                      name={showPassword ? 'eye-off' : 'eye'}
                      size={20}
                      color={Colors.primary}
                    />
                  </TouchableOpacity>
                </View>
                <PasswordStrength password={password} />
              </View>

              <View style={styles.field}>
                <Text style={styles.label}>Confirm App Password</Text>
                <View style={styles.pwRow}>
                  <TextInput
                    style={[styles.input, { flex: 1 }, pwMismatch && styles.inputError]}
                    placeholder="Re-enter your app password"
                    placeholderTextColor={Colors.textMuted}
                    value={confirmPassword}
                    onChangeText={setConfirmPassword}
                    secureTextEntry={!showConfirm}
                  />
                  <TouchableOpacity
                    style={styles.eyeBtn}
                    onPress={() => setShowConfirm((v) => !v)}
                  >
                    <Ionicons
                      name={showConfirm ? 'eye-off' : 'eye'}
                      size={20}
                      color={Colors.primary}
                    />
                  </TouchableOpacity>
                </View>
                {pwMismatch && (
                  <Text style={styles.errorText}>Passwords do not match</Text>
                )}
              </View>

              {/* Section 2: Student Details */}
              <View style={[styles.sectionHeader, { marginTop: 12 }]}>
                <Ionicons name="person-outline" size={16} color={Colors.primaryLight} />
                <Text style={styles.sectionTitle}>2. Student Information</Text>
              </View>

              <View style={styles.field}>
                <Text style={styles.label}>Full Name</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Dela Cruz, Juan J"
                  placeholderTextColor={Colors.textMuted}
                  value={fullName}
                  onChangeText={setFullName}
                  autoCapitalize="words"
                />
                <Text style={styles.hint}>Format: Dela Cruz, Juan J</Text>
              </View>

              <View style={styles.rowTwoCols}>
                <View style={[styles.field, { flex: 1 }]}>
                  <Text style={styles.label}>Age</Text>
                  <TextInput
                    style={styles.input}
                    placeholder="e.g. 20"
                    placeholderTextColor={Colors.textMuted}
                    value={age}
                    onChangeText={setAge}
                    keyboardType="number-pad"
                    maxLength={3}
                  />
                </View>
                <View style={[styles.field, { flex: 1 }]}>
                  <Text style={styles.label}>Sex</Text>
                  <View style={styles.choiceRowCompact}>
                    {['Male', 'Female', 'Other'].map((option) => (
                      <TouchableOpacity
                        key={option}
                        style={[styles.choiceSmall, sex === option && styles.choiceSelected]}
                        onPress={() => setSex(option)}
                      >
                        <Text
                          style={[
                            styles.choiceSmallText,
                            sex === option && styles.choiceTextSelected,
                          ]}
                        >
                          {option}
                        </Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                </View>
              </View>

              <View style={styles.field}>
                <Text style={styles.label}>Course</Text>
                <TextInput
                  style={styles.input}
                  placeholder="e.g. BS Information Technology"
                  placeholderTextColor={Colors.textMuted}
                  value={course}
                  onChangeText={setCourse}
                />
              </View>

              <View style={styles.field}>
                <Text style={styles.label}>Year Level</Text>
                <View style={styles.choiceRow}>
                  {['1st Year', '2nd Year', '3rd Year', '4th Year', 'Graduate'].map((level) => (
                    <TouchableOpacity
                      key={level}
                      style={[styles.choice, yearLevel === level && styles.choiceSelected]}
                      onPress={() => setYearLevel(level)}
                    >
                      <Text
                        style={[
                          styles.choiceText,
                          yearLevel === level && styles.choiceTextSelected,
                        ]}
                      >
                        {level}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>

              <View style={styles.field}>
                <Text style={styles.label}>Organization</Text>
                {organizationsLoading ? (
                  <View style={styles.loadingBox}>
                    <ActivityIndicator color={Colors.primaryLight} />
                    <Text style={styles.loadingBoxText}>Loading organizations...</Text>
                  </View>
                ) : organizations.length > 0 ? (
                  <View style={styles.choiceRow}>
                    {organizations.map((org) => (
                      <TouchableOpacity
                        key={org.id}
                        style={[
                          styles.choice,
                          organizationId === org.id && styles.choiceSelected,
                        ]}
                        onPress={() => setOrganizationId(org.id)}
                      >
                        <Text
                          style={[
                            styles.choiceText,
                            organizationId === org.id && styles.choiceTextSelected,
                          ]}
                        >
                          {org.name} ({org.code})
                        </Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                ) : (
                  <View style={styles.organizationEmpty}>
                    <Text style={styles.organizationEmptyText}>
                      {organizationsError || 'No organizations found. Please ask an admin to add your organization.'}
                    </Text>
                    <TouchableOpacity onPress={loadOrganizations} style={{ marginTop: 6 }}>
                      <Text style={styles.retryText}>Retry Loading</Text>
                    </TouchableOpacity>
                  </View>
                )}
              </View>

              <TouchableOpacity
                style={[styles.primaryBtn, saving && styles.btnDisabled]}
                onPress={handleSaveAndProceedToFaceScan}
                disabled={saving}
                activeOpacity={0.85}
              >
                {saving ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <>
                    <Text style={styles.primaryBtnText}>Save & Proceed to Face Scan</Text>
                    <Ionicons name="camera-outline" size={20} color="#fff" style={{ marginLeft: 6 }} />
                  </>
                )}
              </TouchableOpacity>
            </>
          ) : (
            <View style={styles.faceStepContainer}>
              <View style={styles.faceIconCircle}>
                <Ionicons name="scan" size={48} color={Colors.primaryLight} />
              </View>
              <Text style={styles.title}>Facial Registration Required</Text>
              <Text style={styles.subtitle}>
                Profile details saved! To finish activating your account and enter the dashboard, please complete your facial biometric registration.
              </Text>

              <View style={styles.requirementsList}>
                <View style={styles.reqRow}>
                  <Ionicons name="sunny-outline" size={16} color={Colors.primaryLight} />
                  <Text style={styles.reqText}>Ensure good lighting on your face</Text>
                </View>
                <View style={styles.reqRow}>
                  <Ionicons name="glasses-outline" size={16} color={Colors.primaryLight} />
                  <Text style={styles.reqText}>Remove caps, sunglasses, or masks</Text>
                </View>
                <View style={styles.reqRow}>
                  <Ionicons name="person-outline" size={16} color={Colors.primaryLight} />
                  <Text style={styles.reqText}>Look straight into the camera</Text>
                </View>
              </View>

              <TouchableOpacity
                style={styles.primaryBtn}
                onPress={() => setFaceModalVisible(true)}
                activeOpacity={0.85}
              >
                <Text style={styles.primaryBtnText}>Start Face Scan</Text>
                <Ionicons name="camera" size={20} color="#fff" style={{ marginLeft: 8 }} />
              </TouchableOpacity>
            </View>
          )}

          <TouchableOpacity style={styles.logoutRow} onPress={handleLogout}>
            <Ionicons name="log-out-outline" size={16} color={Colors.textMuted} />
            <Text style={styles.logoutText}>Cancel & Sign Out</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>

      {/* Face Scan Biometric Enrollment Modal */}
      <FaceVerificationModal
        visible={faceModalVisible}
        action="enroll"
        context="general"
        mandatory={true}
        onClose={handleFaceModalClose}
        onSuccess={handleFaceEnrollSuccess}
      />
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.primary,
  },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 36,
    paddingHorizontal: 20,
  },
  logoArea: {
    alignItems: 'center',
    marginBottom: 20,
  },
  logoImage: {
    width: 90,
    height: 90,
    borderRadius: 14,
    marginBottom: 10,
  },
  appName: {
    color: '#fff',
    fontSize: 24,
    fontWeight: '900',
    letterSpacing: 0.5,
  },
  appTagline: {
    color: 'rgba(255,255,255,0.7)',
    fontSize: 12,
    marginTop: 2,
  },
  card: {
    width: '100%',
    maxWidth: 420,
    backgroundColor: Colors.white,
    borderRadius: 24,
    padding: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.25,
    shadowRadius: 20,
    elevation: 12,
  },
  badgeRow: {
    alignItems: 'center',
    marginBottom: 10,
  },
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#E8F5E9',
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 20,
    gap: 6,
  },
  badgeText: {
    color: Colors.primaryLight,
    fontSize: 12,
    fontWeight: '700',
  },
  title: {
    fontSize: 22,
    fontWeight: '800',
    color: Colors.text,
    textAlign: 'center',
    marginBottom: 6,
  },
  subtitle: {
    fontSize: 13,
    color: Colors.textSecondary,
    textAlign: 'center',
    lineHeight: 18,
    marginBottom: 14,
  },
  accountPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f6f8f6',
    borderWidth: 1,
    borderColor: '#e2eae2',
    borderRadius: 12,
    padding: 12,
    marginBottom: 16,
  },
  accountLabel: {
    fontSize: 11,
    color: Colors.textMuted,
    fontWeight: '500',
  },
  accountEmail: {
    fontSize: 13,
    color: Colors.text,
    fontWeight: '700',
    marginTop: 1,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 4,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: Colors.text,
  },
  sectionDesc: {
    fontSize: 12,
    color: Colors.textMuted,
    marginBottom: 10,
    lineHeight: 16,
  },
  field: {
    marginBottom: 13,
  },
  rowTwoCols: {
    flexDirection: 'row',
    gap: 12,
  },
  label: {
    fontSize: 13,
    fontWeight: '600',
    color: '#333',
    marginBottom: 6,
  },
  input: {
    height: 48,
    borderWidth: 1.5,
    borderColor: Colors.border,
    borderRadius: 12,
    paddingHorizontal: 14,
    fontSize: 14,
    color: Colors.text,
    backgroundColor: '#fafafa',
  },
  inputError: {
    borderColor: Colors.error,
  },
  pwRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  eyeBtn: {
    position: 'absolute',
    right: 14,
    height: 48,
    justifyContent: 'center',
  },
  hint: {
    fontSize: 11,
    color: Colors.textMuted,
    marginTop: 4,
    marginLeft: 2,
  },
  errorText: {
    fontSize: 11,
    color: Colors.error,
    marginTop: 4,
    marginLeft: 2,
  },
  strengthRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 6,
    gap: 6,
  },
  strengthBar: {
    flex: 1,
    height: 4,
    borderRadius: 2,
  },
  strengthLabel: {
    fontSize: 11,
    fontWeight: '700',
    width: 42,
  },
  choiceRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  choiceRowCompact: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
  },
  choice: {
    borderWidth: 1,
    borderColor: Colors.border,
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 7,
    backgroundColor: '#fcfcfc',
  },
  choiceSmall: {
    borderWidth: 1,
    borderColor: Colors.border,
    borderRadius: 8,
    paddingHorizontal: 8,
    paddingVertical: 6,
    backgroundColor: '#fcfcfc',
  },
  choiceSelected: {
    backgroundColor: Colors.primaryLight,
    borderColor: Colors.primaryLight,
  },
  choiceText: {
    color: Colors.textSecondary,
    fontSize: 12,
  },
  choiceSmallText: {
    color: Colors.textSecondary,
    fontSize: 11,
  },
  choiceTextSelected: {
    color: Colors.white,
    fontWeight: '700',
  },
  loadingBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    padding: 10,
  },
  loadingBoxText: {
    fontSize: 12,
    color: Colors.textMuted,
  },
  organizationEmpty: {
    backgroundColor: '#f4f7f4',
    borderRadius: 8,
    padding: 12,
  },
  organizationEmptyText: {
    color: Colors.textSecondary,
    fontSize: 12,
    lineHeight: 18,
  },
  retryText: {
    color: Colors.primaryLight,
    fontWeight: '700',
    fontSize: 12,
  },
  primaryBtn: {
    height: 50,
    backgroundColor: Colors.primaryLight,
    borderRadius: 25,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 12,
    shadowColor: Colors.primaryLight,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.35,
    shadowRadius: 8,
    elevation: 6,
  },
  btnDisabled: {
    opacity: 0.65,
  },
  primaryBtnText: {
    color: '#fff',
    fontSize: 15,
    fontWeight: '700',
    letterSpacing: 0.3,
  },
  logoutRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 16,
    gap: 6,
    paddingVertical: 8,
  },
  logoutText: {
    color: Colors.textMuted,
    fontSize: 13,
  },
  faceStepContainer: {
    alignItems: 'center',
    paddingVertical: 8,
  },
  faceIconCircle: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: '#E8F5E9',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  requirementsList: {
    width: '100%',
    backgroundColor: '#f8faf8',
    borderRadius: 12,
    padding: 14,
    marginVertical: 14,
    gap: 10,
  },
  reqRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  reqText: {
    fontSize: 12,
    color: Colors.textSecondary,
  },
});
