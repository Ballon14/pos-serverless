import { deflateSync } from 'node:zlib';
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const outDir = join(root, 'public', 'icons');
mkdirSync(outDir, { recursive: true });

const CRC_TABLE = (() => {
    const table = new Uint32Array(256);
    for (let n = 0; n < 256; n++) {
        let c = n;
        for (let k = 0; k < 8; k++) {
            c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
        }
        table[n] = c >>> 0;
    }
    return table;
})();

function crc32(buf) {
    let c = 0xffffffff;
    for (let i = 0; i < buf.length; i++) {
        c = CRC_TABLE[(c ^ buf[i]) & 0xff] ^ (c >>> 8);
    }
    return (c ^ 0xffffffff) >>> 0;
}

function chunk(type, data) {
    const len = Buffer.alloc(4);
    len.writeUInt32BE(data.length);
    const typeBuf = Buffer.from(type, 'ascii');
    const crcBuf = Buffer.alloc(4);
    crcBuf.writeUInt32BE(crc32(Buffer.concat([typeBuf, data])));
    return Buffer.concat([len, typeBuf, data, crcBuf]);
}

function encodePng(width, height, rgba) {
    const signature = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);
    const ihdr = Buffer.alloc(13);
    ihdr.writeUInt32BE(width, 0);
    ihdr.writeUInt32BE(height, 4);
    ihdr[8] = 8;
    ihdr[9] = 6;
    const raw = Buffer.alloc((width * 4 + 1) * height);
    for (let y = 0; y < height; y++) {
        raw[y * (width * 4 + 1)] = 0;
        rgba.copy(raw, y * (width * 4 + 1) + 1, y * width * 4, (y + 1) * width * 4);
    }
    return Buffer.concat([
        signature,
        chunk('IHDR', ihdr),
        chunk('IDAT', deflateSync(raw, { level: 9 })),
        chunk('IEND', Buffer.alloc(0)),
    ]);
}

const lerp = (a, b, t) => a + (b - a) * t;
const clamp01 = (v) => Math.min(1, Math.max(0, v));

function sdRoundRect(px, py, x, y, w, h, r) {
    const qx = Math.abs(px - (x + w / 2)) - (w / 2 - r);
    const qy = Math.abs(py - (y + h / 2)) - (h / 2 - r);
    const ox = Math.max(qx, 0);
    const oy = Math.max(qy, 0);
    return Math.hypot(ox, oy) + Math.min(Math.max(qx, qy), 0) - r;
}

function sdCircle(px, py, cx, cy, r) {
    return Math.hypot(px - cx, py - cy) - r;
}

function sdRect(px, py, x, y, w, h) {
    const qx = Math.abs(px - (x + w / 2)) - w / 2;
    const qy = Math.abs(py - (y + h / 2)) - h / 2;
    return Math.hypot(Math.max(qx, 0), Math.max(qy, 0)) + Math.min(Math.max(qx, qy), 0);
}

const INDIGO = [99, 102, 241];
const PURPLE = [139, 92, 246];
const WHITE = [255, 255, 255];

function coverage(d) {
    return clamp01(0.5 - d);
}

function sample(px, py, opts) {
    const { size, maskable } = opts;
    const s = size;
    const u = px / s;
    const v = py / s;

    const bg = maskable
        ? sdRect(px, py, 0, 0, s, s)
        : sdRoundRect(px, py, 0, 0, s, s, s * 0.19);

    if (bg > 0) return [0, 0, 0, 0];

    const g = v * 0.55 + 0.15;
    let r = lerp(INDIGO[0], PURPLE[0], g);
    let g2 = lerp(INDIGO[1], PURPLE[1], g);
    let b = lerp(INDIGO[2], PURPLE[2], g);

    const scale = maskable ? 0.62 : 0.78;
    const cx = s / 2;
    const cy = s / 2;
    const bw = 252 * scale;
    const bh = 150 * scale;
    const bx = cx - bw / 2;
    const by = cy - bh / 2;

    const basket = sdRoundRect(px, py, bx, by, bw, bh, 24 * scale);
    const handle = sdRoundRect(px, py, bx, by - bh * 0.62, 24 * scale, bh * 0.66, 12 * scale);
    const wheelL = sdCircle(px, py, bx + 64 * scale, by + bh + 32 * scale, 24 * scale);
    const wheelR = sdCircle(px, py, bx + bw - 64 * scale, by + bh + 32 * scale, 24 * scale);

    const cart = Math.min(basket, handle, wheelL, wheelR);
    const cartCov = coverage(cart);
    r = lerp(r, WHITE[0], cartCov);
    g2 = lerp(g2, WHITE[1], cartCov);
    b = lerp(b, WHITE[2], cartCov);

    if (!maskable) {
        const pr = 30 * scale;
        const pcx = cx + bw / 2 + 14 * scale;
        const pcy = cy - bh / 2 - 8 * scale;
        const badge = sdCircle(px, py, pcx, pcy, pr);
        const badgeCov = coverage(badge);
        r = lerp(r, WHITE[0], badgeCov);
        g2 = lerp(g2, WHITE[1], badgeCov);
        b = lerp(b, WHITE[2], badgeCov);

        const plusW = 26 * scale;
        const plusT = 9 * scale;
        const plusH = sdRect(px, py, pcx - plusW / 2, pcy - plusT / 2, plusW, plusT);
        const plusV = sdRect(px, py, pcx - plusT / 2, pcy - plusW / 2, plusT, plusW);
        const plusCov = coverage(Math.min(plusH, plusV));
        r = lerp(r, INDIGO[0], plusCov);
        g2 = lerp(g2, INDIGO[1], plusCov);
        b = lerp(b, INDIGO[2], plusCov);
    }

    return [Math.round(r), Math.round(g2), Math.round(b), 255];
}

function render(size, opts) {
    const rgba = Buffer.alloc(size * size * 4);
    const ss = 4;
    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            let acc = [0, 0, 0, 0];
            for (let sy = 0; sy < ss; sy++) {
                for (let sx = 0; sx < ss; sx++) {
                    const [r, g, b, a] = sample(x + (sx + 0.5) / ss, y + (sy + 0.5) / ss, opts);
                    acc[0] += r;
                    acc[1] += g;
                    acc[2] += b;
                    acc[3] += a;
                }
            }
            const n = ss * ss;
            const idx = (y * size + x) * 4;
            rgba[idx] = Math.round(acc[0] / n);
            rgba[idx + 1] = Math.round(acc[1] / n);
            rgba[idx + 2] = Math.round(acc[2] / n);
            rgba[idx + 3] = Math.round(acc[3] / n);
        }
    }
    return encodePng(size, size, rgba);
}

writeFileSync(join(outDir, 'icon-192.png'), render(192, { size: 192, maskable: false }));
writeFileSync(join(outDir, 'icon-512.png'), render(512, { size: 512, maskable: false }));
writeFileSync(join(outDir, 'icon-maskable-512.png'), render(512, { size: 512, maskable: true }));
writeFileSync(join(outDir, 'apple-touch-icon.png'), render(180, { size: 180, maskable: false }));

const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#6366f1"/>
      <stop offset="1" stop-color="#8b5cf6"/>
    </linearGradient>
  </defs>
  <rect width="512" height="512" rx="97" fill="url(#g)"/>
  <path fill="#fff" d="M143 163h24v61c0 15 12 27 27 27h124c15 0 27-12 27-27v-61h24v61c0 28-23 51-51 51H194c-28 0-51-23-51-51v-61z"/>
  <circle cx="200" cy="312" r="19" fill="#fff"/>
  <circle cx="312" cy="312" r="19" fill="#fff"/>
  <circle cx="378" cy="137" r="30" fill="#fff"/>
  <rect x="361" y="128" width="34" height="18" rx="9" fill="#6366f1"/>
  <rect x="368" y="121" width="18" height="32" rx="9" fill="#6366f1"/>
</svg>`;
writeFileSync(join(outDir, 'icon.svg'), svg);

console.log('Icons generated in public/icons/');
