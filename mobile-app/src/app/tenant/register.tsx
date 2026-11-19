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
import API, { setToken } from '../../services/api';
import { notify } from '../../services/alert';
import { C, S } from '../../theme';

export default function RegisterScreen() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [focus, setFocus] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleRegister = async () => {
    if (!name || !email || !password) {
      notify('Error', 'Name, email and password are required.');
      return;
    }

    if (password.length < 6) {
      notify('Error', 'Password must be at least 6 characters.');
      return;
    }

    if (password !== confirm) {
      notify('Error', 'The two passwords do not match.');
      return;
    }

    setLoading(true);
    try {
      const response = await API.post('/register', {
        name,
        email,
        phone: phone || null,
        password,
      });

      setToken(response.data.access_token);

      // If the admin already recorded this email as a tenant, the backend
      // links the new login to that record instead of creating a duplicate.
      const linked = response.data.user?.tenant?.room
        ? ` You are already assigned to Room ${response.data.user.tenant.room.room_number}.`
        : ' Ask the admin to assign your room.';

      notify('Account created', `Welcome, ${name}!${linked}`, [
        { text: 'OK', onPress: () => router.replace('/tenant/dashboard') },
      ]);
    } catch (error: any) {
      const errors = error?.response?.data?.errors;
      const firstFieldError = errors && Object.values(errors)[0];
      const message =
        (Array.isArray(firstFieldError) ? firstFieldError[0] : firstFieldError) ||
        error?.response?.data?.message ||
        `Could not reach ${API.defaults.baseURL}. Check that the phone and computer are on the same Wi-Fi, and that the LAN API proxy is running.`;
      notify('Registration Failed', String(message));
    } finally {
      setLoading(false);
    }
  };

  const field = (id: string) => [S.field, focus === id && S.fieldFocused];

  return (
    <SafeAreaView style={S.screen} edges={['top', 'left', 'right', 'bottom']}>
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <View style={styles.card}>
            <Text style={styles.kicker}>Tenant Sign Up</Text>
            <Text style={styles.title}>Create Account</Text>

            <Text style={S.fieldLabel}>Full Name</Text>
            <TextInput
              style={field('name')}
              placeholder="Full Name"
              placeholderTextColor="#9aa5b4"
              value={name}
              onChangeText={setName}
              textContentType="name"
              onFocus={() => setFocus('name')}
              onBlur={() => setFocus(null)}
            />

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

            <Text style={S.fieldLabel}>Contact Number (optional)</Text>
            <TextInput
              style={field('phone')}
              placeholder="09xx xxx xxxx"
              placeholderTextColor="#9aa5b4"
              value={phone}
              onChangeText={setPhone}
              keyboardType="phone-pad"
              onFocus={() => setFocus('phone')}
              onBlur={() => setFocus(null)}
            />

            <Text style={S.fieldLabel}>Password</Text>
            <TextInput
              style={field('password')}
              placeholder="At least 6 characters"
              placeholderTextColor="#9aa5b4"
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              onFocus={() => setFocus('password')}
              onBlur={() => setFocus(null)}
            />

            <Text style={S.fieldLabel}>Confirm Password</Text>
            <TextInput
              style={field('confirm')}
              placeholder="Type it again"
              placeholderTextColor="#9aa5b4"
              value={confirm}
              onChangeText={setConfirm}
              secureTextEntry
              onFocus={() => setFocus('confirm')}
              onBlur={() => setFocus(null)}
            />

            <TouchableOpacity
              style={[S.primary, styles.submit, loading && S.primaryDisabled]}
              onPress={handleRegister}
              disabled={loading}
              activeOpacity={0.85}
            >
              {loading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={S.primaryText}>Create Account</Text>
              )}
            </TouchableOpacity>

            <TouchableOpacity style={S.link} onPress={() => router.replace('/tenant/login')} disabled={loading}>
              <Text style={S.linkText}>Already have an account? Sign in</Text>
            </TouchableOpacity>
          </View>
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
    paddingTop: 20,
  },
  kicker: {
    fontSize: 10.5,
    fontWeight: '800',
    letterSpacing: 1.1,
    textTransform: 'uppercase',
    color: C.brand2,
    marginBottom: 6,
    textAlign: 'center',
  },
  title: { fontSize: 21, fontWeight: '800', color: C.ink, letterSpacing: -0.5, textAlign: 'center' },
  lede: { fontSize: 13, color: C.muted, textAlign: 'center', marginTop: 6, lineHeight: 19 },
  submit: { marginTop: 22 },
});
