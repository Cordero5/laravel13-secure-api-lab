import React, { useEffect, useState } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';
import { router } from 'expo-router';
import API from '../services/api';
import { C, S, money, titleCase } from '../theme';
import { Card, EmptyState, Loader, Pill, Screen } from '../components/bh';

export default function PaymentsScreen() {
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchPayments();
  }, []);

  const fetchPayments = async () => {
    try {
      const response = await API.get('/payments');
      setPayments(response.data);
    } catch (error) {
      console.error('Failed to fetch payments:', error);
    } finally {
      setLoading(false);
    }
  };

  const paidTotal = payments
    .filter((payment: Record<string, any>) => payment.status === 'paid')
    .reduce((total: number, payment: Record<string, any>) => total + Number(payment.amount ?? 0), 0);

  const renderItem = ({ item }: { item: Record<string, any> }) => (
    <Card tone={item.status === 'paid' ? 'ok' : 'warn'} style={styles.card}>
      <View style={styles.row}>
        <View style={styles.main}>
          <Text style={styles.amount}>{money(item.amount)}</Text>
          <Text style={S.meta}>
            {item.payment_date || 'No date recorded'}
            {item.reference_number ? ` · ${item.reference_number}` : ''}
          </Text>
        </View>
        <View style={styles.end}>
          <Pill status={item.status} label={titleCase(item.status || 'pending')} />
          {item.tenant?.name ? <Text style={styles.who} numberOfLines={1}>{item.tenant.name}</Text> : null}
        </View>
      </View>
    </Card>
  );

  return (
    <Screen
      title="Payment History"
      subtitle={loading ? 'Loading your records…' : `${payments.length} record(s) · ${money(paidTotal)} paid`}
      onBack={() => router.back()}
    >
      {loading ? (
        <Loader />
      ) : (
        <FlatList
          data={payments}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={S.list}
          ListEmptyComponent={<EmptyState glyph="₱" text="No payment records found." />}
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { marginBottom: 12 },
  row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  main: { flex: 1, minWidth: 0, gap: 3 },
  end: { alignItems: 'flex-end', gap: 5 },
  amount: { fontSize: 18, fontWeight: '800', color: C.ink, letterSpacing: -0.4, fontVariant: ['tabular-nums'] },
  who: { fontSize: 11, color: C.faint, maxWidth: 120 },
});
