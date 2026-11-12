import React, { useEffect, useState } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';
import { router } from 'expo-router';
import API from '../services/api';
import { C, S } from '../theme';
import { Card, EmptyState, Loader, Screen } from '../components/bh';

export default function HouseRulesScreen() {
  const [rules, setRules] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchRules();
  }, []);

  const fetchRules = async () => {
    try {
      const response = await API.get('/house-rules');
      setRules(response.data);
    } catch (error) {
      console.error('Failed to fetch house rules:', error);
    } finally {
      setLoading(false);
    }
  };

  const renderItem = ({ item, index }: { item: Record<string, any>; index: number }) => (
    <Card style={styles.card}>
      <View style={styles.row}>
        <View style={styles.number}>
          <Text style={styles.numberText}>{String(index + 1).padStart(2, '0')}</Text>
        </View>
        <View style={styles.main}>
          <Text style={styles.title}>{item.title || `Rule ${index + 1}`}</Text>
          <Text style={styles.body}>{item.description}</Text>
        </View>
      </View>
    </Card>
  );

  return (
    <Screen
      title="House Rules"
      subtitle={loading ? 'Loading…' : `${rules.length} rule(s) in the house`}
      onBack={() => router.back()}
    >
      {loading ? (
        <Loader />
      ) : (
        <FlatList
          data={rules}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={S.list}
          ListEmptyComponent={<EmptyState glyph="§" text="No house rules set yet." />}
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { marginBottom: 12 },
  row: { flexDirection: 'row', gap: 12 },
  number: {
    width: 32,
    height: 32,
    borderRadius: 10,
    backgroundColor: C.brandSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  numberText: { fontSize: 12.5, fontWeight: '800', color: C.brand, fontVariant: ['tabular-nums'] },
  main: { flex: 1, minWidth: 0 },
  title: { fontSize: 15, fontWeight: '700', color: C.ink, marginBottom: 4, letterSpacing: -0.2 },
  body: { fontSize: 13.5, color: C.muted, lineHeight: 20 },
});
