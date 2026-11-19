import React, { useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import API, { setToken, clearToken } from '../../services/api';
import { notify } from '../../services/alert';
import { C, S } from '../../theme';

export default function LoginScreen() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [focus, setFocus] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleLogin = async () => {
    if (!email || !password) {
      notify('Error', 'Please fill in all fields');
      return;
    }

    setLoading(true);
    try {
      const response = await API.post('/login', {
        email,
        password,
      });

      const { access_token, user } = response.data;

      // The panel and the phone share one users table, so an admin password is
      // a valid credential here too. auth:sanctum would still let that token
      // through every tenant route, and the boarder screens would render an
      // empty shell, so a staff account is turned away before it gets in.
      if (user?.role && user.role !== 'tenant') {
        clearToken();
        notify(
          'Access Denied',
          `This site is for boarders. That account's role is "${user.role}", which is a staff role, so it is denied here. Please use the admin panel in a web browser instead.`,
        );
        return;
      }

      // Every protected /api call needs this bearer token.
      setToken(access_token);

      // Navigate to Dashboard on successful login
      router.replace('/tenant/dashboard');
    } catch (error: any) {
      clearToken();
      // No response at all means the phone never reached the server, which is
      // a different fix than a wrong password.
      const message = error?.response?.data?.message
        || `Cannot reach ${API.defaults.baseURL}. Check that the phone and computer are on the same Wi-Fi, and that the LAN API proxy is running.`;
      notify('Login Failed', message);
    } finally {
      setLoading(false);
    }
  };

  const field = (name: string) => [S.field, focus === name && S.fieldFocused];

  return (
    <SafeAreaView style={S.screen} edges={['top', 'left', 'right', 'bottom']}>
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <View style={styles.card}>
            <View style={styles.mark}>
              <Text style={styles.markText}>BH</Text>
            </View>
            <Text style={styles.title}>Boarding House</Text>

            <Text style={S.fieldLabel}>Email Address</Text>
            <TextInput
              style={field('email')}
              placeholder="Email"
              placeholderTextColor="#9aa5b4"
              value={email}
              onChangeText={setEmail}
              autoCapitalize="none"
              keyboardType="email-address"
              onFocus={() => setFocus('email')}
              onBlur={() => setFocus(null)}
            />

            <Text style={S.fieldLabel}>Password</Text>
            <TextInput
              style={field('password')}
              placeholder="Your password"
              placeholderTextColor="#9aa5b4"
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              onFocus={() => setFocus('password')}
              onBlur={() => setFocus(null)}
            />

            <TouchableOpacity
              style={[S.primary, loading && S.primaryDisabled]}
              onPress={handleLogin}
              disabled={loading}
              activeOpacity={0.85}
            >
              {loading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={S.primaryText}>Login</Text>
              )}
            </TouchableOpacity>

            <TouchableOpacity style={S.link} onPress={() => router.push('/tenant/register')} disabled={loading}>
              <Text style={S.linkText}>New here? Create an account</Text>
            </TouchableOpacity>
          </View>

          <Text style={styles.footnote}>Tenant accounts sign in here.{'\n'}The admin panel runs in the browser.</Text>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 20,
    paddingBottom: 34,
  },
  card: {
    backgroundColor: C.surface,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: C.line,
    padding: 22,
    paddingTop: 26,
  },
  mark: {
    width: 46,
    height: 46,
    borderRadius: 14,
    backgroundColor: C.brandSoft,
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    marginBottom: 14,
  },
  markText: { color: C.brand, fontWeight: '800', fontSize: 15, letterSpacing: 0.4 },
  title: { fontSize: 21, fontWeight: '800', color: C.ink, letterSpacing: -0.5, textAlign: 'center' },
  lede: { fontSize: 13.5, color: C.muted, textAlign: 'center', marginTop: 5, marginBottom: 6, lineHeight: 19 },
  footnote: { fontSize: 11.5, color: C.faint, textAlign: 'center', marginTop: 20, lineHeight: 17 },
});
