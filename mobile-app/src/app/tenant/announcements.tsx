import React, { useEffect, useState } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';
import { router } from 'expo-router';
import API from '../../services/api';
import { C, S } from '../../theme';
import { Card, EmptyState, Loader, Screen } from '../../components/bh';

type Announcement = {
  id: number;
  title: string;
  content: string;
  published_at: string | null;
};

export default function AnnouncementsScreen() {
  const [announcements, setAnnouncements] = useState<Announcement[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    const fetchAnnouncements = async () => {
      try {
        const response = await API.get('/announcements');
        if (active) setAnnouncements(response.data);
      } catch (fetchError) {
        if (active) setError('Could not reach the server. Check that you are signed in.');
        console.error('Failed to fetch announcements:', fetchError);
      } finally {
        if (active) setLoading(false);
      }
    };

    void fetchAnnouncements();

    return () => {
      active = false;
    };
  }, []);

  const formatDate = (value: string | null) => {
    if (!value) return '';
    const date = new Date(value);
    return isNaN(date.getTime())
      ? value
      : date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
  };

  const tile = (value: string | null) => {
    if (!value) return { day: '—', month: '' };
    const date = new Date(value);
    if (isNaN(date.getTime())) return { day: '•', month: '' };
    return {
      day: String(date.getDate()),
      month: date.toLocaleDateString(undefined, { month: 'short' }).toUpperCase(),
    };
  };

  const renderItem = ({ item }: { item: Announcement }) => {
    const posted = tile(item.published_at);
    return (
      <Card style={styles.card}>
        <View style={styles.row}>
          <View style={styles.date}>
            <Text style={styles.dateDay}>{posted.day}</Text>
            {posted.month ? <Text style={styles.dateMonth}>{posted.month}</Text> : null}
          </View>
          <View style={styles.main}>
            <Text style={styles.title} numberOfLines={2}>{item.title}</Text>
            <Text style={styles.body}>{item.content}</Text>
            {item.published_at ? <Text style={styles.stamp}>{formatDate(item.published_at)}</Text> : null}
          </View>
        </View>
      </Card>
    );
  };

  return (
    <Screen
      title="Announcements"
      subtitle={loading ? 'Loading…' : `${announcements.length} notice(s) posted`}
      onBack={() => router.back()}
    >
      {loading ? (
        <Loader />
      ) : (
        <FlatList
          data={announcements}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={S.list}
          ListEmptyComponent={
            <EmptyState
              glyph="▲"
              text={error ?? 'No announcements from the admin yet.'}
            />
          }
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { marginBottom: 12 },
  row: { flexDirection: 'row', gap: 12 },
  date: {
    width: 42,
    borderRadius: 10,
    backgroundColor: C.brandSoft,
    paddingVertical: 7,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dateDay: { fontSize: 17, fontWeight: '800', color: C.brand, lineHeight: 20, fontVariant: ['tabular-nums'] },
  dateMonth: { fontSize: 9.5, fontWeight: '800', letterSpacing: 0.8, color: '#7d94ab' },
  main: { flex: 1, minWidth: 0 },
  title: { fontSize: 15, fontWeight: '700', color: C.ink, letterSpacing: -0.2, marginBottom: 4 },
  body: { fontSize: 13.5, color: C.muted, lineHeight: 20 },
  stamp: { fontSize: 11, color: C.faint, marginTop: 7 },
});
