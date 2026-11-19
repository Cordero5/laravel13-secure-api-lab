import React, { useEffect, useState } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';
import { router } from 'expo-router';
import API from '../../services/api';
import { S, titleCase } from '../../theme';
import { Card, EmptyState, Loader, Pill, Screen } from '../../components/bh';

type CheckInRecord = {
  id: number;
  status: string;
  check_in_date: string | null;
  check_out_date: string | null;
  room?: {
    id: number;
    room_number: string;
    room_type?: string | null;
  } | null;
};

export default function CheckInsScreen() {
  const [items, setItems] = useState<CheckInRecord[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;

    const fetchCheckIns = async () => {
      try {
        const response = await API.get('/check-ins');
        if (active) setItems(response.data ?? []);
      } catch (error) {
        console.error('Failed to fetch check-ins:', error);
      } finally {
        if (active) setLoading(false);
      }
    };

    void fetchCheckIns();

    return () => {
      active = false;
    };
  }, []);

  const day = (value: string | null | undefined) => (value ? String(value).slice(0, 10) : 'no date');

  const renderItem = ({ item }: { item: CheckInRecord }) => (
    <Card style={styles.card}>
      <View style={styles.row}>
        <View style={styles.main}>
          <Text style={S.title}>
            {item.room ? `Room ${item.room.room_number}` : 'Room unassigned'}
          </Text>
          <Text style={S.meta}>
            {item.room ? titleCase(item.room.room_type) || 'Standard' : 'Awaiting room assignment'}
          </Text>
          <Text style={S.meta}>
            In: {day(item.check_in_date)}
            {item.check_out_date ? ` · Out: ${day(item.check_out_date)}` : ''}
          </Text>
        </View>
        <Pill status={item.status} label={item.status === 'checked_in' ? 'Staying' : 'Checked out'} />
      </View>
    </Card>
  );

  return (
    <Screen
      title="Check-ins"
      subtitle={loading ? 'Loading your stay history…' : `${items.length} record(s)`}
      onBack={() => router.back()}
    >
      {loading ? (
        <Loader />
      ) : (
        <FlatList
          data={items}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={S.list}
          ListEmptyComponent={<EmptyState glyph="▢" text="No check-in records yet." />}
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { marginBottom: 12 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  main: { flex: 1, minWidth: 0, gap: 3 },
});
