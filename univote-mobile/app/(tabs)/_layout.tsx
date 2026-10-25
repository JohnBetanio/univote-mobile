import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/Colors';

function TabIcon({
    focused,
    activeIcon,
    inactiveIcon,
}: {
    focused: boolean;
    activeIcon: keyof typeof Ionicons.glyphMap;
    inactiveIcon: keyof typeof Ionicons.glyphMap;
}) {
    return (
        <View style={[styles.iconCircle, focused && styles.iconCircleActive]}>
            <Ionicons
                name={focused ? activeIcon : inactiveIcon}
                size={22}
                color={focused ? '#ffffff' : 'rgba(255,255,255,0.72)'}
            />
        </View>
    );
}

function TabLabel({ label, focused }: { label: string; focused: boolean }) {
    return (
        <Text
            numberOfLines={1}
            ellipsizeMode="tail"
            adjustsFontSizeToFit
            style={[styles.tabLabel, focused && styles.tabLabelActive]}
        >
            {label}
        </Text>
    );
}

export default function TabsLayout() {
    const insets = useSafeAreaInsets();
    const bottomPadding = Math.max(insets.bottom, 8);
    const tabHeight = 60 + bottomPadding;

    return (
        <Tabs
            screenOptions={{
                headerShown: false,
                tabBarStyle: {
                    backgroundColor: Colors.primary,
                    borderTopWidth: 0,
                    height: tabHeight,
                    paddingBottom: bottomPadding,
                    paddingTop: 6,
                    elevation: 8,
                    shadowColor: '#000',
                    shadowOffset: { width: 0, height: -2 },
                    shadowOpacity: 0.1,
                    shadowRadius: 4,
                },
                tabBarActiveTintColor: '#ffffff',
                tabBarInactiveTintColor: 'rgba(255,255,255,0.72)',
                tabBarShowLabel: true,
                tabBarItemStyle: {
                    justifyContent: 'center',
                    alignItems: 'center',
                    paddingHorizontal: 0,
                    paddingVertical: 0,
                },
            }}
        >
            {/* Hidden routes */}
            <Tabs.Screen
                name="index"
                options={{
                    tabBarButton: () => null,
                    tabBarItemStyle: { width: 0, flex: 0, padding: 0, margin: 0 },
                }}
            />

            {/* Voter tabs */}
            <Tabs.Screen
                name="(voter)/VoterDashboard"
                options={{
                    title: 'Home',
                    tabBarIcon: ({ focused }) => (
                        <TabIcon
                            focused={focused}
                            activeIcon="home"
                            inactiveIcon="home-outline"
                        />
                    ),
                    tabBarLabel: ({ focused }) => (
                        <TabLabel label="Home" focused={focused} />
                    ),
                }}
            />
            <Tabs.Screen
                name="(voter)/VoterVote"
                options={{
                    title: 'Vote Now',
                    tabBarIcon: ({ focused }) => (
                        <TabIcon
                            focused={focused}
                            activeIcon="grid"
                            inactiveIcon="grid-outline"
                        />
                    ),
                    tabBarLabel: ({ focused }) => (
                        <TabLabel label="Vote Now" focused={focused} />
                    ),
                }}
            />
            <Tabs.Screen
                name="(voter)/VoterVotes"
                options={{
                    title: 'View Votes',
                    tabBarIcon: ({ focused }) => (
                        <TabIcon
                            focused={focused}
                            activeIcon="refresh"
                            inactiveIcon="refresh-outline"
                        />
                    ),
                    tabBarLabel: ({ focused }) => (
                        <TabLabel label="View Votes" focused={focused} />
                    ),
                }}
            />
            <Tabs.Screen
                name="(voter)/VoterResults"
                options={{
                    title: 'Results',
                    tabBarIcon: ({ focused }) => (
                        <TabIcon
                            focused={focused}
                            activeIcon="bar-chart"
                            inactiveIcon="bar-chart-outline"
                        />
                    ),
                    tabBarLabel: ({ focused }) => (
                        <TabLabel label="Results" focused={focused} />
                    ),
                }}
            />
            <Tabs.Screen
                name="(voter)/VoterProfile"
                options={{
                    title: 'Profile',
                    tabBarIcon: ({ focused }) => (
                        <TabIcon
                            focused={focused}
                            activeIcon="person"
                            inactiveIcon="person-outline"
                        />
                    ),
                    tabBarLabel: ({ focused }) => (
                        <TabLabel label="Profile" focused={focused} />
                    ),
                }}
            />
        </Tabs>
    );
}

const styles = StyleSheet.create({
    iconCircle: {
        width: 38,
        height: 38,
        borderRadius: 19,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 2,
    },
    iconCircleActive: {
        backgroundColor: 'rgba(255,255,255,0.22)',
    },
    tabLabel: {
        fontSize: 10,
        fontWeight: '600',
        color: 'rgba(255,255,255,0.72)',
        textAlign: 'center',
        lineHeight: 12,
        paddingHorizontal: 2,
    },
    tabLabelActive: {
        color: '#ffffff',
        fontWeight: '700',
    },
});

