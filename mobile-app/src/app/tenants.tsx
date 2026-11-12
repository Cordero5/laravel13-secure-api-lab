import React, { useEffect, useState } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';
import { router } from 'expo-router';
import API from '../services/api';
import { C, S } from '../theme';
import { Avatar, Card, EmptyState, Loader, Pill, Screen } from '../components/bh';

export default function TenantsScreen() {
  const [tenants, setTenants] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchTenants();
  }, []);

  const fetchTenants = async () => {
    try {
      const response = await API.get('/tenants');
      setTenants(response.data);
    } catch (error) {
      console.error('Failed to fetch tenants:', error);
    } finally {
      setLoading(false);
    }
  };

  const housed = tenants.filter((tenant: Record<string, any>) => tenant.room).length;

  const renderItem = ({ item }: { item: Record<string, any> }) => (
    <Card style={styles.card}>
      <View style={styles.row}>
        <Avatar name={item.name} />
        <View style={styles.main}>
          <Text style={S.title} numberOfLines={1}>{item.name}</Text>
          <Text style={S.meta} numberOfLines={1}>{item.email || 'No email on file'}</Text>
          <Text style={S.meta}>{item.phone || 'No contact number'}</Text>
        </View>
        <View style={styles.end}>
          {item.room ? (
            <View style={styles.roomChip}>
              <Text style={styles.roomLabel}>Room</Text>
              <Text style={styles.roomNumber}>{item.room.room_number}</Text>
            </View>
          ) : (
            <Pill status="pending" label="No room" />
          )}
        </View>
      </View>
    </Card>
  );

  return (
    <Screen
      title="Tenants"
      subtitle={loading ? 'Loading the house…' : `${tenants.length} registered · ${housed} with a room`}
      onBack={() => router.back()}
    >
      {loading ? (
        <Loader />
      ) : (
        <FlatList
          data={tenants}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={S.list}
          ListEmptyComponent={<EmptyState glyph="◦" text="No tenants registered yet." />}
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { marginBottom: 12 },
  row: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  main: { flex: 1, minWidth: 0, gap: 2 },
  end: { alignItems: 'flex-end' },
  roomChip: {
    backgroundColor: C.brandSoft,
    borderRadius: 10,
    paddingHorizontal: 10,
    paddingVertical: 6,
    alignItems: 'center',
    minWidth: 52,
  },
  roomLabel: {
    fontSize: 9.5,
    fontWeight: '800',
    letterSpacing: 0.8,
    textTransform: 'uppercase',
    color: '#7d94ab',
  },
  roomNumber: { fontSize: 16, fontWeight: '800', color: C.brand, fontVariant: ['tabular-nums'] },
});
