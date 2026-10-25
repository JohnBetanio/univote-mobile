import { Stack, useRouter, useSegments } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { AuthProvider, useAuth } from '@/context/AuthContext';
import { useEffect } from 'react';
import { View, ActivityIndicator } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { NavigationBar } from 'expo-navigation-bar';
import { Colors } from '@/constants/Colors';

function RootLayoutNav() {
  const { user, loading } = useAuth();
  const router = useRouter();
  const segments = useSegments();

  useEffect(() => {
    if (loading) return;

    const inAuthGroup = segments[0] === '(auth)';
    const inTabsGroup = segments[0] === '(tabs)';

    if (!user) {
      // Not logged in → send to voter login
      if (!inAuthGroup) {
        router.replace('/(auth)/VoterLogin');
      }
    } else {
      // If student needs setup (password, profile info, face scan), enforce GoogleSetup
      if (user.role === 'student' && user.needs_setup) {
        if ((segments[1] as string) !== 'GoogleSetup') {
          router.replace('/(auth)/GoogleSetup' as any);
        }
      } else {
        // The mobile app currently provides the voter dashboard for all roles.
        if (!inTabsGroup) {
          router.replace('/(tabs)/(voter)/VoterDashboard');
        }
      }
    }
  }, [user, loading, router, segments]);

  if (loading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: Colors.background }}>
        <ActivityIndicator size="large" color={Colors.primaryLight} />
      </View>
    );
  }

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="(auth)" />
      <Stack.Screen name="(tabs)" />
      <Stack.Screen name="+not-found" />
    </Stack>
  );
}

export default function RootLayout() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <StatusBar style="light" />
        <NavigationBar style="dark" />
        <RootLayoutNav />
      </AuthProvider>
    </SafeAreaProvider>
  );
}

