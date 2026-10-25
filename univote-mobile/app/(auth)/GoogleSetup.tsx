import React, { useEffect, useState, useMemo, useRef } from 'react';
import {
  View,
  Text,
  TextInput,
  Pressable,
  TouchableOpacity,
  StyleSheet,
  Image,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator,
  Modal,
  FlatList,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useAuth } from '@/context/AuthContext';
import { Colors } from '@/constants/Colors';
import { api } from '@/services/api';
import FaceVerificationModal from '@/components/FaceVerificationModal';

export interface CourseOption {
  name: string;
  code: string;
  college: string;
  orgKeyword: string;
}

export const SNSU_OFFICIAL_COURSES: CourseOption[] = [
  // College of Engineering
  { name: 'Bachelor of Science in Civil Engineering (BSCE)', code: 'BSCE', college: 'College of Engineering', orgKeyword: 'ENG' },
  { name: 'Bachelor of Science in Electronics Engineering (BSECE)', code: 'BSECE', college: 'College of Engineering', orgKeyword: 'ENG' },
  { name: 'Bachelor of Science in Electrical Engineering (BSEE)', code: 'BSEE', college: 'College of Engineering', orgKeyword: 'ENG' },
  { name: 'Bachelor of Science in Computer Engineering (BSCoE)', code: 'BSCoE', college: 'College of Engineering', orgKeyword: 'ENG' },

  // College of Computing & Information Sciences
  { name: 'Bachelor of Science in Information Technology (BS I.T.)', code: 'BS I.T.', college: 'College of Computing & Information Sciences', orgKeyword: 'COMP' },
  { name: 'Bachelor of Science in Computer Science (BSCS)', code: 'BSCS', college: 'College of Computing & Information Sciences', orgKeyword: 'COMP' },
  { name: 'Bachelor of Science in Information System (BSIS)', code: 'BSIS', college: 'College of Computing & Information Sciences', orgKeyword: 'COMP' },

  // College of Technology
  { name: 'Bachelor of Automotive Engineering Technology', code: 'BAET', college: 'College of Technology', orgKeyword: 'TECH' },
  { name: 'Bachelor of Electrical Engineering Technology', code: 'BEET', college: 'College of Technology', orgKeyword: 'TECH' },
  { name: 'Bachelor of Electronics Engineering Technology', code: 'BExET', college: 'College of Technology', orgKeyword: 'TECH' },
  { name: 'Bachelor of Mechanical Engineering Technology', code: 'BMET', college: 'College of Technology', orgKeyword: 'TECH' },
  { name: 'Bachelor of Science in Industrial Technology', code: 'BSIT', college: 'College of Technology', orgKeyword: 'TECH' },

  // College of Business and Management
  { name: 'Bachelor of Science in Hospitality Management', code: 'BSHM', college: 'College of Business & Management', orgKeyword: 'BUS' },
  { name: 'Bachelor of Science in Tourism Management', code: 'BSTM', college: 'College of Business & Management', orgKeyword: 'BUS' },

  // College of Teacher Education
  { name: 'Bachelor of Secondary Education (BSED) - Major in English', code: 'BSED-Eng', college: 'College of Teacher Education', orgKeyword: 'TEACH' },
  { name: 'Bachelor of Secondary Education (BSED) - Major in Filipino', code: 'BSED-Fil', college: 'College of Teacher Education', orgKeyword: 'TEACH' },
  { name: 'Bachelor of Secondary Education (BSED) - Major in Science', code: 'BSED-Sci', college: 'College of Teacher Education', orgKeyword: 'TEACH' },
  { name: 'Bachelor of Secondary Education (BSED) - Major in Mathematics', code: 'BSED-Math', college: 'College of Teacher Education', orgKeyword: 'TEACH' },
  { name: 'Bachelor of Elementary Education (BEED)', code: 'BEED', college: 'College of Teacher Education', orgKeyword: 'TEACH' },
  { name: 'Bachelor of Physical Education', code: 'BPEd', college: 'College of Teacher Education', orgKeyword: 'TEACH' },
  { name: 'Bachelor of Technical Teacher Education (BTTE)', code: 'BTTE', college: 'College of Teacher Education', orgKeyword: 'TEACH' },

  // College of Arts and Sciences
  { name: 'Bachelor of Science in Environmental Science (BSES)', code: 'BSES', college: 'College of Arts and Sciences', orgKeyword: 'ARTS' },
  { name: 'Bachelor of Science in Mathematics (BS Math)', code: 'BS Math', college: 'College of Arts and Sciences', orgKeyword: 'ARTS' },
  { name: 'Bachelor of Arts in English Language', code: 'BAEL', college: 'College of Arts and Sciences', orgKeyword: 'ARTS' },
];

const YEAR_OPTIONS = [
  { level: '1st Year', description: 'Freshman' },
  { level: '2nd Year', description: 'Sophomore' },
  { level: '3rd Year', description: 'Junior' },
  { level: '4th Year', description: 'Senior' },
  { level: 'Graduate', description: 'Post-Graduate' },
];

export default function GoogleSetupScreen() {
  const { user, completeProfileSetup, finishRegistration, logout } = useAuth();
  const router = useRouter();

  // If user profile is already saved, default to face enrollment step (step 2)
  const [step, setStep] = useState<number>(user?.is_profile_completed ? 2 : 1);

  // Profile details
  const [fullName, setFullName] = useState(user?.name || '');
  const [studentId, setStudentId] = useState(user?.student_id || '');
  const studentIdInputRef = useRef<TextInput>(null);
  const [course, setCourse] = useState(user?.course && user.course !== 'Not Specified' ? user.course : '');
  const [yearLevel, setYearLevel] = useState(user?.year_level || '1st Year');
  const [organizationId, setOrganizationId] = useState<number | undefined>(user?.organization_id || undefined);

  // Custom Course input toggle
  const [isCustomCourse, setIsCustomCourse] = useState(false);

  // Active focus highlight
  const [focusedField, setFocusedField] = useState<string | null>(null);

  // Modals & Search state
  const [deptModalVisible, setDeptModalVisible] = useState(false);
  const [deptSearchQuery, setDeptSearchQuery] = useState('');
  const [courseModalVisible, setCourseModalVisible] = useState(false);
  const [courseSearchQuery, setCourseSearchQuery] = useState('');
  const [yearModalVisible, setYearModalVisible] = useState(false);

  // College Departments (Organizations)
  const [organizations, setOrganizations] = useState<{ id: number; name: string; code: string }[]>([]);
  const [organizationsLoading, setOrganizationsLoading] = useState(true);
  const [organizationsError, setOrganizationsError] = useState('');

  // States
  const [saving, setSaving] = useState(false);
  const [faceModalVisible, setFaceModalVisible] = useState(false);

  const hasLoadedInitialUser = useRef(false);

  // Sync state only once when user data becomes available
  useEffect(() => {
    if (user && !hasLoadedInitialUser.current) {
      hasLoadedInitialUser.current = true;
      if (user.name) setFullName(user.name);
      if (user.student_id) setStudentId(user.student_id);
      if (user.course && user.course !== 'Not Specified') setCourse(user.course);
      if (user.year_level) setYearLevel(user.year_level);
      if (user.organization_id) setOrganizationId(user.organization_id);
    }
  }, [user]);

  const loadOrganizations = async () => {
    setOrganizationsLoading(true);
    setOrganizationsError('');
    try {
      const response = await api.getOrganizations();
      if (response.data && Array.isArray(response.data)) {
        setOrganizations(response.data);
      } else {
        setOrganizationsError('College departments are unavailable. Please contact an administrator.');
      }
    } catch {
      setOrganizationsError('Failed to load college departments. Please check your connection.');
    } finally {
      setOrganizationsLoading(false);
    }
  };

  useEffect(() => {
    loadOrganizations();
  }, []);

  const selectedOrg = useMemo(() => {
    return organizations.find((o) => o.id === organizationId);
  }, [organizations, organizationId]);

  const filteredOrganizations = useMemo(() => {
    if (!deptSearchQuery.trim()) return organizations;
    const q = deptSearchQuery.toLowerCase();
    return organizations.filter(
      (org) => org.name.toLowerCase().includes(q) || org.code.toLowerCase().includes(q)
    );
  }, [organizations, deptSearchQuery]);

  const filteredCourses = useMemo(() => {
    if (!courseSearchQuery.trim()) return SNSU_OFFICIAL_COURSES;
    const q = courseSearchQuery.toLowerCase();
    return SNSU_OFFICIAL_COURSES.filter(
      (c) => c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q) || c.college.toLowerCase().includes(q)
    );
  }, [courseSearchQuery]);

  const handleSelectCourse = (selected: CourseOption) => {
    setCourse(selected.name);
    setIsCustomCourse(false);
    setCourseModalVisible(false);

    // Smart auto-match college department if not already selected
    if (!organizationId && organizations.length > 0) {
      const matchedOrg = organizations.find((org) => {
        const orgName = org.name.toLowerCase();
        const orgCode = org.code.toLowerCase();
        const courseCollege = selected.college.toLowerCase();

        if (selected.orgKeyword === 'COMP' && (orgCode.includes('cite') || orgCode.includes('ccis') || orgName.includes('comput') || orgName.includes('information'))) {
          return true;
        }
        if (selected.orgKeyword === 'ENG' && (orgCode.includes('coe') || orgName.includes('engineer'))) {
          return true;
        }
        if (selected.orgKeyword === 'TECH' && (orgCode.includes('cot') || orgName.includes('technol'))) {
          return true;
        }
        if (selected.orgKeyword === 'TEACH' && (orgCode.includes('cte') || orgName.includes('teacher') || orgName.includes('educat'))) {
          return true;
        }
        if (selected.orgKeyword === 'ARTS' && (orgCode.includes('cas') || orgName.includes('arts') || orgName.includes('science'))) {
          return true;
        }
        if (selected.orgKeyword === 'BUS' && (orgCode.includes('cba') || orgCode.includes('cbm') || orgName.includes('business') || orgName.includes('management') || orgName.includes('tourism') || orgName.includes('hospitality'))) {
          return true;
        }
        return orgName.includes(courseCollege) || courseCollege.includes(orgName);
      });

      if (matchedOrg) {
        setOrganizationId(matchedOrg.id);
      }
    }
  };

  const handleSaveAndProceedToFaceScan = async () => {
    // 1. Full Name Validation
    if (!fullName.trim()) {
      Alert.alert('Missing Name', 'Please enter your full name as registered in university records.');
      return;
    }

    // 2. Student ID Validation
    if (!studentId.trim()) {
      Alert.alert('Missing Student ID', 'Please enter your official student ID number (e.g. 2022-01452).');
      return;
    }

    // 3. College Department Validation
    if (!organizationId) {
      Alert.alert('Missing Department', 'Please select your college department to determine ballot positions.');
      return;
    }

    // 4. Course / Program Validation
    if (!course.trim()) {
      Alert.alert('Missing Course', 'Please select or enter your degree program / course.');
      return;
    }

    setSaving(true);
    const result = await completeProfileSetup({
      fullName: fullName.trim(),
      studentId: studentId.trim(),
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
    } catch (err: any) {
      setFaceModalVisible(false);
      Alert.alert(
        'Setup Incomplete',
        err?.message || 'Could not verify account activation. Please complete face verification to proceed.',
        [
          {
            text: 'Retry Face Scan',
            onPress: () => setFaceModalVisible(true),
          },
        ]
      );
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

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView
        contentContainerStyle={styles.scroll}
        keyboardShouldPersistTaps="handled"
        keyboardDismissMode="on-drag"
        showsVerticalScrollIndicator={false}
      >
        {/* Brand Header */}
        <View style={styles.logoArea}>
          <Image
            source={require('../../assets/univote-logo.jpg')}
            style={styles.logoImage}
          />
          <Text style={styles.appName}>UniVote</Text>
          <Text style={styles.appTagline}>Campus Voting & Election System</Text>
        </View>

        {/* Setup Card */}
        <View style={styles.card}>
          {/* Visual Step Progress Bar */}
          <View style={styles.stepProgressBar}>
            <View style={styles.stepItem}>
              <View style={[styles.stepNumberBadge, step >= 1 ? styles.stepBadgeActive : styles.stepBadgeInactive]}>
                {step > 1 ? (
                  <Ionicons name="checkmark" size={13} color="#fff" />
                ) : (
                  <Text style={styles.stepNumberTextActive}>1</Text>
                )}
              </View>
              <Text style={[styles.stepLabel, step >= 1 && styles.stepLabelActive]}>Student Profile</Text>
            </View>
            <View style={[styles.stepLine, step >= 2 && styles.stepLineActive]} />
            <View style={styles.stepItem}>
              <View style={[styles.stepNumberBadge, step === 2 ? styles.stepBadgeActive : styles.stepBadgeInactive]}>
                <Text style={step === 2 ? styles.stepNumberTextActive : styles.stepNumberTextInactive}>2</Text>
              </View>
              <Text style={[styles.stepLabel, step === 2 && styles.stepLabelActive]}>Face Scan</Text>
            </View>
          </View>

          {step === 1 ? (
            <>
              <Text style={styles.title}>Student Profile Setup</Text>
              <Text style={styles.subtitle}>
                Complete your official academic details to activate your voter eligibility.
              </Text>

              {/* Connected Google Account pill */}
              <View style={styles.accountPill}>
                <View style={styles.googleIconCircle}>
                  <Ionicons name="logo-google" size={16} color="#EA4335" />
                </View>
                <View style={{ flex: 1, marginLeft: 10 }}>
                  <Text style={styles.accountLabel}>Connected Account</Text>
                  <Text style={styles.accountEmail} numberOfLines={1}>
                    {user?.email || 'Logged in with Google'}
                  </Text>
                </View>
                <View style={styles.verifiedBadge}>
                  <Ionicons name="checkmark-circle" size={14} color="#059669" />
                  <Text style={styles.verifiedBadgeText}>Verified</Text>
                </View>
              </View>

              {/* University COMELEC Verification Reminder */}
              <View style={styles.noticeBox}>
                <View style={styles.noticeHeader}>
                  <Ionicons name="shield-checkmark" size={16} color="#B45309" />
                  <Text style={styles.noticeTitle}>Official COMELEC Verification</Text>
                </View>
                <Text style={styles.noticeText}>
                  Ensure your details match your official university registration card. COMELEC cross-verifies records to validate your ballot eligibility.
                </Text>
              </View>

              {/* Section Header */}
              <View style={styles.sectionHeader}>
                <Ionicons name="school-outline" size={16} color={Colors.primaryLight} />
                <Text style={styles.sectionTitle}>Academic Information</Text>
              </View>

              {/* 1. Full Name (Disabled / Auto-set from Google) */}
              <View style={styles.field}>
                <View style={styles.labelRow}>
                  <Text style={styles.label}>
                    Full Name <Text style={styles.requiredStar}>*</Text>
                  </Text>
                  {user?.name ? (
                    <View style={styles.fromGoogleBadge}>
                      <Ionicons name="lock-closed" size={10} color="#059669" />
                      <Text style={styles.fromGoogleText}>From Google (Locked)</Text>
                    </View>
                  ) : null}
                </View>
                <View style={[styles.inputContainer, styles.inputContainerDisabled]}>
                  <Ionicons name="person-outline" size={18} color="#9CA3AF" style={styles.inputIcon} />
                  <TextInput
                    style={[styles.textInput, styles.textInputDisabled]}
                    placeholder="e.g. Dela Cruz, Juan J."
                    placeholderTextColor="#9CA3AF"
                    value={fullName}
                    editable={false}
                  />
                  <Ionicons name="lock-closed" size={15} color="#9CA3AF" style={{ marginRight: 4 }} />
                </View>
                <Text style={styles.hint}>
                  Auto-set and linked to your Google institutional account.
                </Text>
              </View>

              {/* 2. Student ID Number */}
              <View style={styles.field}>
                <Text style={styles.label}>
                  Student ID Number <Text style={styles.requiredStar}>*</Text>
                </Text>
                <Pressable
                  style={[styles.inputContainer, focusedField === 'studentId' && styles.inputContainerFocused]}
                  onPress={() => studentIdInputRef.current?.focus()}
                  accessibilityRole="none"
                >
                  <Ionicons name="card-outline" size={18} color={focusedField === 'studentId' ? Colors.primaryLight : '#9CA3AF'} style={styles.inputIcon} />
                  <TextInput
                    ref={studentIdInputRef}
                    style={styles.textInput}
                    placeholder="e.g. 2022-01452"
                    placeholderTextColor="#9CA3AF"
                    value={studentId}
                    onChangeText={setStudentId}
                    editable
                    showSoftInputOnFocus
                    autoCapitalize="characters"
                    autoCorrect={false}
                    keyboardType="default"
                    returnKeyType="done"
                    onFocus={() => setFocusedField('studentId')}
                    onBlur={() => setFocusedField(null)}
                  />
                </Pressable>
                <Text style={styles.hint}>Official university ID / Certificate of Registration (COR) number</Text>
              </View>

              {/* 3. Degree Program / Course Dropdown */}
              <View style={styles.field}>
                <View style={styles.labelRow}>
                  <Text style={styles.label}>
                    Degree Program / Course <Text style={styles.requiredStar}>*</Text>
                  </Text>
                  {isCustomCourse && (
                    <TouchableOpacity
                      onPress={() => {
                        setIsCustomCourse(false);
                        setCourseModalVisible(true);
                      }}
                    >
                      <Text style={styles.pickFromListText}>Pick from SNSU list</Text>
                    </TouchableOpacity>
                  )}
                </View>

                {isCustomCourse ? (
                  <View style={[styles.inputContainer, focusedField === 'customCourse' && styles.inputContainerFocused]}>
                    <Ionicons name="school-outline" size={18} color={focusedField === 'customCourse' ? Colors.primaryLight : '#9CA3AF'} style={styles.inputIcon} />
                    <TextInput
                      style={styles.textInput}
                      placeholder="Type your official course/program..."
                      placeholderTextColor="#9CA3AF"
                      value={course}
                      onChangeText={setCourse}
                      autoFocus={true}
                      onFocus={() => setFocusedField('customCourse')}
                      onBlur={() => setFocusedField(null)}
                    />
                  </View>
                ) : (
                  <TouchableOpacity
                    style={[
                      styles.inputContainer,
                      styles.dropdownTrigger,
                      !course && styles.dropdownTriggerEmpty,
                    ]}
                    onPress={() => {
                      setCourseSearchQuery('');
                      setCourseModalVisible(true);
                    }}
                    activeOpacity={0.8}
                  >
                    <View style={styles.dropdownLeft}>
                      <View style={[styles.dropdownIconCircle, course ? styles.dropdownIconCircleActive : null]}>
                        <Ionicons
                          name="school-outline"
                          size={17}
                          color={course ? Colors.primaryLight : '#9CA3AF'}
                        />
                      </View>
                      <View style={{ flex: 1 }}>
                        {course ? (
                          <View>
                            <Text style={styles.dropdownSelectedText} numberOfLines={1}>
                              {course}
                            </Text>
                            <Text style={styles.dropdownSelectedCode}>
                              {SNSU_OFFICIAL_COURSES.find((c) => c.name === course)?.college || 'Selected Program'}
                            </Text>
                          </View>
                        ) : (
                          <Text style={styles.dropdownPlaceholderText}>
                            Select your program
                          </Text>
                        )}
                      </View>
                    </View>
                    <View style={styles.dropdownChevronBox}>
                      <Ionicons name="chevron-down" size={16} color="#6B7280" />
                    </View>
                  </TouchableOpacity>
                )}
                <Text style={styles.hint}>Select your Program</Text>
              </View>

              {/* 4. College Department Dropdown */}
              <View style={styles.field}>
                <Text style={styles.label}>
                  Organization <Text style={styles.requiredStar}>*</Text>
                </Text>
                {organizationsLoading ? (
                  <View style={[styles.inputContainer, styles.loadingInput]}>
                    <ActivityIndicator size="small" color={Colors.primaryLight} />
                    <Text style={styles.loadingDropdownText}>Loading departments...</Text>
                  </View>
                ) : (
                  <TouchableOpacity
                    style={[
                      styles.inputContainer,
                      styles.dropdownTrigger,
                      !organizationId && styles.dropdownTriggerEmpty,
                    ]}
                    onPress={() => {
                      setDeptSearchQuery('');
                      setDeptModalVisible(true);
                    }}
                    activeOpacity={0.8}
                  >
                    <View style={styles.dropdownLeft}>
                      <View style={[styles.dropdownIconCircle, organizationId ? styles.dropdownIconCircleActive : null]}>
                        <Ionicons
                          name="business-outline"
                          size={17}
                          color={organizationId ? Colors.primaryLight : '#9CA3AF'}
                        />
                      </View>
                      <View style={{ flex: 1 }}>
                        {selectedOrg ? (
                          <View>
                            <Text style={styles.dropdownSelectedText} numberOfLines={1}>
                              {selectedOrg.name}
                            </Text>
                            <Text style={styles.dropdownSelectedCode}>
                              {selectedOrg.code}
                            </Text>
                          </View>
                        ) : (
                          <Text style={styles.dropdownPlaceholderText}>
                            Select your organization
                          </Text>
                        )}
                      </View>
                    </View>
                    <View style={styles.dropdownChevronBox}>
                      <Ionicons name="chevron-down" size={16} color="#6B7280" />
                    </View>
                  </TouchableOpacity>
                )}
                <Text style={styles.hint}>Select your Organization</Text>
              </View>

              {/* 5. Year Level Dropdown */}
              <View style={styles.field}>
                <Text style={styles.label}>
                  Year Level <Text style={styles.requiredStar}>*</Text>
                </Text>
                <TouchableOpacity
                  style={[styles.inputContainer, styles.dropdownTrigger]}
                  onPress={() => setYearModalVisible(true)}
                  activeOpacity={0.8}
                >
                  <View style={styles.dropdownLeft}>
                    <View style={[styles.dropdownIconCircle, styles.dropdownIconCircleActive]}>
                      <Ionicons name="calendar-outline" size={17} color={Colors.primaryLight} />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.dropdownSelectedText}>
                        {yearLevel}
                      </Text>
                      <Text style={styles.dropdownSelectedCode}>
                        {YEAR_OPTIONS.find((y) => y.level === yearLevel)?.description || 'Academic Year'}
                      </Text>
                    </View>
                  </View>
                  <View style={styles.dropdownChevronBox}>
                    <Ionicons name="chevron-down" size={16} color="#6B7280" />
                  </View>
                </TouchableOpacity>
                <Text style={styles.hint}>Select Year Level</Text>
              </View>


              {/* Primary Action Button */}
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
                    <View style={styles.primaryBtnIconCircle}>
                      <Ionicons name="arrow-forward" size={16} color={Colors.primaryLight} />
                    </View>
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

      {/* Degree Program / Course Dropdown Modal */}
      <Modal
        visible={courseModalVisible}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setCourseModalVisible(false)}
      >
        <KeyboardAvoidingView
          style={styles.modalOverlay}
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        >
          <View style={styles.modalBackdrop} onTouchEnd={() => setCourseModalVisible(false)} />
          <View style={styles.modalSheet}>
            {/* Sheet Handle */}
            <View style={styles.modalHandle} />

            {/* Header */}
            <View style={styles.modalHeader}>
              <View>
                <Text style={styles.modalTitle}>Select Degree Program</Text>
                <Text style={styles.modalSubtitle}>
                  Choose your official SNSU offered program / course
                </Text>
              </View>
              <TouchableOpacity
                style={styles.modalCloseBtn}
                onPress={() => setCourseModalVisible(false)}
              >
                <Ionicons name="close" size={20} color="#4B5563" />
              </TouchableOpacity>
            </View>

            {/* Instant Search Bar */}
            <View style={styles.modalSearchContainer}>
              <Ionicons name="search" size={18} color="#9CA3AF" style={{ marginRight: 8 }} />
              <TextInput
                style={styles.modalSearchInput}
                placeholder="Search course by name, acronym (e.g. BSCE, BSIT)..."
                placeholderTextColor="#9CA3AF"
                value={courseSearchQuery}
                onChangeText={setCourseSearchQuery}
                clearButtonMode="while-editing"
              />
              {courseSearchQuery.length > 0 && (
                <TouchableOpacity onPress={() => setCourseSearchQuery('')}>
                  <Ionicons name="close-circle" size={16} color="#9CA3AF" />
                </TouchableOpacity>
              )}
            </View>

            {/* List */}
            <FlatList
              data={filteredCourses}
              keyExtractor={(item) => item.code + item.name}
              showsVerticalScrollIndicator={false}
              contentContainerStyle={{ paddingBottom: 24 }}
              ListFooterComponent={
                <TouchableOpacity
                  style={styles.customCourseOption}
                  onPress={() => {
                    setIsCustomCourse(true);
                    setCourseModalVisible(false);
                  }}
                  activeOpacity={0.7}
                >
                  <View style={styles.customCourseIconCircle}>
                    <Ionicons name="create-outline" size={18} color={Colors.primaryLight} />
                  </View>
                  <View style={{ flex: 1, marginLeft: 12 }}>
                    <Text style={styles.customCourseTitle}>Other / Not Listed</Text>
                    <Text style={styles.customCourseDesc}>Type your degree program manually</Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color="#9CA3AF" />
                </TouchableOpacity>
              }
              renderItem={({ item }) => {
                const isSelected = course === item.name;
                return (
                  <TouchableOpacity
                    style={[
                      styles.deptItemCard,
                      isSelected && styles.deptItemCardSelected,
                    ]}
                    onPress={() => handleSelectCourse(item)}
                    activeOpacity={0.7}
                  >
                    <View style={[styles.deptCodeBadge, isSelected && styles.deptCodeBadgeSelected]}>
                      <Text style={[styles.deptCodeBadgeText, isSelected && styles.deptCodeBadgeTextSelected]}>
                        {item.code}
                      </Text>
                    </View>
                    <View style={{ flex: 1, marginHorizontal: 12 }}>
                      <Text style={[styles.deptItemName, isSelected && styles.deptItemNameSelected]}>
                        {item.name}
                      </Text>
                      <Text style={styles.deptItemSub}>{item.college}</Text>
                    </View>
                    {isSelected ? (
                      <View style={styles.selectedCheckCircle}>
                        <Ionicons name="checkmark" size={14} color="#fff" />
                      </View>
                    ) : (
                      <View style={styles.unselectedRadioCircle} />
                    )}
                  </TouchableOpacity>
                );
              }}
            />
          </View>
        </KeyboardAvoidingView>
      </Modal>

      {/* College Department Dropdown Modal */}
      <Modal
        visible={deptModalVisible}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setDeptModalVisible(false)}
      >
        <KeyboardAvoidingView
          style={styles.modalOverlay}
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        >
          <View style={styles.modalBackdrop} onTouchEnd={() => setDeptModalVisible(false)} />
          <View style={styles.modalSheet}>
            {/* Sheet Handle */}
            <View style={styles.modalHandle} />

            {/* Header */}
            <View style={styles.modalHeader}>
              <View>
                <Text style={styles.modalTitle}>Select College Department</Text>
                <Text style={styles.modalSubtitle}>
                  Choose the department you are officially enrolled in
                </Text>
              </View>
              <TouchableOpacity
                style={styles.modalCloseBtn}
                onPress={() => setDeptModalVisible(false)}
              >
                <Ionicons name="close" size={20} color="#4B5563" />
              </TouchableOpacity>
            </View>

            {/* Instant Search Bar */}
            <View style={styles.modalSearchContainer}>
              <Ionicons name="search" size={18} color="#9CA3AF" style={{ marginRight: 8 }} />
              <TextInput
                style={styles.modalSearchInput}
                placeholder="Search department by name or code..."
                placeholderTextColor="#9CA3AF"
                value={deptSearchQuery}
                onChangeText={setDeptSearchQuery}
                clearButtonMode="while-editing"
              />
              {deptSearchQuery.length > 0 && (
                <TouchableOpacity onPress={() => setDeptSearchQuery('')}>
                  <Ionicons name="close-circle" size={16} color="#9CA3AF" />
                </TouchableOpacity>
              )}
            </View>

            {/* List */}
            {filteredOrganizations.length > 0 ? (
              <FlatList
                data={filteredOrganizations}
                keyExtractor={(item) => String(item.id)}
                showsVerticalScrollIndicator={false}
                contentContainerStyle={{ paddingBottom: 20 }}
                renderItem={({ item }) => {
                  const isSelected = organizationId === item.id;
                  return (
                    <TouchableOpacity
                      style={[
                        styles.deptItemCard,
                        isSelected && styles.deptItemCardSelected,
                      ]}
                      onPress={() => {
                        setOrganizationId(item.id);
                        setDeptModalVisible(false);
                      }}
                      activeOpacity={0.7}
                    >
                      <View style={[styles.deptCodeBadge, isSelected && styles.deptCodeBadgeSelected]}>
                        <Text style={[styles.deptCodeBadgeText, isSelected && styles.deptCodeBadgeTextSelected]}>
                          {item.code}
                        </Text>
                      </View>
                      <View style={{ flex: 1, marginHorizontal: 12 }}>
                        <Text style={[styles.deptItemName, isSelected && styles.deptItemNameSelected]}>
                          {item.name}
                        </Text>
                      </View>
                      {isSelected ? (
                        <View style={styles.selectedCheckCircle}>
                          <Ionicons name="checkmark" size={14} color="#fff" />
                        </View>
                      ) : (
                        <View style={styles.unselectedRadioCircle} />
                      )}
                    </TouchableOpacity>
                  );
                }}
              />
            ) : (
              <View style={styles.emptyListContainer}>
                <Ionicons name="search-outline" size={36} color="#9CA3AF" />
                <Text style={styles.emptyListText}>No departments match "{deptSearchQuery}"</Text>
              </View>
            )}
          </View>
        </KeyboardAvoidingView>
      </Modal>

      {/* Year Level Dropdown Modal */}
      <Modal
        visible={yearModalVisible}
        transparent={true}
        animationType="fade"
        onRequestClose={() => setYearModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalBackdrop} onTouchEnd={() => setYearModalVisible(false)} />
          <View style={[styles.modalSheet, { maxHeight: '60%' }]}>
            <View style={styles.modalHandle} />
            <View style={styles.modalHeader}>
              <View>
                <Text style={styles.modalTitle}>Select Year Level</Text>
                <Text style={styles.modalSubtitle}>
                  Used to determine your Year Representative ballot candidates
                </Text>
              </View>
              <TouchableOpacity
                style={styles.modalCloseBtn}
                onPress={() => setYearModalVisible(false)}
              >
                <Ionicons name="close" size={20} color="#4B5563" />
              </TouchableOpacity>
            </View>

            <ScrollView contentContainerStyle={{ paddingBottom: 20 }}>
              {YEAR_OPTIONS.map((item) => {
                const isSelected = yearLevel === item.level;
                return (
                  <TouchableOpacity
                    key={item.level}
                    style={[
                      styles.deptItemCard,
                      isSelected && styles.deptItemCardSelected,
                    ]}
                    onPress={() => {
                      setYearLevel(item.level);
                      setYearModalVisible(false);
                    }}
                    activeOpacity={0.7}
                  >
                    <View style={[styles.deptCodeBadge, isSelected && styles.deptCodeBadgeSelected]}>
                      <Ionicons
                        name="calendar-outline"
                        size={14}
                        color={isSelected ? '#fff' : Colors.primaryLight}
                      />
                    </View>
                    <View style={{ flex: 1, marginHorizontal: 12 }}>
                      <Text style={[styles.deptItemName, isSelected && styles.deptItemNameSelected]}>
                        {item.level}
                      </Text>
                      <Text style={styles.deptItemSub}>{item.description}</Text>
                    </View>
                    {isSelected ? (
                      <View style={styles.selectedCheckCircle}>
                        <Ionicons name="checkmark" size={14} color="#fff" />
                      </View>
                    ) : (
                      <View style={styles.unselectedRadioCircle} />
                    )}
                  </TouchableOpacity>
                );
              })}
            </ScrollView>
          </View>
        </View>
      </Modal>

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
    paddingVertical: 32,
    paddingHorizontal: 20,
  },
  logoArea: {
    alignItems: 'center',
    marginBottom: 18,
  },
  logoImage: {
    width: 84,
    height: 84,
    borderRadius: 16,
    marginBottom: 8,
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
    maxWidth: 440,
    backgroundColor: '#FFFFFF',
    borderRadius: 24,
    padding: 22,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 12 },
    shadowOpacity: 0.15,
    shadowRadius: 24,
    elevation: 8,
  },

  /* Visual Step Progress */
  stepProgressBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
    paddingHorizontal: 12,
  },
  stepItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  stepNumberBadge: {
    width: 24,
    height: 24,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
  },
  stepBadgeActive: {
    backgroundColor: Colors.primaryLight,
  },
  stepBadgeInactive: {
    backgroundColor: '#E5E7EB',
  },
  stepNumberTextActive: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '800',
  },
  stepNumberTextInactive: {
    color: '#6B7280',
    fontSize: 12,
    fontWeight: '700',
  },
  stepLabel: {
    fontSize: 12.5,
    fontWeight: '600',
    color: '#9CA3AF',
  },
  stepLabelActive: {
    color: '#111827',
    fontWeight: '700',
  },
  stepLine: {
    flex: 1,
    height: 2,
    backgroundColor: '#E5E7EB',
    marginHorizontal: 12,
  },
  stepLineActive: {
    backgroundColor: Colors.primaryLight,
  },

  title: {
    fontSize: 21,
    fontWeight: '800',
    color: '#111827',
    textAlign: 'center',
    marginBottom: 4,
  },
  subtitle: {
    fontSize: 13,
    color: '#6B7280',
    textAlign: 'center',
    lineHeight: 18,
    marginBottom: 14,
  },

  /* Connected Google Pill */
  accountPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#E5E7EB',
    borderRadius: 14,
    paddingVertical: 10,
    paddingHorizontal: 12,
    marginBottom: 14,
  },
  googleIconCircle: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#E5E7EB',
    justifyContent: 'center',
    alignItems: 'center',
  },
  accountLabel: {
    fontSize: 10.5,
    color: '#6B7280',
    fontWeight: '500',
    textTransform: 'uppercase',
    letterSpacing: 0.3,
  },
  accountEmail: {
    fontSize: 12.5,
    color: '#111827',
    fontWeight: '700',
    marginTop: 1,
  },
  verifiedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#ECFDF5',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  verifiedBadgeText: {
    fontSize: 11,
    color: '#059669',
    fontWeight: '700',
  },

  /* Notice Box */
  noticeBox: {
    backgroundColor: '#FFFBEB',
    borderWidth: 1,
    borderColor: '#FDE68A',
    borderRadius: 12,
    padding: 12,
    marginBottom: 14,
  },
  noticeHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 3,
  },
  noticeTitle: {
    fontSize: 12,
    fontWeight: '700',
    color: '#92400E',
    letterSpacing: 0.2,
  },
  noticeText: {
    fontSize: 11.5,
    color: '#78350F',
    lineHeight: 16.5,
  },

  /* Section Header */
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 10,
    marginTop: 2,
  },
  sectionTitle: {
    fontSize: 13.5,
    fontWeight: '700',
    color: '#374151',
    letterSpacing: 0.2,
  },

  /* Form Fields */
  field: {
    marginBottom: 13,
  },
  labelRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
  },
  label: {
    fontSize: 13,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 6,
  },
  requiredStar: {
    color: '#EF4444',
    fontWeight: '700',
  },
  fromGoogleBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#ECFDF5',
    paddingHorizontal: 8,
    paddingVertical: 2.5,
    borderRadius: 6,
  },
  fromGoogleText: {
    fontSize: 10.5,
    color: '#059669',
    fontWeight: '700',
  },
  pickFromListText: {
    fontSize: 11.5,
    color: Colors.primaryLight,
    fontWeight: '700',
  },

  /* Modern Input Container */
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 48,
    borderWidth: 1.5,
    borderColor: '#E5E7EB',
    borderRadius: 12,
    paddingHorizontal: 12,
    backgroundColor: '#F9FAFB',
  },
  inputContainerFocused: {
    borderColor: Colors.primaryLight,
    backgroundColor: '#FFFFFF',
    shadowColor: Colors.primaryLight,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 2,
  },
  inputContainerDisabled: {
    backgroundColor: '#F3F4F6',
    borderColor: '#E5E7EB',
  },
  inputIcon: {
    marginRight: 10,
  },
  textInput: {
    flex: 1,
    fontSize: 13.5,
    color: '#111827',
    fontWeight: '500',
  },
  textInputDisabled: {
    color: '#4B5563',
    fontWeight: '600',
  },
  hint: {
    fontSize: 11,
    color: '#6B7280',
    marginTop: 4,
    marginLeft: 2,
  },

  /* Dropdown Trigger */
  dropdownTrigger: {
    justifyContent: 'space-between',
  },
  dropdownTriggerEmpty: {
    backgroundColor: '#F9FAFB',
  },
  dropdownLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
    marginRight: 8,
  },
  dropdownIconCircle: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#F3F4F6',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 10,
  },
  dropdownIconCircleActive: {
    backgroundColor: '#E8F5E9',
  },
  dropdownSelectedText: {
    fontSize: 13,
    fontWeight: '700',
    color: '#111827',
  },
  dropdownSelectedCode: {
    fontSize: 11,
    fontWeight: '600',
    color: Colors.primaryLight,
    marginTop: 1,
  },
  dropdownPlaceholderText: {
    fontSize: 13,
    color: '#9CA3AF',
  },
  dropdownChevronBox: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#F3F4F6',
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingInput: {
    justifyContent: 'center',
    gap: 8,
  },
  loadingDropdownText: {
    fontSize: 12.5,
    color: '#6B7280',
  },

  /* Optional Collapsible */
  collapsibleHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 10,
    paddingHorizontal: 12,
    backgroundColor: '#F9FAFB',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    marginTop: 4,
    marginBottom: 8,
  },
  collapsibleIconCircle: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#E5E7EB',
    justifyContent: 'center',
    alignItems: 'center',
  },
  collapsibleTitle: {
    fontSize: 12.5,
    fontWeight: '600',
    color: '#374151',
  },
  optionalBox: {
    backgroundColor: '#FAFBFB',
    borderWidth: 1,
    borderColor: '#E5E7EB',
    borderRadius: 12,
    padding: 12,
    marginBottom: 10,
  },
  rowTwoCols: {
    flexDirection: 'row',
    gap: 10,
    alignItems: 'flex-start',
  },
  sexSelectorContainer: {
    flexDirection: 'row',
    height: 48,
    backgroundColor: '#F3F4F6',
    borderRadius: 12,
    padding: 3,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  sexOptionBtn: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: 9,
  },
  sexOptionBtnActive: {
    backgroundColor: '#FFFFFF',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.12,
    shadowRadius: 2,
    elevation: 2,
  },
  sexOptionText: {
    fontSize: 11.5,
    color: '#6B7280',
    fontWeight: '600',
  },
  sexOptionTextActive: {
    color: Colors.primaryLight,
    fontWeight: '800',
  },

  /* Primary Button */
  primaryBtn: {
    height: 50,
    backgroundColor: Colors.primaryLight,
    borderRadius: 25,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 10,
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
    fontSize: 14.5,
    fontWeight: '800',
    letterSpacing: 0.3,
    marginRight: 8,
  },
  primaryBtnIconCircle: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#fff',
    justifyContent: 'center',
    alignItems: 'center',
  },
  logoutRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 14,
    gap: 6,
    paddingVertical: 6,
  },
  logoutText: {
    color: '#6B7280',
    fontSize: 12.5,
    fontWeight: '500',
  },

  /* Face Step Container */
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

  /* Modal Sheets */
  modalOverlay: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  modalBackdrop: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0, 0, 0, 0.45)',
  },
  modalSheet: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: Platform.OS === 'ios' ? 36 : 20,
    maxHeight: '75%',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.1,
    shadowRadius: 16,
    elevation: 16,
  },
  modalHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#D1D5DB',
    alignSelf: 'center',
    marginBottom: 14,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 14,
  },
  modalTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: '#111827',
  },
  modalSubtitle: {
    fontSize: 12,
    color: '#6B7280',
    marginTop: 2,
  },
  modalCloseBtn: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#F3F4F6',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalSearchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 44,
    backgroundColor: '#F3F4F6',
    borderRadius: 12,
    paddingHorizontal: 12,
    marginBottom: 14,
  },
  modalSearchInput: {
    flex: 1,
    fontSize: 13,
    color: '#111827',
  },
  deptItemCard: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    paddingHorizontal: 14,
    borderRadius: 14,
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#E5E7EB',
    marginBottom: 9,
  },
  deptItemCardSelected: {
    backgroundColor: '#ECFDF5',
    borderColor: Colors.primaryLight,
  },
  deptCodeBadge: {
    backgroundColor: '#E5E7EB',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
    minWidth: 44,
  },
  deptCodeBadgeSelected: {
    backgroundColor: Colors.primaryLight,
  },
  deptCodeBadgeText: {
    fontSize: 11,
    fontWeight: '800',
    color: '#374151',
  },
  deptCodeBadgeTextSelected: {
    color: '#FFFFFF',
  },
  deptItemName: {
    fontSize: 13,
    fontWeight: '600',
    color: '#1F2937',
  },
  deptItemNameSelected: {
    color: '#065F46',
    fontWeight: '700',
  },
  deptItemSub: {
    fontSize: 11,
    color: '#6B7280',
    marginTop: 2,
  },
  selectedCheckCircle: {
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: Colors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
  },
  unselectedRadioCircle: {
    width: 18,
    height: 18,
    borderRadius: 9,
    borderWidth: 1.5,
    borderColor: '#D1D5DB',
  },
  customCourseOption: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 14,
    paddingHorizontal: 14,
    borderRadius: 14,
    backgroundColor: '#F3F4F6',
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: '#9CA3AF',
    marginTop: 6,
  },
  customCourseIconCircle: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#E8F5E9',
    justifyContent: 'center',
    alignItems: 'center',
  },
  customCourseTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: Colors.primaryLight,
  },
  customCourseDesc: {
    fontSize: 11,
    color: '#6B7280',
    marginTop: 1,
  },
  emptyListContainer: {
    alignItems: 'center',
    paddingVertical: 32,
    gap: 8,
  },
  emptyListText: {
    fontSize: 13,
    color: '#6B7280',
  },
});
