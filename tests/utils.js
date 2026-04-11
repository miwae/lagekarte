/**
 * Pure utility functions extracted from index.server.live.html for unit testing.
 * These functions have no browser/DOM/Leaflet dependencies.
 */

'use strict';

/* ====== Time / Padding ====== */

function pad(n, l) {
  var s = String(Math.floor(n));
  while (s.length < (l || 2)) s = '0' + s;
  return s;
}

/**
 * Returns true when the given Date falls inside Central European Summer Time
 * (MESZ, UTC+2): from the last Sunday of March 01:00 UTC until the last
 * Sunday of October 01:00 UTC.
 */
function isSommerzeit(d) {
  var y = d.getUTCFullYear();
  var mrzEnd = new Date(Date.UTC(y, 2, 31, 1, 0, 0));
  mrzEnd.setUTCDate(31 - mrzEnd.getUTCDay());
  var oktEnd = new Date(Date.UTC(y, 9, 31, 1, 0, 0));
  oktEnd.setUTCDate(31 - oktEnd.getUTCDay());
  return d >= mrzEnd && d < oktEnd;
}

/* ====== Coordinate conversion ====== */

function dd2dms(dd) {
  var d = Math.floor(Math.abs(dd));
  var mf = (Math.abs(dd) - d) * 60;
  var m = Math.floor(mf);
  var s = ((mf - m) * 60).toFixed(1);
  return { d: d, m: m, s: parseFloat(s) };
}

function dd2dmsStr(lat, lon) {
  function pa(n) { return n < 10 ? '0' + n : String(n); }
  var la = dd2dms(lat), lo = dd2dms(lon);
  return la.d + '\xf0' + pa(la.m) + "'" + pa(la.s) + '"' + (lat >= 0 ? 'N' : 'S') + ' ' +
         lo.d + '\xf0' + pa(lo.m) + "'" + pa(lo.s) + '"' + (lon >= 0 ? 'E' : 'W');
}

/**
 * Converts WGS84 decimal degrees to UTM and returns an object with
 * numeric fields { zone, E, N } (grid-layer variant used by dd2mgrs and fmtLabel).
 */
function dd2utm(lat, lon) {
  var a = 6378137, f = 1 / 298.257223563, b = a * (1 - f), e2 = 1 - (b * b) / (a * a);
  var zone = Math.floor((lon + 180) / 6) + 1, lon0 = (zone - 1) * 6 - 180 + 3;
  var latr = lat * Math.PI / 180, lonr = lon * Math.PI / 180, lon0r = lon0 * Math.PI / 180;
  var k0 = 0.9996, E0 = 500000, N0 = lat < 0 ? 10000000 : 0;
  var n = f / (2 - f), A = a / (1 + n) * (1 + n * n / 4 + n * n * n * n / 64);
  var a0 = 1 - n * n / 4 - 3 * n * n * n * n / 64,
      a2 = 3 / 2 * (n - 27 * n * n * n / 32),
      a4 = 15 / 16 * (n * n - n * n * n * n / 4),
      a6 = 35 * n * n * n / 48,
      a8 = 315 * n * n * n * n / 512;
  var sigma = a0 * latr - a2 * Math.sin(2 * latr) + a4 * Math.sin(4 * latr) -
              a6 * Math.sin(6 * latr) + a8 * Math.sin(8 * latr);
  var t = Math.tan(latr), ep2c = e2 / (1 - e2) * Math.cos(latr) * Math.cos(latr), dl = lonr - lon0r;
  var N = a / Math.sqrt(1 - e2 * Math.sin(latr) * Math.sin(latr));
  var E = k0 * N * (dl + Math.pow(dl, 3) / 6 * Math.cos(latr) * Math.cos(latr) * (1 - t * t + ep2c) +
          Math.pow(dl, 5) / 120 * Math.pow(Math.cos(latr), 4) * (5 - 18 * t * t + t * t * t * t + 14 * ep2c - 58 * ep2c * t * t)) + E0;
  var Nv = k0 * (sigma * A + N * Math.sin(latr) * Math.cos(latr) * dl * dl / 2 +
           N * Math.sin(latr) * Math.pow(Math.cos(latr), 3) * Math.pow(dl, 4) / 24 *
           (5 - t * t + 9 * ep2c + 4 * ep2c * ep2c)) + N0;
  return { zone: zone, E: Math.round(E), N: Math.round(Nv) };
}

/**
 * Converts WGS84 decimal degrees to MGRS string, e.g. "32U NB 12345 67890".
 * Depends on the object-returning dd2utm above.
 */
function dd2mgrs(lat, lon) {
  try {
    var u = dd2utm(lat, lon);
    var zoneNum = u.zone, E = u.E, N = u.N;
    var bands = 'CDEFGHJKLMNPQRSTUVWX';
    var bidx = Math.min(Math.max(Math.floor((lat + 80) / 8), 0), 19);
    if (lat >= 72) bidx = 19;
    var band = bands.charAt(bidx);
    var setNum = ((zoneNum - 1) % 6) + 1;
    var colSets = ['ABCDEFGH', 'JKLMNPQR', 'STUVWXYZ', 'ABCDEFGH', 'JKLMNPQR', 'STUVWXYZ'];
    var eCol = Math.floor(E / 100000) - 1;
    if (eCol < 0) eCol = 0;
    if (eCol > 7) eCol = 7;
    var colLetter = colSets[setNum - 1].charAt(eCol) || '?';
    var rowLetters = 'ABCDEFGHJKLMNPQRSTUV';
    var nRow = Math.floor(N / 100000) % 20;
    if (zoneNum % 2 === 0) nRow = (nRow + 5) % 20;
    var rowLetter = rowLetters.charAt(nRow) || '?';
    var eLocal = String(E % 100000).padStart(5, '0');
    var nLocal = String(N % 100000).padStart(5, '0');
    return zoneNum + band + ' ' + colLetter + rowLetter + ' ' + eLocal + ' ' + nLocal;
  } catch (ex) { return '--'; }
}

/**
 * Converts WGS84 decimal degrees to Gauss-Krüger and returns an object
 * with numeric fields { zone, R, H } (grid-layer variant used by fmtLabel).
 */
function dd2gk(lat, lon) {
  var a = 6377397.155, f = 1 / 299.1528128, b = a * (1 - f), e2 = 1 - (b * b) / (a * a);
  var zone = Math.round(lon / 3), lon0 = zone * 3;
  var latr = lat * Math.PI / 180, lonr = lon * Math.PI / 180, lon0r = lon0 * Math.PI / 180, dl = lonr - lon0r;
  var N = a / Math.sqrt(1 - e2 * Math.sin(latr) * Math.sin(latr)), t = Math.tan(latr);
  var A0 = 1 - e2 / 4 - 3 * e2 * e2 / 64, A2 = 3 / 8 * (e2 + e2 * e2 / 4), A4 = 15 * e2 * e2 / 256;
  var M = a * (A0 * latr - A2 * Math.sin(2 * latr) + A4 * Math.sin(4 * latr));
  var E = N * Math.cos(latr) * dl + N * Math.pow(Math.cos(latr), 3) * dl * dl * dl / 6 * (1 - t * t) +
          zone * 1000000 + 500000;
  var Nv = M + N * Math.sin(latr) * Math.cos(latr) * dl * dl / 2;
  return { zone: zone, R: Math.round(E), H: Math.round(Nv) };
}

/**
 * Parses a coordinate string in various formats.
 * Returns { lat, lon, fmt } or null if unrecognised.
 */
function parseKoord(input) {
  input = input.trim();
  var m = input.match(/^(-?\d+\.\d+)[,\s]+(-?\d+\.\d+)$/);
  if (m) return { lat: parseFloat(m[1]), lon: parseFloat(m[2]), fmt: 'WGS84 Dezimal' };
  m = input.match(/(\d+)[ðd\s]+(\d+)['\u2019m\s]+(\d+\.?\d*)["\"s]?\s*([NS])[,\s]+(\d+)[ðd\s]+(\d+)['\u2019m\s]+(\d+\.?\d*)["\"s]?\s*([EW])/i);
  if (m) {
    var lat = (parseInt(m[1]) + parseInt(m[2]) / 60 + parseFloat(m[3]) / 3600) * (m[4].toUpperCase() === 'S' ? -1 : 1);
    var lon = (parseInt(m[5]) + parseInt(m[6]) / 60 + parseFloat(m[7]) / 3600) * (m[8].toUpperCase() === 'W' ? -1 : 1);
    return { lat, lon, fmt: 'WGS84 DMS' };
  }
  m = input.match(/(\d+)[ðd\s]+(\d+\.?\d*)['\u2019m]?\s*([NS])[,\s]+(\d+)[ðd\s]+(\d+\.?\d*)['\u2019m]?\s*([EW])/i);
  if (m) {
    var lat = (parseInt(m[1]) + parseFloat(m[2]) / 60) * (m[3].toUpperCase() === 'S' ? -1 : 1);
    var lon = (parseInt(m[4]) + parseFloat(m[5]) / 60) * (m[6].toUpperCase() === 'W' ? -1 : 1);
    return { lat, lon, fmt: 'WGS84 DM' };
  }
  m = input.match(/^(\d{1,2}[A-Z])\s+(\d{6,7})\s+(\d{6,7})$/i);
  if (m) {
    var utmZ = parseInt(m[1]), utmE = parseInt(m[2]), utmN = parseInt(m[3]);
    var lon0 = (utmZ - 1) * 6 - 180 + 3;
    var k0 = 0.9996, a = 6378137, f = 1 / 298.257223563, b = a * (1 - f), e2 = 1 - (b * b) / (a * a);
    var x = utmE - 500000, y = utmN;
    var M = y / k0, mu = M / (a * (1 - e2 / 4 - 3 * e2 * e2 / 64));
    var e1 = (1 - Math.sqrt(1 - e2)) / (1 + Math.sqrt(1 - e2));
    var phi = mu + (3 * e1 / 2 - 27 * e1 * e1 * e1 / 32) * Math.sin(2 * mu) + (21 * e1 * e1 / 16) * Math.sin(4 * mu);
    var N2 = a / Math.sqrt(1 - e2 * Math.sin(phi) * Math.sin(phi));
    var T = Math.tan(phi) * Math.tan(phi), C = e2 / (1 - e2) * Math.cos(phi) * Math.cos(phi);
    var R = a * (1 - e2) / Math.pow(1 - e2 * Math.sin(phi) * Math.sin(phi), 1.5);
    var D = x / (N2 * k0);
    var lat = phi - (N2 * Math.tan(phi) / R) * (D * D / 2 - (5 + 3 * T + 10 * C - 4 * C * C - 9 * e2 / (1 - e2)) * Math.pow(D, 4) / 24);
    var lon = lon0 * Math.PI / 180 + (D - (1 + 2 * T + C) * D * D * D / 6) / Math.cos(phi);
    return { lat: lat * 180 / Math.PI, lon: lon * 180 / Math.PI, fmt: 'UTM' };
  }
  m = input.match(/N\s*(\d+\.?\d*)\s*E\s*(\d+\.?\d*)/i);
  if (m) return { lat: parseFloat(m[1]), lon: parseFloat(m[2]), fmt: 'N/E Dezimal' };
  return null;
}

/* ====== HTML escaping ====== */

function tetraEscape(s) {
  return String(s || '').replace(/[&<>"']/g, function (ch) {
    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
  });
}

function otEsc(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function (ch) {
    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
  });
}

/* ====== OwnTracks helpers ====== */

var OT_COLORS = ['#e85d04', '#2563eb', '#22c55e', '#f59e0b', '#a855f7', '#ef4444', '#06b6d4', '#84cc16'];

function otColorHash(tid) {
  var h = 0;
  for (var i = 0; i < tid.length; i++) h = (h * 31 + tid.charCodeAt(i)) % OT_COLORS.length;
  return OT_COLORS[h];
}

function otBatIcon(pct) {
  if (pct < 0) return '';
  var icon = pct > 60 ? '\uD83D\uDFE2' : pct > 30 ? '\uD83D\uDFE1' : '\uD83D\uDD34';
  return icon + pct + '%';
}

function otAgo(ts) {
  var s = Math.floor(Date.now() / 1000) - ts;
  if (s < 60) return 'gerade eben';
  if (s < 3600) return Math.floor(s / 60) + 'min';
  if (s < 86400) return Math.floor(s / 3600) + 'h';
  return Math.floor(s / 86400) + 'T';
}

/* ====== Taktische Zeit (DTG) ====== */

function getDTG(date) {
  date = date || new Date();
  var d = String(date.getUTCDate()).padStart(2, '0');
  var h = String(date.getUTCHours()).padStart(2, '0');
  var m = String(date.getUTCMinutes()).padStart(2, '0');
  var months = ['JAN', 'FEB', 'MAR', 'APR', 'MAI', 'JUN', 'JUL', 'AUG', 'SEP', 'OKT', 'NOV', 'DEZ'];
  var mon = months[date.getUTCMonth()];
  var y = String(date.getUTCFullYear()).slice(-2);
  return d + h + m + 'Z ' + mon + ' ' + y;
}

function getDTGLocal(date) {
  date = date || new Date();
  var isMessz = isSommerzeit(date);
  var offset = isMessz ? 2 : 1;
  var local = new Date(date.getTime() + offset * 3600000);
  var d = String(local.getUTCDate()).padStart(2, '0');
  var h = String(local.getUTCHours()).padStart(2, '0');
  var m = String(local.getUTCMinutes()).padStart(2, '0');
  var tz = isMessz ? 'MESZ' : 'MEZ';
  var months = ['JAN', 'FEB', 'MAR', 'APR', 'MAI', 'JUN', 'JUL', 'AUG', 'SEP', 'OKT', 'NOV', 'DEZ'];
  var mon = months[local.getUTCMonth()];
  var y = String(local.getUTCFullYear()).slice(-2);
  return d + h + m + tz.charAt(0) + ' ' + mon + ' ' + y + ' (' + tz + ')';
}

/* ====== Grid helpers ====== */

function gridInterval(zoom) {
  if (zoom >= 16) return 0.001;
  if (zoom >= 14) return 0.005;
  if (zoom >= 12) return 0.01;
  if (zoom >= 10) return 0.05;
  if (zoom >= 8)  return 0.1;
  return 0.5;
}

function fmtLabel(lat, lon, type, zoom) {
  var itvl = gridInterval(zoom);
  if (type === 'wgs84') {
    var dp = itvl < 0.01 ? 3 : itvl < 0.1 ? 2 : 1;
    return lat.toFixed(dp) + '\xf0N / ' + lon.toFixed(dp) + '\xf0E';
  } else if (type === 'utm') {
    var u = dd2utm(lat, lon);
    return u.zone + 'U ' + Math.round(u.E / 1000) + 'k / ' + Math.round(u.N / 1000) + 'k';
  } else if (type === 'mgrs') {
    var u2 = dd2utm(lat, lon);
    return u2.zone + 'U ' + Math.round(u2.E) + ' / ' + Math.round(u2.N);
  } else if (type === 'gk') {
    var g = dd2gk(lat, lon);
    return 'GK' + g.zone + ' R' + Math.round(g.R / 1000) + 'k / H' + Math.round(g.H / 1000) + 'k';
  }
  return '';
}

/* ====== Symbol helpers ====== */

/**
 * Returns the SVG file/cache key for a tactical symbol object.
 * Depends on the CORE_SVG map being available (pass as parameter for testing).
 */
function symbolFileKey(z, CORE_SVG) {
  if (!z) return null;
  if (z.src === 'core') return z.id || z.f;
  if ((!z.f || z.f === null) && z.id && CORE_SVG && CORE_SVG[z.id]) return z.id;
  return z.f || z.id || null;
}

module.exports = {
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
};
