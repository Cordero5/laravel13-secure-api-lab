import React, { useEffect, useState } from 'react';
import { ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { router, type Href } from 'expo-router';
import API, { clearToken, setUnauthorizedHandler } from '../services/api';
import { C, S, TONES, money, titleCase, type Tone } from '../theme';
import { Card, EmptyState, Meter, Screen, SectionLabel } from '../components/bh';

type Room = {
  id: number;
  room_number: string;
  room_type: string | null;
  price: string | number;
  capacity: number;
  occupancy: number;
  is_full: boolean;
};

type Me = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  room?: Room | null;
};

const SECTIONS: { route: Href; title: string; desc: string; mark: string; tone: Tone }[] = [
  { route: '/rooms', title: 'Rooms', desc: 'Availability, price and capacity', mark: 'R', tone: 'brand' },
  { route: '/tenants', title: 'Tenants', desc: 'Who lives in the house', mark: 'T', tone: 'neutral' },
  { route: '/payments', title: 'Payments', desc: 'Rent records and payment status', mark: '₱', tone: 'ok' },
  { route: '/house-rules', title: 'House Rules', desc: 'Rules you follow while staying here', mark: '§', tone: 'warn' },
  { route: '/announcements', title: 'Announcements', desc: 'Notices posted by the admin', mark: 'A', tone: 'brand' },
  { route: '/profile', title: 'My Profile', desc: 'Your room, payments and check-ins', mark: 'P', tone: 'neutral' },
];

export default function DashboardScreen() {
  const [me, setMe] = useState<Me | null>(null);
  const [meLoaded, setMeLoaded] = useState(false);

  useEffect(() => {
    // If any protected call comes back 401, the session is gone - send the
    // user back to the login screen instead of showing empty lists.
    setUnauthorizedHandler(() => router.replace('/login'));
    return () => setUnauthorizedHandler(null);
  }, []);

  useEffect(() => {
    let active = true;
    API.get('/me')
      .then((response) => { if (active) setMe(response.data as Me); })
      .catch((error) => console.warn('Could not load the tenant header:', error))
      .finally(() => { if (active) setMeLoaded(true); });
    return () => { active = false; };
  }, []);

  const handleLogout = async () => {
    // Revoke the token server-side, then drop it locally.
    try {
      await API.post('/logout');
    } catch (error) {
      console.warn('Logout request failed, clearing the local token anyway:', error);
    } finally {
      clearToken();
      router.replace('/login');
    }
  };

  const firstName = me?.name?.trim().split(/\s+/)[0] ?? '';
  const room = me?.room ?? null;
  const beds = (count: number) => `${count} ${count === 1 ? 'bed' : 'beds'}`;

  return (
    <Screen
      title={firstName ? `Hello, ${firstName}` : 'Dashboard'}
      subtitle={me?.email ?? 'Boarding House Management System'}
      right={
        <TouchableOpacity style={styles.logout} onPress={handleLogout} activeOpacity={0.75}>
          <Text style={styles.logoutText}>Logout</Text>
        </TouchableOpacity>
      }
    >
      <ScrollView contentContainerStyle={S.scroll} showsVerticalScrollIndicator={false}>
        {/* The room card: what a boarder looks for first, and the one fact
            that differs per tenant. */}
        {room ? (
          <Card style={styles.roomCard}>
            <View style={styles.cardHead}>
              <SectionLabel>Room</SectionLabel>
              {/* Neutral rather than bad/ok: how many beds the room holds is a
                  fact about the room, not something the boarder can act on. */}
              <Text style={[styles.cardFlag, { color: room.is_full ? TONES.neutral.fg : TONES.ok.fg }]}>
                {room.is_full ? 'Fully occupied' : 'Beds free'}
              </Text>
            </View>

            <View style={styles.figures}>
              <View style={styles.figure}>
                <Text style={styles.figureLabel}>Room number</Text>
                <Text style={styles.figureValue}>{room.room_number}</Text>
                <Text style={S.meta}>{titleCase(room.room_type) || 'Standard'}</Text>
              </View>
              <View style={styles.divider} />
              <View style={styles.figure}>
                <Text style={styles.figureLabel}>Monthly rent</Text>
                <Text style={[styles.figureValue, { color: TONES.brand.fg }]}>{money(room.price)}</Text>
                <Text style={S.meta}>{beds(room.capacity)} in the room</Text>
              </View>
            </View>

            <Meter value={room.occupancy} max={room.capacity} />
            <Text style={styles.meterCaption}>
              {room.occupancy} of {beds(room.capacity)} occupied
            </Text>
          </Card>
        ) : (
          <Card style={[styles.roomCard, styles.roomCardEmpty]}>
            <View style={styles.cardHead}>
              <SectionLabel>No room yet</SectionLabel>
              {meLoaded ? <Text style={[styles.cardFlag, { color: TONES.warn.fg }]}>Awaiting check-in</Text> : null}
            </View>
            {/* No number slot at all when unassigned: a lone placeholder dash
                reads as a broken render rather than as an empty state. */}
            <Text style={styles.emptyRoomText}>
              {meLoaded
                ? 'The admin assigns your room when they check you in.'
                : 'Loading your room assignment…'}
            </Text>
          </Card>
        )}

        {meLoaded && !me ? (
          <EmptyState glyph="○" text="Sign-in worked, but your tenant record is not linked yet. Ask the admin to complete your check-in." />
        ) : null}

        <View style={styles.sectionGap}>
          <SectionLabel>All Sections</SectionLabel>
        </View>

        {/* No `gap` here: 48% + 48% + a fixed gap overflows a narrow phone and
            collapses the grid to one column. space-between supplies the gutter. */}
        <View style={styles.grid}>
          {SECTIONS.map((section) => (
            <TouchableOpacity
              key={section.title}
              style={styles.card}
              activeOpacity={0.75}
              onPress={() => router.push(section.route)}
            >
              <View style={[styles.chip, { backgroundColor: TONES[section.tone].bg }]}>
                <Text style={[styles.chipText, { color: TONES[section.tone].fg }]}>{section.mark}</Text>
              </View>
              <Text style={styles.cardTitle} numberOfLines={1}>{section.title}</Text>
              <Text style={styles.cardDesc} numberOfLines={2}>{section.desc}</Text>
              <Text style={S.chevron}>›</Text>
            </TouchableOpacity>
          ))}
        </View>
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  logout: {
    backgroundColor: TONES.bad.bg,
    borderRadius: 10,
    paddingHorizontal: 13,
    paddingVertical: 8,
  },
  logoutText: { color: TONES.bad.fg, fontSize: 12.5, fontWeight: '800' },

  roomCard: { padding: 16, marginBottom: 14 },
  roomCardEmpty: {
    borderStyle: 'dashed',
    borderColor: '#e0a437',
    shadowOpacity: 0,
    elevation: 0,
  },
  cardHead: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  cardFlag: { fontSize: 11, fontWeight: '800', letterSpacing: 0.4, textTransform: 'uppercase' },
  figures: { flexDirection: 'row', alignItems: 'center', marginBottom: 14 },
  figure: { flex: 1 },
  figureLabel: { fontSize: 11, color: C.muted, marginBottom: 2 },
  figureValue: { fontSize: 22, fontWeight: '800', letterSpacing: -0.4, marginBottom: 2, fontVariant: ['tabular-nums'] },
  divider: { width: 1, alignSelf: 'stretch', backgroundColor: C.line, marginHorizontal: 12 },
  meterCaption: { fontSize: 11.5, color: C.muted, marginTop: 8 },
  emptyRoomText: { fontSize: 13, color: C.muted, lineHeight: 19 },

  sectionGap: { marginTop: 8, marginBottom: 10 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' },
  card: {
    width: '48%',
    minHeight: 128,
    backgroundColor: C.surface,
    paddingVertical: 14,
    paddingHorizontal: 12,
    borderRadius: 14,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: C.line,
    shadowColor: '#141e2d',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.06,
    shadowRadius: 2,
    elevation: 1,
  },
  chip: {
    width: 30,
    height: 30,
    borderRadius: 9,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  chipText: { fontSize: 14, fontWeight: '800' },
  cardTitle: { fontSize: 14, fontWeight: '700', color: C.ink, marginBottom: 4 },
  cardDesc: { fontSize: 11.5, color: C.muted, lineHeight: 16, paddingRight: 10 },
});
