import React from 'react';
import { ActivityIndicator, StyleSheet, Text, TouchableOpacity, View, type StyleProp, type ViewStyle } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { C, S, TONES, initials, toneOf, titleCase, type Tone } from '../theme';

/**
 * Small presentational primitives shared by every screen. They exist so the
 * tenant app and the Laravel admin panel draw cards, pills and meters the
 * same way - change a token in src/theme.ts and all screens follow.
 */

export function Screen({
  title,
  subtitle,
  onBack,
  right,
  children,
}: {
  title: string;
  subtitle?: string;
  onBack?: () => void;
  right?: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <SafeAreaView style={S.screen} edges={['top', 'left', 'right']}>
      <View style={S.header}>
        {onBack ? (
          <TouchableOpacity style={S.backChip} onPress={onBack} hitSlop={8} activeOpacity={0.7}>
            <Text style={S.backGlyph}>‹</Text>
          </TouchableOpacity>
        ) : null}
        <View style={S.headerMain}>
          <Text style={S.headerTitle} numberOfLines={1}>{title}</Text>
          {subtitle ? <Text style={S.headerSub} numberOfLines={1}>{subtitle}</Text> : null}
        </View>
        {right}
      </View>
      <View style={{ flex: 1 }}>{children}</View>
    </SafeAreaView>
  );
}

export function Card({
  children,
  tone,
  style,
}: {
  children: React.ReactNode;
  tone?: Tone;
  style?: StyleProp<ViewStyle>;
}) {
  return (
    <View style={[S.card, tone ? styles.cardTone : null, style]}>
      {tone ? <View style={[S.accentBar, styles.accent, { backgroundColor: TONES[tone].fg }]} /> : null}
      {children}
    </View>
  );
}

export function Pill({ status, label }: { status: string | null | undefined; label?: string }) {
  const tone = toneOf(status);
  return (
    <Text style={[S.pill, { backgroundColor: TONES[tone].bg, color: TONES[tone].fg }]}>
      {label ?? titleCase(status) ?? '—'}
    </Text>
  );
}

export function Avatar({
  name,
  size = 32,
  tone = 'brand',
}: {
  name: string | null | undefined;
  size?: number;
  tone?: Tone;
}) {
  return (
    <View
      style={[
        S.avatar,
        {
          width: size,
          height: size,
          borderRadius: Math.max(9, size * 0.3),
          backgroundColor: tone === 'brand' ? C.brand : TONES[tone].bg,
        },
      ]}
    >
      <Text style={[S.avatarText, { fontSize: size * 0.36, color: tone === 'brand' ? '#fff' : TONES[tone].fg }]}>
        {initials(name)}
      </Text>
    </View>
  );
}

export function Meter({
  value,
  max,
  tone = 'brand',
}: {
  value: number;
  max: number;
  tone?: Tone;
}) {
  const pct = max > 0 ? Math.min(100, Math.max(0, Math.round((value / max) * 100))) : 0;
  return (
    <View style={S.meterTrack}>
      <View style={[S.meterFill, { width: `${pct}%`, backgroundColor: TONES[tone].fg }]} />
    </View>
  );
}

export function BedDots({ taken, capacity }: { taken: number; capacity: number }) {
  const total = Math.max(0, capacity);
  return (
    <View style={styles.dots}>
      {Array.from({ length: total }).map((_, index) => (
        <View key={index} style={[S.dot, index < taken && S.dotOn]} />
      ))}
    </View>
  );
}

export function SectionLabel({ children }: { children: string }) {
  return <Text style={S.label}>{children}</Text>;
}

export function EmptyState({ glyph = '○', text }: { glyph?: string; text: string }) {
  return (
    <View style={S.empty}>
      <Text style={S.emptyGlyph}>{glyph}</Text>
      <Text style={S.emptyText}>{text}</Text>
    </View>
  );
}

export function Loader() {
  return (
    <View style={S.loader}>
      <ActivityIndicator size="large" color={C.brand2} />
    </View>
  );
}

const styles = StyleSheet.create({
  accent: { position: 'absolute', left: 0, top: 0, bottom: 0 },
  cardTone: { paddingLeft: 18 },
  dots: { flexDirection: 'row', alignItems: 'center' },
});
