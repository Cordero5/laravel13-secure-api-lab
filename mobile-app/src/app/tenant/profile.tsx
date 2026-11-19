import React, { useEffect, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { router } from 'expo-router';
import API from '../../services/api';
import { C, S, money, titleCase } from '../../theme';
import { Avatar, Card, EmptyState, Loader, Pill, Screen, SectionLabel } from '../../components/bh';

type Room = {
  id: number;
  room_number: string;
  room_type: string | null;
  price: string | number;
};

type Payment = {
  id: number;
  amount: string | number;
  status: string;
  payment_date: string | null;
  reference_number: string | null;
};

type CheckIn = {
  id: number;
  status: string;
  check_in_date: string | null;
  check_out_date: string | null;
  room?: Room | null;
};

type Profile = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  room?: Room | null;
  payments?: Payment[];
  check_ins?: CheckIn[];
};

export default function ProfileScreen() {
  const [tenant, setTenant] = useState<Profile | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    const fetchProfile = async () => {
      try {
        const response = await API.get('/me');
        if (active) setTenant(response.data as Profile);
      } catch (fetchError) {
        if (active) setError('Could not load your tenant profile.');
        console.error('Failed to fetch profile:', fetchError);
      } finally {
        if (active) setLoading(false);
      }
    };

    void fetchProfile();

    return () => {
      active = false;
    };
  }, []);

  const day = (value: string | null | undefined) => (value ? String(value).slice(0, 10) : 'no date');

  const renderBody = () => {
    if (loading) return <Loader />;

    if (error || !tenant) {
      return <EmptyState glyph="○" text={error ?? 'No profile found.'} />;
    }

    const room = tenant.room ?? null;

    return (
      <View style={styles.body}>
        <Card style={styles.identity}>
          <View style={styles.identityRow}>
            <Avatar name={tenant.name} size={44} />
            <View style={styles.identityMain}>
              <Text style={styles.name}>{tenant.name}</Text>
              <Text style={S.meta} numberOfLines={1}>{tenant.email || 'No email on file'}</Text>
              <Text style={S.meta}>{tenant.phone || 'No contact number on file'}</Text>
            </View>
          </View>
        </Card>

        <Card tone={room ? 'brand' : 'warn'} style={styles.roomCard}>
          <View style={styles.roomRow}>
            <View style={styles.roomMain}>
              <SectionLabel>{room ? 'Assigned Room' : 'Not Assigned Yet'}</SectionLabel>
              <Text style={styles.roomNumber}>{room ? room.room_number : '—'}</Text>
              <Text style={S.meta}>
                {room ? `${titleCase(room.room_type) || 'Standard'} · ${money(room.price)} per month` : 'The admin assigns a room at check-in.'}
              </Text>
            </View>
            <View style={styles.roomEnd}>
              <Pill status={room ? 'active' : 'pending'} label={room ? 'In house' : 'Awaiting'} />
            </View>
          </View>
        </Card>

        <View style={styles.sectionGap}>
          <SectionLabel>My Payments</SectionLabel>
        </View>
        {tenant.payments && tenant.payments.length > 0 ? (
          tenant.payments.map((payment) => (
            <Card key={payment.id} style={styles.rowCard}>
              <View style={styles.row}>
                <View style={styles.rowMain}>
                  <Text style={S.money}>{money(payment.amount)}</Text>
                  <Text style={S.meta}>
                    {day(payment.payment_date)}
                    {payment.reference_number ? ` · ${payment.reference_number}` : ''}
                  </Text>
                </View>
                <Pill status={payment.status} />
              </View>
            </Card>
          ))
        ) : (
          <EmptyState glyph="₱" text="No payments recorded yet." />
        )}

        <View style={styles.sectionGap}>
          <SectionLabel>My Check-Ins</SectionLabel>
        </View>
        {tenant.check_ins && tenant.check_ins.length > 0 ? (
          tenant.check_ins.map((entry) => (
            <Card key={entry.id} style={styles.rowCard}>
              <View style={styles.row}>
                <View style={styles.rowMain}>
                  <Text style={styles.rowTitle}>
                    {entry.room ? `Room ${entry.room.room_number}` : 'Room unassigned'}
                  </Text>
                  <Text style={S.meta}>
                    In: {day(entry.check_in_date)}
                    {entry.check_out_date ? ` · Out: ${day(entry.check_out_date)}` : ''}
                  </Text>
                </View>
                <Pill status={entry.status} label={entry.status === 'checked_in' ? 'Staying' : 'Checked out'} />
              </View>
            </Card>
          ))
        ) : (
          <EmptyState glyph="▢" text="No check-in records yet." />
        )}
      </View>
    );
  };

  return (
    <Screen
      title="My Profile"
      subtitle={tenant?.name ?? 'Your room, payments and check-ins'}
      onBack={() => router.back()}
    >
      <ScrollView contentContainerStyle={S.scroll} showsVerticalScrollIndicator={false}>
        {renderBody()}
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  body: { paddingBottom: 6 },
  identity: { marginBottom: 12 },
  identityRow: { flexDirection: 'row', alignItems: 'center', gap: 13 },
  identityMain: { flex: 1, minWidth: 0, gap: 2 },
  name: { fontSize: 17, fontWeight: '800', color: C.ink, letterSpacing: -0.3 },

  roomCard: { marginBottom: 4 },
  roomRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12 },
  roomMain: { flex: 1, minWidth: 0 },
  roomEnd: { alignItems: 'flex-end' },
  roomNumber: { fontSize: 30, fontWeight: '800', color: C.ink, letterSpacing: 0.5, marginTop: 4, marginBottom: 2, fontVariant: ['tabular-nums'] },

  sectionGap: { marginTop: 16, marginBottom: 9 },
  rowCard: { marginBottom: 10, paddingVertical: 13 },
  row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  rowMain: { flex: 1, minWidth: 0, gap: 3 },
  rowTitle: { fontSize: 14.5, fontWeight: '700', color: C.ink },
});
