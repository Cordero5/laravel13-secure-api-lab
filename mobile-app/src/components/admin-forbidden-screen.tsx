import React, { useEffect, useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { router } from 'expo-router';

import API from '../services/api';
import { C, S, TONES } from '../theme';
import { Card, Screen } from './bh';

type AccessState =
  | { kind: 'checking' }
  | { kind: 'allowed' }
  | { kind: 'unauthenticated' }
  | { kind: 'forbidden' }
  | { kind: 'error'; message: string };

export default function AdminForbiddenScreen() {
  const [state, setState] = useState<AccessState>({ kind: 'checking' });
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    let active = true;

    API.get('/admin/overview')
      .then(() => {
        if (active) setState({ kind: 'allowed' });
      })
      .catch((error: { message?: string; response?: { status?: number; data?: { message?: string } } }) => {
        if (!active) return;

        if (error.response?.status === 401) {
          setState({ kind: 'unauthenticated' });
        } else if (error.response?.status === 403) {
          setState({ kind: 'forbidden' });
        } else {
          setState({
            kind: 'error',
            message: error.response?.data?.message ?? error.message ?? 'The authorization check failed.',
          });
        }
      });

    return () => {
      active = false;
    };
  }, [attempt]);

  const isForbidden = state.kind === 'forbidden';
  const title = state.kind === 'checking'
    ? 'Checking access'
    : isForbidden
      ? 'Access denied'
      : state.kind === 'unauthenticated'
        ? 'Sign in required'
        : state.kind === 'allowed'
          ? 'Admin access verified'
          : 'Unable to verify access';
  const subtitle = state.kind === 'forbidden'
    ? 'HTTP 403 · Forbidden'
    : state.kind === 'unauthenticated'
      ? 'HTTP 401 · Unauthenticated'
      : 'Admin routes require an admin account';

  return (
    <Screen title={title} subtitle={subtitle}>
      <View style={styles.content}>
        {state.kind === 'checking' ? (
          <ActivityIndicator size="large" color={C.brand2} />
        ) : (
          <Card tone={isForbidden ? 'bad' : state.kind === 'allowed' ? 'ok' : 'neutral'}>
            <Text style={styles.heading}>
              {state.kind === 'forbidden'
                ? 'Your account does not have the admin role.'
                : state.kind === 'unauthenticated'
                  ? 'You are not authenticated. Sign in to continue.'
                  : state.kind === 'allowed'
                    ? 'The server confirmed admin access.'
                    : 'The server could not complete the access check.'}
            </Text>
            <Text style={styles.message}>
              {state.kind === 'forbidden'
                ? 'The server rejected this request before returning any admin data.'
                : state.kind === 'error'
                  ? state.message
                  : 'The admin management pages are served by the Laravel backend, not the tenant app.'}
            </Text>
          </Card>
        )}

        {state.kind === 'unauthenticated' ? (
          <TouchableOpacity style={S.primary} onPress={() => router.replace('/tenant/login')}>
            <Text style={S.primaryText}>Go to tenant login</Text>
          </TouchableOpacity>
        ) : state.kind === 'forbidden' ? (
          <TouchableOpacity style={S.primary} onPress={() => router.replace('/tenant/dashboard')}>
            <Text style={S.primaryText}>Back to tenant dashboard</Text>
          </TouchableOpacity>
        ) : state.kind === 'error' ? (
          <TouchableOpacity style={S.primary} onPress={() => {
            setState({ kind: 'checking' });
            setAttempt((value) => value + 1);
          }}>
            <Text style={S.primaryText}>Try again</Text>
          </TouchableOpacity>
        ) : state.kind === 'allowed' ? (
          <Text style={styles.allowedNote}>Open the Laravel admin panel on port 8000 to manage the house.</Text>
        ) : null}
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  content: {
    flex: 1,
    justifyContent: 'center',
    gap: 18,
    padding: 20,
  },
  heading: {
    color: C.ink,
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 8,
  },
  message: {
    color: C.muted,
    fontSize: 14,
    lineHeight: 20,
  },
  allowedNote: {
    color: TONES.ok.fg,
    fontSize: 13,
    textAlign: 'center',
  },
});