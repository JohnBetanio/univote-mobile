import React, { useState, useRef, useEffect } from 'react';
import {
  View, Text, TouchableOpacity, StyleSheet, Image,
  Alert, KeyboardAvoidingView, Platform, ScrollView, ActivityIndicator,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import * as Linking from 'expo-linking';
import * as Crypto from 'expo-crypto';
import Constants, { ExecutionEnvironment } from 'expo-constants';
import { makeRedirectUri } from 'expo-auth-session';
import { useAuth } from '@/context/AuthContext';
import { Colors } from '@/constants/Colors';
import { API_BASE_URL } from '@/services/api';

WebBrowser.maybeCompleteAuthSession();

export default function VoterLoginScreen() {
  const { loginWithGoogle } = useAuth();
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const mountedRef = useRef(true);
  const isLoggingInWithGoogle = useRef(false);
  const processedTokens = useRef<Set<string>>(new Set());

  const handleGoogleLogin = async (idToken: string) => {
    if (!idToken || processedTokens.current.has(idToken) || isLoggingInWithGoogle.current) {
      return;
    }
    processedTokens.current.add(idToken);
    isLoggingInWithGoogle.current = true;
    safeSetLoading(true);
    try {
      const result = await loginWithGoogle({ idToken });
      if (!result.success && mountedRef.current) {
        processedTokens.current.delete(idToken);
        Alert.alert('Access Restricted', result.error || 'Google Sign-In failed');
      }
    } catch (err: any) {
      processedTokens.current.delete(idToken);
      if (mountedRef.current) {
        Alert.alert('Google Sign-In Error', err?.message || 'Failed to authenticate with Google');
      }
    } finally {
      isLoggingInWithGoogle.current = false;
      safeSetLoading(false);
    }
  };

  useEffect(() => {
    mountedRef.current = true;

    const subscription = Linking.addEventListener('url', async (event) => {
      if (event?.url) {
        const match = event.url.match(/[#?&]id_token=([^&]+)/);
        if (match && match[1]) {
          const idToken = decodeURIComponent(match[1]);
          await handleGoogleLogin(idToken);
        }
      }
    });

    Linking.getInitialURL().then((url) => {
      if (url) {
        const match = url.match(/[#?&]id_token=([^&]+)/);
        if (match && match[1]) {
          const idToken = decodeURIComponent(match[1]);
          handleGoogleLogin(idToken);
        }
      }
    });

    return () => {
      mountedRef.current = false;
      subscription.remove();
    };
  }, []);

  const handleGoogleButtonPress = async () => {
    const clientId = process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID;
    if (!clientId) {
      Alert.alert(
        'Google Configuration Missing',
        'Please add EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID to your univote-mobile/.env file and restart Expo.'
      );
      return;
    }

    const nonce = Crypto.randomUUID ? Crypto.randomUUID() : Math.random().toString(36).substring(2);

    try {
      safeSetLoading(true);

      let authUrl: string;
      let returnUrl: string;

      if (Platform.OS === 'web') {
        returnUrl = typeof window !== 'undefined' && window.location ? window.location.origin : makeRedirectUri();
        authUrl = `https://accounts.google.com/o/oauth2/v2/auth?` +
          `client_id=${encodeURIComponent(clientId)}` +
          `&redirect_uri=${encodeURIComponent(returnUrl)}` +
          `&response_type=id_token` +
          `&scope=${encodeURIComponent('openid email profile')}` +
          `&nonce=${encodeURIComponent(nonce)}`;
      } else {
        const isExpoGo =
          Constants.appOwnership === 'expo' ||
          Constants.executionEnvironment === ExecutionEnvironment.StoreClient;

        if (isExpoGo) {
          // Development inside Expo Go app: uses registered Expo auth proxy
          returnUrl = makeRedirectUri({ scheme: 'univote', path: 'auth/google' });
          const proxyBase = 'https://auth.expo.io/@johnbetanio/univote';
          const googleAuthUrl = `https://accounts.google.com/o/oauth2/v2/auth?` +
            `client_id=${encodeURIComponent(clientId)}` +
            `&redirect_uri=${encodeURIComponent(proxyBase)}` +
            `&response_type=id_token` +
            `&scope=${encodeURIComponent('openid email profile')}` +
            `&nonce=${encodeURIComponent(nonce)}`;

          authUrl = `${proxyBase}/start?authUrl=${encodeURIComponent(googleAuthUrl)}&returnUrl=${encodeURIComponent(returnUrl)}`;
        } else {
          // Standalone production / preview APK:
          // Google OAuth only accepts HTTPS redirect URIs, so Google redirects to our
          // verified backend callback, which immediately deep-links into univote://auth/google
          returnUrl = 'univote://auth/google';
          const backendCallback = `${API_BASE_URL.replace(/\/api\/?$/, '')}/voter/auth/google/callback`;
          authUrl = `https://accounts.google.com/o/oauth2/v2/auth?` +
            `client_id=${encodeURIComponent(clientId)}` +
            `&redirect_uri=${encodeURIComponent(backendCallback)}` +
            `&response_type=id_token` +
            `&scope=${encodeURIComponent('openid email profile')}` +
            `&nonce=${encodeURIComponent(nonce)}`;
        }
      }

      const result = await WebBrowser.openAuthSessionAsync(authUrl, returnUrl);

      if (result.type === 'success' && result.url) {
        const match = result.url.match(/[#?&]id_token=([^&]+)/);
        if (match && match[1]) {
          const idToken = decodeURIComponent(match[1]);
          await handleGoogleLogin(idToken);
        } else {
          Alert.alert('Google Sign-In Error', 'Could not retrieve ID token from Google response.');
        }
      }
    } catch (err: any) {
      Alert.alert('Google Sign-In Error', err?.message || 'Failed to complete Google authentication.');
    } finally {
      safeSetLoading(false);
    }
  };

  const safeSetLoading = (value: boolean) => {
    if (mountedRef.current) setLoading(value);
  };

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <View style={styles.logoArea}>
          <Image source={require('../../assets/univote-logo.jpg')} style={styles.logoImage} />
          <Text style={styles.appName}>Univote</Text>
          <Text style={styles.appTagline}>SNSU Online Voting System</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.title}>Student Login</Text>

          <View style={styles.noticeBox}>
            <Ionicons name="information-circle" size={20} color={Colors.primaryLight} style={{ marginRight: 8, marginTop: 1 }} />
            <Text style={styles.noticeText}>
              All student voters must sign in using their official university Google account (@ssct.edu.ph or @snsu.edu.ph).
            </Text>
          </View>

          <TouchableOpacity
            style={[styles.googleSignInBtn, loading && styles.btnDisabled]}
            activeOpacity={0.85}
            disabled={loading}
            onPress={handleGoogleButtonPress}
          >
            {loading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <>
                <View style={styles.googleIconBg}>
                  <Ionicons name="logo-google" size={18} color="#4285F4" />
                </View>
                <Text style={styles.googleSignInText}>Continue with Google</Text>
              </>
            )}
          </TouchableOpacity>

          <View style={styles.securityPill}>
            <Ionicons name="shield-checkmark" size={14} color={Colors.success} />
            <Text style={styles.securityPillText}>Secure Student Authentication</Text>
          </View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.primary },
  scroll: { flexGrow: 1, justifyContent: 'center', alignItems: 'center', paddingVertical: 48, paddingHorizontal: 20 },
  logoArea: { alignItems: 'center', marginBottom: 28 },
  logoImage: { width: 112, height: 112, borderRadius: 12, marginBottom: 12 },
  appName: { color: '#fff', fontSize: 26, fontWeight: '900', letterSpacing: 0.5 },
  appTagline: { color: 'rgba(255,255,255,0.65)', fontSize: 13, marginTop: 3 },
  card: {
    width: '100%', maxWidth: 390, backgroundColor: Colors.white,
    borderRadius: 24, padding: 28,
    shadowColor: '#000', shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.25, shadowRadius: 20, elevation: 14,
  },
  title: { fontSize: 22, fontWeight: '800', color: Colors.text, marginBottom: 16, textAlign: 'center' },
  noticeBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: '#eff6ff',
    borderWidth: 1,
    borderColor: '#bfdbfe',
    borderRadius: 12,
    padding: 14,
    marginBottom: 24,
  },
  noticeText: {
    flex: 1,
    fontSize: 13,
    lineHeight: 19,
    color: '#1e40af',
    fontWeight: '500',
  },
  googleSignInBtn: {
    height: 54,
    backgroundColor: '#1e293b',
    borderRadius: 27,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 5,
  },
  googleIconBg: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#fff',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  googleSignInText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
    letterSpacing: 0.3,
  },
  securityPill: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 24,
    gap: 6,
  },
  securityPillText: {
    fontSize: 12,
    fontWeight: '600',
    color: Colors.textMuted,
  },
  btnDisabled: { opacity: 0.65 },
});
