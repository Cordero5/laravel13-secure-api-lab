import React, { useEffect, useState } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';
import { router } from 'expo-router';
import API from '../services/api';
import { C, S, money } from '../theme';
import { BedDots, Card, EmptyState, Loader, Pill, Screen } from '../components/bh';

export default function RoomsScreen() {
  const [rooms, setRooms] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchRooms();
  }, []);

  const fetchRooms = async () => {
    try {
      const response = await API.get('/rooms');
      setRooms(response.data);
    } catch (error) {
      console.error('Failed to fetch rooms:', error);
    } finally {
      setLoading(false);
    }
  };

  const open = rooms.filter((room: Record<string, any>) => room.status === 'available').length;

  const renderItem = ({ item }: { item: Record<string, any> }) => {
    const capacity = Number(item.capacity ?? 1);
    const occupancy = Number(item.occupancy ?? 0);
    const full = item.status !== 'available';

    return (
      <Card tone={full ? 'bad' : 'ok'} style={styles.card}>
        <View style={styles.head}>
          <View style={styles.headMain}>
            <Text style={styles.roomNumber}>{item.room_number}</Text>
            <Text style={S.meta} numberOfLines={1}>
              {item.room_type || 'Standard'}
            </Text>
          </View>
          <View style={styles.headEnd}>
            <Pill status={item.status} />
            <Text style={styles.price}>{money(item.price)}</Text>
            <Text style={styles.perMonth}>per month</Text>
          </View>
        </View>

        <View style={styles.foot}>
          <BedDots taken={occupancy} capacity={capacity} />
          <Text style={S.meta}>{occupancy} of {capacity} {capacity === 1 ? 'bed' : 'beds'} used</Text>
        </View>
      </Card>
    );
  };

  return (
    <Screen
      title="Rooms"
      subtitle={loading ? 'Loading the house…' : `${rooms.length} room(s) · ${open} open`}
      onBack={() => router.back()}
    >
      {loading ? (
        <Loader />
      ) : (
        <FlatList
          data={rooms}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={S.list}
          ListEmptyComponent={
            <EmptyState glyph="▢" text="No rooms found. Add some from the admin panel." />
          }
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { marginBottom: 12 },
  head: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12 },
  headMain: { flex: 1, minWidth: 0 },
  headEnd: { alignItems: 'flex-end', gap: 4 },
  roomNumber: {
    fontSize: 24,
    fontWeight: '800',
    color: C.ink,
    letterSpacing: 0.4,
    marginBottom: 2,
    fontVariant: ['tabular-nums'],
  },
  price: { fontSize: 14, fontWeight: '700', color: C.ink, fontVariant: ['tabular-nums'] },
  perMonth: { fontSize: 10.5, color: C.faint, textTransform: 'uppercase', letterSpacing: 0.6 },
  foot: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 14,
    paddingTop: 11,
    borderTopWidth: 1,
    borderTopColor: C.line,
  },
});
