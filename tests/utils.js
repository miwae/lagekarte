function pad(value, width = 2) {
  const sign = value < 0 ? '-' : '';
  const abs = String(Math.abs(Number(value)));
  return sign + abs.padStart(width, '0');
}

function lastSundayOfMonthUtc(year, monthIndex) {
  const d = new Date(Date.UTC(year, monthIndex + 1, 0, 1, 0, 0));
  const day = d.getUTCDay();
  d.setUTCDate(d.getUTCDate() - day);
  return d;
}

function isSommerzeit(input) {
  const date = input instanceof Date ? input : new Date(input);
  const year = date.getUTCFullYear();
  const start = lastSundayOfMonthUtc(year, 2); // March
  const end = lastSundayOfMonthUtc(year, 9);   // October
  return date >= start && date < end;
}

function dd2dms(decimal, axis = 'lat') {
  const val = Number(decimal);
  const abs = Math.abs(val);
  const deg = Math.floor(abs);
  const minFloat = (abs - deg) * 60;
  const min = Math.floor(minFloat);
  const sec = (minFloat - min) * 60;
  const hemi = axis === 'lon' ? (val < 0 ? 'W' : 'E') : (val < 0 ? 'S' : 'N');
  return `${hemi} ${deg}°${pad(min, 2)}'${sec.toFixed(2)}\"`;
}

function dms2dd(str) {
  const m = String(str).trim().match(/^([NSEW])\s*(\d+)°\s*(\d+)'\s*(\d+(?:\.\d+)?)\"$/i);
  if (!m) return NaN;
  const hemi = m[1].toUpperCase();
  const deg = Number(m[2]);
  const min = Number(m[3]);
  const sec = Number(m[4]);
  let val = deg + min / 60 + sec / 3600;
  if (hemi === 'S' || hemi === 'W') val *= -1;
  return Number(val.toFixed(8));
}

function tetraEscape(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function otColorHash(input) {
  const colors = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#22c55e', '#14b8a6', '#0ea5e9', '#6366f1'];
  const str = String(input || '');
  let h = 0;
  for (let i = 0; i < str.length; i += 1) {
    h = ((h * 31) + str.charCodeAt(i)) >>> 0;
  }
  return colors[h % colors.length];
}

function otBatIcon(value) {
  const v = Number(value);
  if (!Number.isFinite(v)) return 'unknown';
  if (v <= 5) return 'critical';
  if (v <= 25) return 'low';
  if (v <= 60) return 'mid';
  return 'high';
}

function cleanKey(value) {
  return String(value).replace(/[^a-z0-9_.,\-]/gi, '');
}

module.exports = {
  pad,
  isSommerzeit,
  dd2dms,
  dms2dd,
  tetraEscape,
  otColorHash,
  otBatIcon,
  cleanKey,
};
