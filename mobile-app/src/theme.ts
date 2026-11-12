import { StyleSheet, type TextStyle, type ViewStyle } from 'react-native';

/**
 * One source of truth for the app's look, mirroring the `:root` custom
 * properties in backend/resources/views/admin/layout.blade.php so the tenant
 * app and the admin panel read as the same product.
 */
export const C = {
  bg: '#f4f6fb',
  surface: '#ffffff',
  ink: '#1b2430',
  muted: '#6b7688',
  faint: '#98a3b3',
  line: '#e6eaf2',
  brand: '#2c516e',
  brand2: '#40739e',
  brandSoft: '#eaf0f6',
  track: '#eaeef5',
} as const;

export type Tone = 'brand' | 'ok' | 'warn' | 'bad' | 'neutral';

export const TONES: Record<Tone, { fg: string; bg: string }> = {
  brand: { fg: '#2c516e', bg: '#eaf0f6' },
  ok: { fg: '#1a7a4b', bg: '#e7f4ed' },
  warn: { fg: '#96600a', bg: '#fdf1de' },
  bad: { fg: '#b03333', bg: '#fcebeb' },
  neutral: { fg: '#5a6779', bg: '#eef1f5' },
};

const STATUS_TONES: Record<string, Tone> = {
  paid: 'ok',
  pending: 'warn',
  overdue: 'bad',
  cancelled: 'neutral',
  available: 'ok',
  occupied: 'bad',
  full: 'bad',
  checked_in: 'brand',
  checked_out: 'neutral',
  active: 'ok',
  inactive: 'neutral',
};

export const toneOf = (status: string | null | undefined): Tone =>
  STATUS_TONES[String(status ?? '').toLowerCase()] ?? 'neutral';

/** Peso amount formatted the same way the admin panel prints it. */
export const money = (value: string | number | null | undefined) =>
  `₱${Number(value ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export const initials = (name: string | null | undefined) =>
  (name ?? '')
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('') || '·';

export const titleCase = (value: string | null | undefined) => {
  const text = (value ?? '').trim();
  return text ? text.charAt(0).toUpperCase() + text.slice(1).replace(/_/g, ' ') : '';
};

const shadow = (depth: number): ViewStyle =>
  depth === 0
    ? {}
    : {
        shadowColor: '#141e2d',
        shadowOffset: { width: 0, height: depth === 1 ? 1 : 8 },
        shadowOpacity: depth === 1 ? 0.06 : 0.09,
        shadowRadius: depth === 1 ? 2 : 18,
        elevation: depth,
      };

export const S = StyleSheet.create({
  screen: { flex: 1, backgroundColor: C.bg },
  scroll: { padding: 16, paddingBottom: 34 },
  list: { padding: 16, paddingBottom: 34 },

  /* Header bar */
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 14,
    paddingVertical: 13,
    backgroundColor: C.surface,
    borderBottomWidth: 1,
    borderBottomColor: C.line,
  },
  headerMain: { flex: 1, minWidth: 0 },
  headerTitle: { fontSize: 17, fontWeight: '700', color: C.ink, letterSpacing: -0.3 },
  headerSub: { fontSize: 12, color: C.muted, marginTop: 2 },
  backChip: {
    width: 32,
    height: 32,
    borderRadius: 10,
    backgroundColor: C.brandSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  backGlyph: { fontSize: 19, lineHeight: 21, color: C.brand, fontWeight: '700' },

  /* Surfaces */
  card: {
    backgroundColor: C.surface,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: C.line,
    padding: 15,
    ...shadow(1),
  },
  cardFlush: { padding: 0, overflow: 'hidden' },
  accentBar: { width: 3, alignSelf: 'stretch', borderRadius: 2 },

  /* Type */
  label: {
    fontSize: 10.5,
    fontWeight: '700',
    letterSpacing: 1.1,
    textTransform: 'uppercase',
    color: C.faint,
  },
  title: { fontSize: 16, fontWeight: '700', color: C.ink, letterSpacing: -0.2 },
  body: { fontSize: 14, color: C.muted, lineHeight: 20 },
  meta: { fontSize: 12, color: C.muted },
  value: { fontSize: 22, fontWeight: '700', color: C.ink, letterSpacing: -0.5 },
  money: { fontSize: 14, fontWeight: '700', color: C.ink, fontVariant: ['tabular-nums'] },

  /* Chips, pills, meters */
  avatar: {
    backgroundColor: C.brand,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: '#fff', fontWeight: '800', letterSpacing: 0.2 },
  pill: {
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.5,
    textTransform: 'uppercase',
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 999,
    overflow: 'hidden',
  },
  meterTrack: {
    height: 8,
    borderRadius: 999,
    backgroundColor: C.track,
    overflow: 'hidden',
    flexDirection: 'row',
  },
  meterFill: { height: 8, borderRadius: 999 },
  dot: { width: 9, height: 9, borderRadius: 5, backgroundColor: '#dde3ec', marginRight: 4 },
  dotOn: { backgroundColor: C.brand2 },

  /* Rows */
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 12,
    borderTopWidth: 1,
    borderTopColor: C.line,
  },
  rowFirst: { borderTopWidth: 0, paddingTop: 2 },
  rowMain: { flex: 1, minWidth: 0 },
  rowEnd: { alignItems: 'flex-end', gap: 3 },
  chevron: { fontSize: 18, lineHeight: 20, color: '#b6c0cf', fontWeight: '700' },

  /* Buttons */
  primary: {
    backgroundColor: C.brand,
    borderRadius: 11,
    paddingVertical: 14,
    alignItems: 'center',
    justifyContent: 'center',
    ...shadow(1),
  },
  primaryText: { color: '#fff', fontSize: 15.5, fontWeight: '700', letterSpacing: -0.1 },
  primaryDisabled: { opacity: 0.6 },
  link: { paddingVertical: 10, alignItems: 'center' },
  linkText: { color: C.brand2, fontSize: 14, fontWeight: '600' },
  danger: {
    backgroundColor: TONES.bad.bg,
    borderRadius: 10,
    paddingHorizontal: 13,
    paddingVertical: 8,
  },
  dangerText: { color: TONES.bad.fg, fontSize: 12.5, fontWeight: '800' },

  /* Forms */
  fieldLabel: { fontSize: 12.5, fontWeight: '700', color: C.muted, marginBottom: 6, marginTop: 14 },
  field: {
    backgroundColor: C.surface,
    borderWidth: 1,
    borderColor: '#d8dfea',
    borderRadius: 11,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 15.5,
    color: C.ink,
  },
  fieldFocused: { borderColor: C.brand2, backgroundColor: '#fdfeff' },

  /* States */
  loader: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingVertical: 70 },
  empty: { alignItems: 'center', paddingVertical: 34, paddingHorizontal: 20 },
  emptyGlyph: { fontSize: 22, color: '#c3ccd8', marginBottom: 8 },
  emptyText: { fontSize: 13.5, color: C.muted, textAlign: 'center', lineHeight: 19 },
  divider: { height: 1, backgroundColor: C.line },
});

export type { TextStyle, ViewStyle };
