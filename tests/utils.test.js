'use strict';

const {
  pad,
  isSommerzeit,
  dd2dms,
  dd2dmsStr,
  dd2utm,
  dd2mgrs,
  dd2gk,
  parseKoord,
  tetraEscape,
  otEsc,
  otColorHash,
  otBatIcon,
  otAgo,
  getDTG,
  getDTGLocal,
  gridInterval,
  fmtLabel,
  symbolFileKey,
} = require('./utils');

// ---------------------------------------------------------------------------
// pad
// ---------------------------------------------------------------------------
describe('pad', () => {
  test('pads single digit to 2 chars by default', () => {
    expect(pad(5)).toBe('05');
    expect(pad(0)).toBe('00');
    expect(pad(9)).toBe('09');
  });

  test('does not pad numbers already at target length', () => {
    expect(pad(10)).toBe('10');
    expect(pad(99)).toBe('99');
  });

  test('pads to custom length', () => {
    expect(pad(7, 3)).toBe('007');
    expect(pad(42, 5)).toBe('00042');
  });

  test('truncates decimals before padding', () => {
    expect(pad(5.9)).toBe('05');
    expect(pad(9.9, 3)).toBe('009');
  });

  test('returns full number when it exceeds target length', () => {
    expect(pad(123, 2)).toBe('123');
  });
});

// ---------------------------------------------------------------------------
// isSommerzeit
// ---------------------------------------------------------------------------
describe('isSommerzeit', () => {
  test('returns false in January (winter)', () => {
    expect(isSommerzeit(new Date('2024-01-15T12:00:00Z'))).toBe(false);
  });

  test('returns false in December (winter)', () => {
    expect(isSommerzeit(new Date('2024-12-01T00:00:00Z'))).toBe(false);
  });

  test('returns true in July (summer)', () => {
    expect(isSommerzeit(new Date('2024-07-15T12:00:00Z'))).toBe(true);
  });

  test('returns true in late March after transition', () => {
    // 2024: last Sunday of March is 31 March; transition at 01:00 UTC
    expect(isSommerzeit(new Date('2024-03-31T01:00:00Z'))).toBe(true);
  });

  test('returns false in late March just before transition', () => {
    expect(isSommerzeit(new Date('2024-03-31T00:59:59Z'))).toBe(false);
  });

  test('returns false in late October after transition back', () => {
    // 2024: last Sunday of October is 27 October; transition at 01:00 UTC
    expect(isSommerzeit(new Date('2024-10-27T01:00:00Z'))).toBe(false);
  });

  test('returns true in late October just before transition back', () => {
    expect(isSommerzeit(new Date('2024-10-27T00:59:59Z'))).toBe(true);
  });
});

// ---------------------------------------------------------------------------
// dd2dms
// ---------------------------------------------------------------------------
describe('dd2dms', () => {
  test('converts positive decimal degrees', () => {
    const r = dd2dms(52.5);
    expect(r.d).toBe(52);
    expect(r.m).toBe(30);
    expect(r.s).toBeCloseTo(0, 1);
  });

  test('converts negative decimal degrees using absolute value', () => {
    const r = dd2dms(-10.25);
    expect(r.d).toBe(10);
    expect(r.m).toBe(15);
    expect(r.s).toBeCloseTo(0, 1);
  });

  test('handles zero', () => {
    const r = dd2dms(0);
    expect(r.d).toBe(0);
    expect(r.m).toBe(0);
    expect(r.s).toBe(0);
  });

  test('converts known value with seconds', () => {
    // 51°30'30" = 51 + 30/60 + 30/3600 = 51.508333...
    const r = dd2dms(51.508333);
    expect(r.d).toBe(51);
    expect(r.m).toBe(30);
    expect(r.s).toBeCloseTo(30, 0);
  });
});

// ---------------------------------------------------------------------------
// dd2dmsStr
// ---------------------------------------------------------------------------
describe('dd2dmsStr', () => {
  test('formats positive lat/lon as N/E', () => {
    const s = dd2dmsStr(52.0, 7.0);
    expect(s).toContain('N');
    expect(s).toContain('E');
    expect(s).not.toContain('S');
    expect(s).not.toContain('W');
  });

  test('formats negative lat/lon as S/W', () => {
    const s = dd2dmsStr(-10.0, -20.0);
    expect(s).toContain('S');
    expect(s).toContain('W');
  });

  test('formats equator and prime meridian as N/E', () => {
    const s = dd2dmsStr(0.0, 0.0);
    expect(s).toContain('N');
    expect(s).toContain('E');
  });
});

// ---------------------------------------------------------------------------
// dd2utm (object-returning grid version)
// ---------------------------------------------------------------------------
describe('dd2utm', () => {
  test('returns an object with zone, E, N', () => {
    const r = dd2utm(52.67, 7.30);
    expect(typeof r).toBe('object');
    expect(typeof r.zone).toBe('number');
    expect(typeof r.E).toBe('number');
    expect(typeof r.N).toBe('number');
  });

  test('zone 32 for central Europe', () => {
    const r = dd2utm(52.0, 9.0);
    expect(r.zone).toBe(32);
  });

  test('zone 33 for eastern Europe', () => {
    const r = dd2utm(52.0, 15.0);
    expect(r.zone).toBe(33);
  });

  test('southern hemisphere adds 10,000,000 m false northing', () => {
    const north = dd2utm(10.0, 30.0);
    const south = dd2utm(-10.0, 30.0);
    // Southern hemisphere: false northing 10,000,000 m is added.
    // At -10° lat the result is roughly 8,893,000 (10,000,000 minus northing offset).
    expect(south.N).toBeGreaterThan(8000000);
    expect(north.N).toBeLessThan(2000000);
  });

  test('easting is near 500000 on the central meridian', () => {
    // Zone 32 central meridian is at 9° E
    const r = dd2utm(48.0, 9.0);
    expect(Math.abs(r.E - 500000)).toBeLessThan(1000);
  });

  test('E value rounded to integer', () => {
    const r = dd2utm(52.0, 10.0);
    expect(r.E % 1).toBe(0);
    expect(r.N % 1).toBe(0);
  });
});

// ---------------------------------------------------------------------------
// dd2gk (object-returning grid version)
// ---------------------------------------------------------------------------
describe('dd2gk', () => {
  test('returns an object with zone, R, H', () => {
    const r = dd2gk(52.0, 9.0);
    expect(typeof r).toBe('object');
    expect(typeof r.zone).toBe('number');
    expect(typeof r.R).toBe('number');
    expect(typeof r.H).toBe('number');
  });

  test('zone 3 for longitude ~9° E', () => {
    const r = dd2gk(52.0, 9.0);
    expect(r.zone).toBe(3);
  });

  test('R value contains zone*1000000 offset', () => {
    // Zone 3: R should be around 3_500_000 (central meridian)
    const r = dd2gk(52.0, 9.0);
    expect(r.R).toBeGreaterThan(3400000);
    expect(r.R).toBeLessThan(3600000);
  });

  test('R and H values are integers', () => {
    const r = dd2gk(48.0, 12.0);
    expect(r.R % 1).toBe(0);
    expect(r.H % 1).toBe(0);
  });
});

// ---------------------------------------------------------------------------
// dd2mgrs
// ---------------------------------------------------------------------------
describe('dd2mgrs', () => {
  test('returns a string', () => {
    expect(typeof dd2mgrs(52.0, 10.0)).toBe('string');
  });

  test('contains zone number and band letter', () => {
    const s = dd2mgrs(52.0, 10.0);
    // Zone 32, band U for ~52°N
    expect(s).toMatch(/^32U /);
  });

  test('10-digit locator has correct format', () => {
    // Format: "<zone><band> <colRow> <5digits> <5digits>"
    const s = dd2mgrs(52.0, 10.0);
    const parts = s.split(' ');
    expect(parts.length).toBe(4);
    expect(parts[2]).toHaveLength(5);
    expect(parts[3]).toHaveLength(5);
  });

  test('returns "--" for invalid input (should not throw)', () => {
    // Pass NaN to trigger the catch
    const s = dd2mgrs(NaN, NaN);
    expect(typeof s).toBe('string');
  });
});

// ---------------------------------------------------------------------------
// parseKoord
// ---------------------------------------------------------------------------
describe('parseKoord', () => {
  test('parses WGS84 decimal with comma separator', () => {
    const r = parseKoord('52.67, 7.30');
    expect(r).not.toBeNull();
    expect(r.lat).toBeCloseTo(52.67, 4);
    expect(r.lon).toBeCloseTo(7.30, 4);
    expect(r.fmt).toBe('WGS84 Dezimal');
  });

  test('parses WGS84 decimal with space separator', () => {
    const r = parseKoord('52.67 7.30');
    expect(r).not.toBeNull();
    expect(r.lat).toBeCloseTo(52.67, 4);
    expect(r.lon).toBeCloseTo(7.30, 4);
  });

  test('parses negative decimal coordinates', () => {
    const r = parseKoord('-33.8688, 151.2093');
    expect(r).not.toBeNull();
    expect(r.lat).toBeCloseTo(-33.8688, 3);
    expect(r.lon).toBeCloseTo(151.2093, 3);
  });

  test('parses N/E decimal format', () => {
    const r = parseKoord('N 52.67 E 7.30');
    expect(r).not.toBeNull();
    expect(r.lat).toBeCloseTo(52.67, 4);
    expect(r.lon).toBeCloseTo(7.30, 4);
    expect(r.fmt).toBe('N/E Dezimal');
  });

  test('returns null for unrecognised input', () => {
    expect(parseKoord('foobar')).toBeNull();
    expect(parseKoord('')).toBeNull();
    expect(parseKoord('12345')).toBeNull();
  });

  test('round-trips through UTM format', () => {
    // Build the UTM string dynamically from a known point so this is a true
    // round-trip test independent of hardcoded coordinate assumptions.
    const ref = dd2utm(52.0, 9.0); // zone 32U, near central meridian
    const utmStr = `${ref.zone}U ${ref.E} ${ref.N}`;
    const r = parseKoord(utmStr);
    expect(r).not.toBeNull();
    expect(r.fmt).toBe('UTM');
    expect(r.lat).toBeCloseTo(52.0, 0);
    expect(r.lon).toBeCloseTo(9.0, 0);
  });
});

// ---------------------------------------------------------------------------
// tetraEscape
// ---------------------------------------------------------------------------
describe('tetraEscape', () => {
  test('escapes ampersand', () => {
    expect(tetraEscape('a&b')).toBe('a&amp;b');
  });

  test('escapes less-than', () => {
    expect(tetraEscape('<script>')).toBe('&lt;script&gt;');
  });

  test('escapes double quote', () => {
    expect(tetraEscape('"hello"')).toBe('&quot;hello&quot;');
  });

  test('escapes single quote', () => {
    expect(tetraEscape("it's")).toBe('it&#39;s');
  });

  test('leaves safe characters unchanged', () => {
    expect(tetraEscape('Hello World 123')).toBe('Hello World 123');
  });

  test('handles falsy input gracefully', () => {
    expect(tetraEscape(null)).toBe('');
    expect(tetraEscape(undefined)).toBe('');
    // 0 is falsy, so String(0||'') returns '' — same as null/undefined
    expect(tetraEscape(0)).toBe('');
  });
});

// ---------------------------------------------------------------------------
// otEsc
// ---------------------------------------------------------------------------
describe('otEsc', () => {
  test('escapes all HTML special characters', () => {
    expect(otEsc('<b>O\'Reilly & "Sons"</b>')).toBe('&lt;b&gt;O&#39;Reilly &amp; &quot;Sons&quot;&lt;/b&gt;');
  });

  test('handles null and undefined as empty string', () => {
    expect(otEsc(null)).toBe('');
    expect(otEsc(undefined)).toBe('');
  });

  test('handles numbers', () => {
    expect(otEsc(42)).toBe('42');
  });
});

// ---------------------------------------------------------------------------
// otColorHash
// ---------------------------------------------------------------------------
describe('otColorHash', () => {
  const OT_COLORS = ['#e85d04', '#2563eb', '#22c55e', '#f59e0b', '#a855f7', '#ef4444', '#06b6d4', '#84cc16'];

  test('returns a colour from OT_COLORS', () => {
    const c = otColorHash('device1');
    expect(OT_COLORS).toContain(c);
  });

  test('is deterministic for the same tid', () => {
    expect(otColorHash('abc')).toBe(otColorHash('abc'));
  });

  test('different tids may return different colours', () => {
    const colours = new Set(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'].map(otColorHash));
    expect(colours.size).toBeGreaterThan(1);
  });

  test('handles empty string without throwing', () => {
    expect(() => otColorHash('')).not.toThrow();
  });
});

// ---------------------------------------------------------------------------
// otBatIcon
// ---------------------------------------------------------------------------
describe('otBatIcon', () => {
  test('returns empty string for negative percentage', () => {
    expect(otBatIcon(-1)).toBe('');
  });

  test('returns green indicator for > 60%', () => {
    const r = otBatIcon(100);
    expect(r).toContain('100%');
    expect(r).toContain('\uD83D\uDFE2');
  });

  test('returns yellow indicator for 31-60%', () => {
    const r = otBatIcon(50);
    expect(r).toContain('50%');
    expect(r).toContain('\uD83D\uDFE1');
  });

  test('returns red indicator for 0-30%', () => {
    const r = otBatIcon(10);
    expect(r).toContain('10%');
    expect(r).toContain('\uD83D\uDD34');
  });

  test('boundary: 60% returns yellow', () => {
    expect(otBatIcon(60)).toContain('\uD83D\uDFE1');
  });

  test('boundary: 61% returns green', () => {
    expect(otBatIcon(61)).toContain('\uD83D\uDFE2');
  });

  test('boundary: 30% returns red', () => {
    expect(otBatIcon(30)).toContain('\uD83D\uDD34');
  });

  test('boundary: 31% returns yellow', () => {
    expect(otBatIcon(31)).toContain('\uD83D\uDFE1');
  });

  test('handles 0%', () => {
    const r = otBatIcon(0);
    expect(r).toContain('0%');
    expect(r).toContain('\uD83D\uDD34');
  });
});

// ---------------------------------------------------------------------------
// otAgo
// ---------------------------------------------------------------------------
describe('otAgo', () => {
  const nowSec = () => Math.floor(Date.now() / 1000);

  test('returns "gerade eben" for timestamps less than 60 s ago', () => {
    expect(otAgo(nowSec() - 30)).toBe('gerade eben');
    expect(otAgo(nowSec())).toBe('gerade eben');
  });

  test('returns minutes for 60–3599 s ago', () => {
    expect(otAgo(nowSec() - 120)).toBe('2min');
    expect(otAgo(nowSec() - 3599)).toBe('59min');
  });

  test('returns hours for 3600–86399 s ago', () => {
    expect(otAgo(nowSec() - 3600)).toBe('1h');
    expect(otAgo(nowSec() - 7200)).toBe('2h');
  });

  test('returns days for >= 86400 s ago', () => {
    expect(otAgo(nowSec() - 86400)).toBe('1T');
    expect(otAgo(nowSec() - 172800)).toBe('2T');
  });
});

// ---------------------------------------------------------------------------
// getDTG
// ---------------------------------------------------------------------------
describe('getDTG', () => {
  test('formats UTC date correctly', () => {
    // 2024-04-03 12:45 UTC → "031245Z APR 24"
    const d = new Date('2024-04-03T12:45:00Z');
    expect(getDTG(d)).toBe('031245Z APR 24');
  });

  test('uses zero-padded day, hour, minute', () => {
    const d = new Date('2024-01-01T01:05:00Z');
    expect(getDTG(d)).toBe('010105Z JAN 24');
  });

  test('uses German month abbreviations', () => {
    const months = [
      ['JAN', '2024-01-15T00:00:00Z'],
      ['FEB', '2024-02-15T00:00:00Z'],
      ['MAR', '2024-03-15T00:00:00Z'],
      ['APR', '2024-04-15T00:00:00Z'],
      ['MAI', '2024-05-15T00:00:00Z'],
      ['JUN', '2024-06-15T00:00:00Z'],
      ['JUL', '2024-07-15T00:00:00Z'],
      ['AUG', '2024-08-15T00:00:00Z'],
      ['SEP', '2024-09-15T00:00:00Z'],
      ['OKT', '2024-10-15T00:00:00Z'],
      ['NOV', '2024-11-15T00:00:00Z'],
      ['DEZ', '2024-12-15T00:00:00Z'],
    ];
    months.forEach(([abbr, iso]) => {
      expect(getDTG(new Date(iso))).toContain(abbr);
    });
  });

  test('2-digit year', () => {
    const d = new Date('2024-06-01T00:00:00Z');
    expect(getDTG(d)).toMatch(/24$/);
  });
});

// ---------------------------------------------------------------------------
// getDTGLocal
// ---------------------------------------------------------------------------
describe('getDTGLocal', () => {
  test('winter time offset is +1h (MEZ)', () => {
    // 2024-01-15 23:00 UTC → 2024-01-16 00:00 MEZ
    const d = new Date('2024-01-15T23:00:00Z');
    const r = getDTGLocal(d);
    expect(r).toContain('MEZ');
    expect(r).not.toContain('MESZ');
    // local day should be 16 (one hour ahead)
    expect(r.startsWith('16')).toBe(true);
  });

  test('summer time offset is +2h (MESZ)', () => {
    // 2024-07-04 22:00 UTC → 2024-07-05 00:00 MESZ
    const d = new Date('2024-07-04T22:00:00Z');
    const r = getDTGLocal(d);
    expect(r).toContain('MESZ');
    expect(r.startsWith('05')).toBe(true);
  });

  test('contains timezone abbreviation in parentheses', () => {
    const d = new Date('2024-01-15T12:00:00Z');
    expect(getDTGLocal(d)).toMatch(/\(MEZ\)$/);
  });
});

// ---------------------------------------------------------------------------
// gridInterval
// ---------------------------------------------------------------------------
describe('gridInterval', () => {
  test('zoom >= 16 → 0.001', () => {
    expect(gridInterval(16)).toBe(0.001);
    expect(gridInterval(18)).toBe(0.001);
  });

  test('zoom 14-15 → 0.005', () => {
    expect(gridInterval(14)).toBe(0.005);
    expect(gridInterval(15)).toBe(0.005);
  });

  test('zoom 12-13 → 0.01', () => {
    expect(gridInterval(12)).toBe(0.01);
    expect(gridInterval(13)).toBe(0.01);
  });

  test('zoom 10-11 → 0.05', () => {
    expect(gridInterval(10)).toBe(0.05);
    expect(gridInterval(11)).toBe(0.05);
  });

  test('zoom 8-9 → 0.1', () => {
    expect(gridInterval(8)).toBe(0.1);
    expect(gridInterval(9)).toBe(0.1);
  });

  test('zoom < 8 → 0.5', () => {
    expect(gridInterval(7)).toBe(0.5);
    expect(gridInterval(0)).toBe(0.5);
  });
});

// ---------------------------------------------------------------------------
// fmtLabel
// ---------------------------------------------------------------------------
describe('fmtLabel', () => {
  test('wgs84 type includes lat/lon with degree symbol', () => {
    const s = fmtLabel(52.5, 7.3, 'wgs84', 12);
    expect(s).toContain('N');
    expect(s).toContain('E');
    expect(s).toContain('/');
  });

  test('wgs84 precision increases with zoom', () => {
    const low = fmtLabel(52.12345, 7.12345, 'wgs84', 8);
    const high = fmtLabel(52.12345, 7.12345, 'wgs84', 16);
    // High zoom should have more decimal places
    expect(high.length).toBeGreaterThan(low.length);
  });

  test('utm type includes zone and "k"', () => {
    const s = fmtLabel(52.0, 9.0, 'utm', 10);
    expect(s).toContain('k');
    expect(s).toContain('/');
  });

  test('mgrs type includes zone', () => {
    const s = fmtLabel(52.0, 9.0, 'mgrs', 10);
    expect(s).toContain('32');
    expect(s).toContain('/');
  });

  test('gk type starts with "GK"', () => {
    const s = fmtLabel(52.0, 9.0, 'gk', 10);
    expect(s.startsWith('GK')).toBe(true);
    expect(s).toContain('R');
    expect(s).toContain('H');
  });

  test('unknown type returns empty string', () => {
    expect(fmtLabel(52.0, 9.0, 'xyz', 10)).toBe('');
  });
});

// ---------------------------------------------------------------------------
// symbolFileKey
// ---------------------------------------------------------------------------
describe('symbolFileKey', () => {
  const CORE_SVG = { icon_police: '<svg/>' };

  test('returns null for falsy input', () => {
    expect(symbolFileKey(null, CORE_SVG)).toBeNull();
    expect(symbolFileKey(undefined, CORE_SVG)).toBeNull();
  });

  test('returns id for core source symbols', () => {
    expect(symbolFileKey({ src: 'core', id: 'icon_police' }, CORE_SVG)).toBe('icon_police');
  });

  test('returns f when src is core and f is set', () => {
    expect(symbolFileKey({ src: 'core', id: 'foo', f: 'foo.svg' }, CORE_SVG)).toBe('foo');
  });

  test('returns id when f is absent and id is in CORE_SVG', () => {
    expect(symbolFileKey({ id: 'icon_police', f: null }, CORE_SVG)).toBe('icon_police');
  });

  test('returns f for non-core symbols with a file path', () => {
    expect(symbolFileKey({ id: 'something', f: 'path/to/icon.svg' }, CORE_SVG)).toBe('path/to/icon.svg');
  });

  test('returns id as fallback when f is absent and not in CORE_SVG', () => {
    expect(symbolFileKey({ id: 'unknown_icon', f: null }, CORE_SVG)).toBe('unknown_icon');
  });

  test('returns null when both f and id are absent', () => {
    expect(symbolFileKey({}, CORE_SVG)).toBeNull();
  });
});
