const {
  pad,
  isSommerzeit,
  dd2dms,
  dms2dd,
  tetraEscape,
  otColorHash,
  otBatIcon,
  cleanKey,
} = require('./utils');

describe('pad', () => {
  test('links mit Nullen auffuellen', () => {
    expect(pad(7)).toBe('07');
    expect(pad(12)).toBe('12');
  });

  test('negative Zahlen bleiben korrekt', () => {
    expect(pad(-3)).toBe('-03');
  });
});

describe('isSommerzeit (EU-Logik in UTC)', () => {
  test('vor Start im Maerz ist false', () => {
    expect(isSommerzeit(new Date('2026-03-20T12:00:00Z'))).toBe(false);
  });

  test('im Juli ist true', () => {
    expect(isSommerzeit(new Date('2026-07-01T12:00:00Z'))).toBe(true);
  });

  test('nach Ende im November ist false', () => {
    expect(isSommerzeit(new Date('2026-11-01T12:00:00Z'))).toBe(false);
  });
});

describe('dd2dms / dms2dd', () => {
  test('dd2dms erzeugt erwartetes Format', () => {
    expect(dd2dms(52.52, 'lat')).toMatch(/^N\s\d+°\d{2}'\d+\.\d{2}"$/);
    expect(dd2dms(13.405, 'lon')).toMatch(/^E\s\d+°\d{2}'\d+\.\d{2}"$/);
  });

  test('roundtrip bleibt nahe am Ausgangswert', () => {
    const original = 48.137154;
    const dms = dd2dms(original, 'lat');
    const roundtrip = dms2dd(dms);
    expect(Math.abs(roundtrip - original)).toBeLessThan(0.001);
  });

  test('ungueltiges DMS liefert NaN', () => {
    expect(Number.isNaN(dms2dd('foo'))).toBe(true);
  });
});

describe('tetraEscape', () => {
  test('escaped HTML Sonderzeichen', () => {
    expect(tetraEscape('<b>"x" & y</b>')).toBe('&lt;b&gt;&quot;x&quot; &amp; y&lt;/b&gt;');
  });
});

describe('otColorHash', () => {
  test('deterministisch fuer gleiche Eingabe', () => {
    expect(otColorHash('tracker-1')).toBe(otColorHash('tracker-1'));
  });

  test('liefert Hex-Farbe', () => {
    expect(otColorHash('tracker-2')).toMatch(/^#[0-9a-f]{6}$/i);
  });
});

describe('otBatIcon', () => {
  test('kritische Batterie', () => {
    expect(otBatIcon(3)).toBe('critical');
  });

  test('niedrig / mittel / hoch', () => {
    expect(otBatIcon(20)).toBe('low');
    expect(otBatIcon(55)).toBe('mid');
    expect(otBatIcon(88)).toBe('high');
  });

  test('ungueltig -> unknown', () => {
    expect(otBatIcon('x')).toBe('unknown');
  });
});

describe('cleanKey', () => {
  test('entfernt unerlaubte Zeichen', () => {
    expect(cleanKey('../../x?<script>')).toBe('....xscript');
  });

  test('laesst erlaubte Zeichen unveraendert', () => {
    expect(cleanKey('amenity,shop_1-2.3')).toBe('amenity,shop_1-2.3');
  });
});
