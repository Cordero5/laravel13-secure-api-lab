import { DarkTheme, DefaultTheme, Stack, ThemeProvider } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { useColorScheme } from 'react-native';

import * as animatedIcon from '@/components/animated-icon';

SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  const colorScheme = useColorScheme();
  return (
    <ThemeProvider value={colorScheme === 'dark' ? DarkTheme : DefaultTheme}>
      <animatedIcon.AnimatedSplashOverlay />
      {/* Login -> Register -> Dashboard -> detail screens is a stack, not tabs:
          only the dashboard screens should sit on top of the login screen. */}
      <Stack screenOptions={{ headerShown: false }} />
    </ThemeProvider>
  );
}
