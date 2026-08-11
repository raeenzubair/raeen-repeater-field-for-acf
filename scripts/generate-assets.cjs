const sharp = require("sharp");
const { Resvg } = require("@resvg/resvg-js");
const fs = require("fs");
const path = require("path");

const OUT = path.resolve(__dirname, "..", ".wordpress-org");
fs.mkdirSync(OUT, { recursive: true });

// Brand palette (matches the plugin's compiled CSS variables).
const C = {
  border: "#e2e4e7",
  bg: "#ffffff",
  bgRow: "#f9f9f9",
  text: "#1d2327",
  muted: "#646970",
  primary: "#2271b1",
  primaryH: "#135e96",
  danger: "#d63638",
  dangerBg: "#fcf0f1",
  handle: "#c3c4c7",
  adminBg: "#f0f0f1",
  adminDark: "#1d2327",
  adminBorder: "#c3c4c7",
  green: "#46b450",
};

const FONT = "Segoe UI, -apple-system, sans-serif";

// ─────────────────────────────────────────────────────────────
// Icon
// ─────────────────────────────────────────────────────────────
function svgIcon(size) {
  const rows = 4;
  const gap = size * 0.055;
  const rowH = (size * 0.58) / rows;
  const left = size * 0.2;
  const width = size * 0.6;
  let rowMarkup = "";
  for (let r = 0; r < rows; r++) {
    const y = size * 0.21 + r * (rowH + gap);
    rowMarkup += `<circle cx="${size * 0.15}" cy="${y + rowH / 2}" r="${size * 0.016}" fill="#ffffff" opacity="0.85"/>`;
    rowMarkup += `<rect x="${left}" y="${y}" width="${width}" height="${rowH}" rx="${size * 0.018}" fill="#ffffff" opacity="${r === 0 ? 1 : 0.9 - r * 0.12}"/>`;
  }
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
    <defs>
      <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stop-color="#1d6fb8"/>
        <stop offset="1" stop-color="#135e96"/>
      </linearGradient>
    </defs>
    <rect width="${size}" height="${size}" rx="${size * 0.2}" fill="url(#g)"/>
    ${rowMarkup}
  </svg>`;
}

async function renderIcon() {
  for (const size of [128, 256]) {
    await renderSvg(svgIcon(size), path.join(OUT, `icon-${size}x${size}.png`));
  }
}

// ─────────────────────────────────────────────────────────────
// Banner
// ─────────────────────────────────────────────────────────────
function svgBanner(w, h) {
  const leftCol = w * 0.09;
  const rowW = w * 0.42;
  const rows = 3;
  const gap = h * 0.06;
  const rowH = (h * 0.44) / rows;
  let rowsMarkup = "";
  for (let r = 0; r < rows; r++) {
    const y = h * 0.24 + r * (rowH + gap);
    rowsMarkup += `<circle cx="${leftCol + h * 0.04}" cy="${y + rowH / 2}" r="${h * 0.022}" fill="#ffffff" opacity="0.7"/>`;
    rowsMarkup += `<rect x="${leftCol + h * 0.09}" y="${y}" width="${rowW}" height="${rowH}" rx="${h * 0.02}" fill="#ffffff" opacity="${0.95 - r * 0.1}"/>`;
  }
  const fs = Math.round(h * 0.22);
  const fs2 = Math.round(h * 0.075);
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">
    <defs>
      <linearGradient id="g" x1="0" y1="0" x2="1" y2="0">
        <stop offset="0" stop-color="#135e96"/>
        <stop offset="1" stop-color="#2d87d6"/>
      </linearGradient>
    </defs>
    <rect width="${w}" height="${h}" fill="url(#g)"/>
    ${rowsMarkup}
    <text x="${w * 0.55}" y="${h * 0.52}" font-family="${FONT}" font-size="${fs}" font-weight="700" fill="#ffffff">Repeater Field for ACF</text>
    <text x="${w * 0.55}" y="${h * 0.72}" font-family="${FONT}" font-size="${fs2}" fill="#d9e8f7">Table, Block &amp; Row layouts · Drag &amp; drop · Nested repeaters · ACF Pro-compatible</text>
  </svg>`;
}

async function renderBanner() {
  await renderSvg(svgBanner(1544, 500), path.join(OUT, "banner-1544x500.png"));
  await renderSvg(svgBanner(772, 250), path.join(OUT, "banner-772x250.png"));
}

// ─────────────────────────────────────────────────────────────
// Shared admin-chrome mockup helpers
// ─────────────────────────────────────────────────────────────
function adminShell(W, H, { title = "", subtitle = "" } = {}) {
  const barH = H * 0.055;
  const sideW = W * 0.15;
  const labelSize = Math.round(W * 0.0125);
  const titleSize = Math.round(W * 0.022);
  const mutedSize = Math.round(W * 0.012);

  const menuItems = ["Dashboard", "Posts", "Media", "Pages", "Comments", "Appearance", "Plugins", "Users", "Tools", "Settings", "Custom Fields (ACF)"].slice(0, 8);
  let menu = "";
  menuItems.forEach((m, i) => {
    const y = barH + (i + 1) * (H * 0.052);
    const active = m.includes("ACF") || i === 5;
    const bx = W * 0.012;
    const bw = sideW - W * 0.024;
    if (active) {
      menu += `<rect x="${bx}" y="${y}" width="${bw}" height="${H * 0.035}" fill="#135e96"/>`;
    }
    menu += `<text x="${sideW * 0.55}" y="${y + H * 0.02}" font-family="${FONT}" font-size="${labelSize}" fill="#a7aaad" text-anchor="middle">${m}</text>`;
  });

  return `
    <rect width="${W}" height="${H}" fill="${C.adminBg}"/>
    <rect width="${W}" height="${barH}" fill="${C.adminDark}"/>
    <rect x="${W * 0.02}" y="${barH * 0.28}" width="${barH * 2.6}" height="${barH * 0.44}" fill="#135e96" rx="2"/>
    <text x="${barH * 0.5}" y="${barH * 0.62}" font-family="${FONT}" font-size="${barH * 0.42}" fill="#fff" font-weight="600">Raeen Repeater</text>
    <rect width="${sideW}" height="${H}" fill="#1b232e"/>
    ${menu}
    <text x="${W * 0.175}" y="${barH + H * 0.09}" font-family="${FONT}" font-size="${titleSize}" font-weight="600" fill="${C.text}">${title}</text>
    ${subtitle ? `<text x="${W * 0.175}" y="${barH + H * 0.14}" font-family="${FONT}" font-size="${mutedSize}" fill="${C.muted}">${subtitle}</text>` : ""}
  `;
}

function card(x, y, w, h) {
  return `<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${C.bg}" stroke="${C.border}" rx="4"/>`;
}

function field(x, y, w, label, placeholder) {
  const labelSize = Math.round(w * 0.016);
  const inputH = Math.round(w * 0.035);
  return `
    <text x="${x}" y="${y}" font-family="${FONT}" font-size="${labelSize}" font-weight="600" fill="${C.text}">${label}</text>
    <rect x="${x}" y="${y + labelSize * 0.6}" width="${w}" height="${inputH}" fill="${C.bg}" stroke="${C.border}" rx="3"/>
    <text x="${x + Math.round(w * 0.01)}" y="${y + labelSize * 0.6 + inputH * 0.62}" font-family="${FONT}" font-size="${labelSize * 0.85}" fill="#c3c4c7">${placeholder}</text>
  `;
}

function repeaterTable(W, x, y, w, headers, rows) {
  const hh = Math.round(w * 0.033);
  const rowH = Math.round(w * 0.052);
  const handleW = Math.round(w * 0.02);
  let out = "";
  out += `<rect x="${x}" y="${y}" width="${w}" height="${hh + rowH * rows.length}" fill="${C.bg}" stroke="${C.border}" rx="4"/>`;
  // header
  out += `<rect x="${x}" y="${y}" width="${w}" height="${hh}" fill="${C.bgRow}"/>`;
  out += `<rect x="${x}" y="${y}" width="${handleW}" height="${hh}" fill="#ececee"/>`;
  headers.forEach((h, i) => {
    const cw = (w - handleW * 3) / headers.length;
    const hx = x + handleW + i * cw + w * 0.008;
    out += `<text x="${hx}" y="${y + hh * 0.6}" font-family="${FONT}" font-size="${w * 0.0115}" font-weight="600" fill="${C.muted}">${h}</text>`;
  });
  rows.forEach((cols, r) => {
    const ry = y + hh + r * rowH;
    out += `<rect x="${x}" y="${ry}" width="${w}" height="${rowH}" fill="${r % 2 ? C.bgRow : C.bg}"/>`;
    // drag handle dots
    out += `<rect x="${x}" y="${ry}" width="${handleW}" height="${rowH}" fill="#ececee"/>`;
    const dots = [0, 6, 12].map(
      (dy) => `<rect x="${x + handleW * 0.28}" y="${ry + rowH * 0.4 + dy}" width="3" height="3" rx="1" fill="${C.handle}"/>`
    ).join("");
    out += dots;
    // order number
    out += `<text x="${x + handleW * 0.72}" y="${ry + rowH * 0.62}" font-family="${FONT}" font-size="${w * 0.0095}" fill="${C.muted}" text-anchor="middle">${r + 1}</text>`;
    cols.forEach((val, i) => {
      const cw = (w - handleW * 3) / headers.length;
      const hx = x + handleW + i * cw + w * 0.008;
      const iw = cw - w * 0.016;
      out += `<rect x="${hx}" y="${ry + rowH * 0.22}" width="${iw}" height="${rowH * 0.56}" fill="${C.bg}" stroke="${C.border}" rx="3"/>`;
      out += `<text x="${hx + w * 0.008}" y="${ry + rowH * 0.63}" font-family="${FONT}" font-size="${w * 0.0105}" fill="${C.text}">${val}</text>`;
    });
    // remove/duplicate buttons
    const rx2 = x + w - handleW * 1.86;
    out += `<rect x="${rx2}" y="${ry + rowH * 0.24}" width="${handleW}" height="${handleW}" rx="${handleW / 2}" fill="${C.bg}" stroke="${C.border}"/>`;
    out += `<rect x="${rx2 + handleW * 1.15}" y="${ry + rowH * 0.24}" width="${handleW}" height="${handleW}" rx="${handleW / 2}" fill="${C.bg}" stroke="${C.border}"/>`;
  });
  return out;
}

// ─────────────────────────────────────────────────────────────
// Screenshots
// ─────────────────────────────────────────────────────────────
const SW = 1200;
const SH = 900;

async function screenshot1() {
  // Field group editor: Repeater field with sub-fields
  const W = 1200, H = 900;
  let s = adminShell(W, H, { title: "Field Groups · Edit Field Group", subtitle: "Add new group" });
  // field list card
  s += card(W * 0.175, H * 0.19, W * 0.57, H * 0.72);
  s += `<text x="${W * 0.21}" y="${H * 0.24}" font-family="${FONT}" font-size="${Math.round(W * 0.016)}" font-weight="600" fill="${C.text}">Field Group</text>`;
  // fields
  let fy = H * 0.285;
  const fieldRow = (label, type, highlight) => `
    <rect x="${W * 0.21}" y="${fy}" width="${W * 0.5}" height="${H * 0.1}" fill="${highlight ? "#f0f6fc" : C.bg}" stroke="${highlight ? C.primary : C.border}" rx="3"/>
    <text x="${W * 0.222}" y="${fy + H * 0.045}" font-family="${FONT}" font-size="${Math.round(W * 0.0125)}" font-weight="600" fill="${C.text}">${label}</text>
    <text x="${W * 0.222}" y="${fy + H * 0.078}" font-family="${FONT}" font-size="${Math.round(W * 0.0105)}" fill="${C.muted}">${type}</text>
    <text x="${W * 0.675}" y="${fy + H * 0.06}" font-family="${FONT}" font-size="${Math.round(W * 0.012)}" fill="${C.primary}">✎ Edit</text>
  `;
  // repeater block
  s += `<rect x="${W * 0.21}" y="${fy}" width="${W * 0.5}" height="${H * 0.115}" fill="#f6fdf7" stroke="#46b450" rx="3"/>`;
  s += `<text x="${W * 0.222}" y="${fy + H * 0.05}" font-family="${FONT}" font-size="${Math.round(W * 0.013)}" font-weight="700" fill="#2c7a39">Team Members</text>`;
  s += `<text x="${W * 0.222}" y="${fy + H * 0.085}" font-family="${FONT}" font-size="${Math.round(W * 0.011)}" fill="${C.muted}">Repeater · Table layout · 3 sub fields</text>`;
  s += `<text x="${W * 0.665}" y="${fy + H * 0.06}" font-family="${FONT}" font-size="${Math.round(W * 0.012)}" fill="#2c7a39">✎ Edit</text>`;
  fy += H * 0.125;
  // sub fields
  const subFields = ["Name (Text)", "Position (Text)", "Email (Email)", "Website (URL)"];
  subFields.forEach((sf) => {
    s += `<rect x="${W * 0.235}" y="${fy}" width="${W * 0.45}" height="${H * 0.05}" fill="${C.bg}" stroke="${C.border}" rx="3"/>`;
    s += `<text x="${W * 0.248}" y="${fy + H * 0.033}" font-family="${FONT}" font-size="${Math.round(W * 0.011)}" fill="${C.text}">${sf}</text>`;
    fy += H * 0.058;
  });
  s += field(W * 0.235, fy + H * 0.015, W * 0.23, "Layout", "Table");
  s += field(W * 0.48, fy + H * 0.015, W * 0.205, "Button label", "Add Row");
  // right column
  s += card(W * 0.765, H * 0.19, W * 0.21, H * 0.42);
  s += `<text x="${W * 0.785}" y="${H * 0.235}" font-family="${FONT}" font-size="${Math.round(W * 0.0135)}" font-weight="600" fill="${C.text}">Field type</text>`;
  const types = [["Repeater", true], ["Text", false], ["Select", false], ["Group", false], ["Image", false], ["WYSIWYG", false]];
  let ty = H * 0.275;
  types.forEach(([t, sel]) => {
    s += `<rect x="${W * 0.785}" y="${ty}" width="${W * 0.17}" height="${H * 0.032}" fill="${sel ? C.primary : C.bg}" rx="3"/>`;
    s += `<text x="${W * 0.795}" y="${ty + H * 0.022}" font-family="${FONT}" font-size="${Math.round(W * 0.0115)}" fill="${sel ? "#fff" : C.text}">${t}</text>`;
    ty += H * 0.041;
  });
  s += `<rect x="${W * 0.765}" y="${H * 0.66}" width="${W * 0.21}" height="${H * 0.085}" fill="#e5f0f8" stroke="${C.border}" rx="4"/>`;
  s += `<text x="${W * 0.785}" y="${H * 0.69}" font-family="${FONT}" font-size="${Math.round(W * 0.0105)}" font-weight="600" fill="${C.primaryH}">ⓘ PRO Field unlocked</text>`;
  s += `<text x="${W * 0.785}" y="${H * 0.718}" font-family="${FONT}" font-size="${Math.round(W * 0.0095)}" fill="${C.muted}">Repeater now available in ACF Free</text>`;
  // footer buttons
  s += `<rect x="${W * 0.21}" y="${H * 0.895}" width="${W * 0.11}" height="${H * 0.035}" fill="${C.primary}" rx="3"/>`;
  s += `<text x="${W * 0.265}" y="${H * 0.918}" font-family="${FONT}" font-size="${Math.round(W * 0.012)}" fill="#fff" text-anchor="middle">Save Changes</text>`;

  await renderSvg(wrapSvg(s, SW, SH), path.join(OUT, "screenshot-1.png"));
}

async function screenshot2() {
  // Post edit, table layout with rows
  const W = 1200, H = 900;
  let s = adminShell(W, H, { title: "Edit Post · Sample Page", subtitle: "Welcome to WordPress!" });
  s += card(W * 0.175, H * 0.19, W * 0.58, H * 0.74);
  s += `<text x="${W * 0.21}" y="${H * 0.245}" font-family="${FONT}" font-size="${Math.round(W * 0.016)}" font-weight="600" fill="${C.text}">Team Members</text>`;
  const headers = ["Name", "Position", "Email", "Website"];
  const rows = [
    ["Sarah Khan", "Co-founder", "sarah@example.com", "example.com"],
    ["Ali Raza", "Engineer", "ali@example.com", "example.com"],
    ["Mira Ahmed", "Designer", "mira@example.com", "example.com"],
    ["Omar Hashmi", "Marketing", "omar@example.com", "example.com"],
  ];
  s += repeaterTable(W, W * 0.21, H * 0.28, W * 0.5, headers, rows);
  s += `<rect x="${W * 0.21}" y="${H * 0.28 + Math.round(W * 0.033) + Math.round(W * 0.052) * rows.length + H * 0.025}" width="${W * 0.16}" height="${H * 0.035}" fill="${C.primary}" rx="3"/>`;
  s += `<text x="${W * 0.235}" y="${H * 0.845 + H * 0.04}" font-family="${FONT}" font-size="${Math.round(W * 0.0115)}" fill="#fff">＋ Add Row</text>`;
  // right metabox
  s += card(W * 0.775, H * 0.19, W * 0.2, H * 0.18);
  s += `<text x="${W * 0.795}" y="${H * 0.225}" font-family="${FONT}" font-size="${Math.round(W * 0.0125)}" font-weight="600" fill="${C.text}">Publish</text>`;
  s += `<rect x="${W * 0.795}" y="${H * 0.265}" width="${W * 0.16}" height="${H * 0.032}" fill="${C.primary}" rx="2"/>`;
  s += `<text x="${W * 0.875}" y="${H * 0.286}" font-family="${FONT}" font-size="${Math.round(W * 0.011)}" fill="#fff" text-anchor="middle">Update</text>`;
  await renderSvg(wrapSvg(s, SW, SH), path.join(OUT, "screenshot-2.png"));
}

async function screenshot3() {
  // Block (card) layout
  const W = 1200, H = 900;
  let s = adminShell(W, H, { title: "Edit Post · Sample Page", subtitle: "Testimonials" });
  s += card(W * 0.175, H * 0.19, W * 0.58, H * 0.74);
  s += `<text x="${W * 0.21}" y="${H * 0.245}" font-family="${FONT}" font-size="${Math.round(W * 0.016)}" font-weight="600" fill="${C.text}">Testimonials</text>`;
  s += `<text x="${W * 0.21}" y="${H * 0.275}" font-family="${FONT}" font-size="${Math.round(W * 0.011)}" fill="${C.muted}">Block layout · card style</text>`;

  const cardDefs = [["Sarah Khan", "Co-founder @ Nice Co", "Lorem ipsum dolor sit amet, consectetur adipiscing elit.", 5], ["Ali Raza", "Engineer", "Praesent commodo cursus magna, vel scelerisque nisl.", 4]];
  let cy = H * 0.31;
  cardDefs.forEach(([name, role, quote, stars]) => {
    s += `<rect x="${W * 0.21}" y="${cy}" width="${W * 0.5}" height="${H * 0.2}" fill="${C.bg}" stroke="${C.border}" rx="5"/>`;
    s += `<text x="${W * 0.235}" y="${cy + H * 0.05}" font-family="${FONT}" font-size="${Math.round(W * 0.013)}" font-weight="700" fill="${C.text}">${name}</text>`;
    s += `<text x="${W * 0.235}" y="${cy + H * 0.085}" font-family="${FONT}" font-size="${Math.round(W * 0.0105)}" fill="${C.muted}">${role}</text>`;
    s += `<text x="${W * 0.235}" y="${cy + H * 0.14}" font-family="${FONT}" font-size="${Math.round(W * 0.011)}" fill="${C.text}">${quote}</text>`;
    s += `<text x="${W * 0.235}" y="${cy + H * 0.175}" font-family="${FONT}" font-size="${Math.round(W * 0.011)}" fill="#f0ad4e">${"★".repeat(stars)}${"☆".repeat(5 - stars)}</text>`;
    // remove btn
    s += `<rect x="${W * 0.665}" y="${cy + H * 0.02}" width="${Math.round(W * 0.016)}" height="${Math.round(W * 0.016)}" rx="${Math.round(W * 0.008)}" fill="${C.bg}" stroke="${C.border}"/>`;
    s += `<rect x="${W * 0.665}" y="${cy + H * 0.045}" width="${Math.round(W * 0.016)}" height="${Math.round(W * 0.016)}" rx="${Math.round(W * 0.008)}" fill="${C.bg}" stroke="${C.border}"/>`;
    cy += H * 0.235;
  });
  s += `<rect x="${W * 0.21}" y="${cy}" width="${W * 0.16}" height="${H * 0.035}" fill="${C.primary}" rx="3"/>`;
  s += `<text x="${W * 0.235}" y="${cy + H * 0.024}" font-family="${FONT}" font-size="${Math.round(W * 0.0115)}" fill="#fff">＋ Add Testimonial</text>`;
  s += card(W * 0.775, H * 0.19, W * 0.2, H * 0.18);
  s += `<text x="${W * 0.795}" y="${H * 0.225}" font-family="${FONT}" font-size="${Math.round(W * 0.0125)}" font-weight="600" fill="${C.text}">Publish</text>`;
  await renderSvg(wrapSvg(s, SW, SH), path.join(OUT, "screenshot-3.png"));
}

async function screenshot4() {
  // Settings page
  const W = 1200, H = 900;
  let s = adminShell(W, H, { title: "Repeater Field for ACF", subtitle: "Settings" });
  s += card(W * 0.175, H * 0.19, W * 0.58, H * 0.72);
  let y = H * 0.24;
  const row = (label, desc, on) => {
    let out = "";
    out += `<text x="${W * 0.21}" y="${y}" font-family="${FONT}" font-size="${Math.round(W * 0.013)}" font-weight="600" fill="${C.text}">${label}</text>`;
    if (desc) {
      out += `<text x="${W * 0.21}" y="${y + H * 0.025}" font-family="${FONT}" font-size="${Math.round(W * 0.0105)}" fill="${C.muted}">${desc}</text>`;
    }
    const tx = W * 0.68;
    const tw = W * 0.045;
    out += `<rect x="${tx}" y="${y - H * 0.012}" width="${tw * 1.9}" height="${tw}" rx="${tw / 2}" fill="${on ? C.green : "#c8ced4"}"/>`;
    out += `<circle cx="${on ? tx + tw * 1.9 - tw * 0.85 : tx + tw * 0.85}" cy="${y + tw * 0.38}" r="${tw * 0.62}" fill="#fff"/>`;
    return out;
  };
  s += row("Enable drag &amp; drop sorting", "Reorder rows by dragging the handle", true);
  y += H * 0.055;
  s += row("Confirm before deleting rows", "Show a confirmation prompt on row removal", true);
  y += H * 0.055;
  s += row("Allow duplicating rows", "Add a duplicate button to each row", true);
  y += H * 0.055;
  s += row("Nested repeaters", "Allow repeaters inside repeaters", true);
  y += H * 0.06;
  s += `<line x1="${W * 0.21}" y1="${y}" x2="${W * 0.71}" y2="${y}" stroke="${C.border}" stroke-width="1"/>`;
  y += H * 0.025;
  // Layout options
  s += `<text x="${W * 0.21}" y="${y}" font-family="${FONT}" font-size="${Math.round(W * 0.0125)}" font-weight="700" fill="${C.primaryH}">Layout options</text>`;
  y += H * 0.045;
  const layouts = [["Table", "Columns like a spreadsheet", true], ["Block", "Card-based presentation", false], ["Row", "Stacked rows with full-width fields", false]];
  layouts.forEach(([l, d, sel]) => {
    s += `<rect x="${W * 0.21}" y="${y - H * 0.016}" width="${Math.round(W * 0.02)}" height="${Math.round(W * 0.02)}" rx="${Math.round(W * 0.01)}" fill="${sel ? C.primary : C.bg}" stroke="${sel ? C.primary : C.border}"/>`;
    s += `<text x="${W * 0.245}" y="${y}" font-family="${FONT}" font-size="${Math.round(W * 0.0115)}" font-weight="600" fill="${C.text}">${l}</text>`;
    s += `<text x="${W * 0.245}" y="${y + H * 0.023}" font-family="${FONT}" font-size="${Math.round(W * 0.0102)}" fill="${C.muted}">${d}</text>`;
    y += H * 0.05;
  });
  s += `<rect x="${W * 0.21}" y="${H * 0.865}" width="${W * 0.11}" height="${H * 0.035}" fill="${C.primary}" rx="3"/>`;
  s += `<text x="${W * 0.265}" y="${H * 0.888}" font-family="${FONT}" font-size="${Math.round(W * 0.012)}" fill="#fff" text-anchor="middle">Save Settings</text>`;
  await renderSvg(wrapSvg(s, SW, SH), path.join(OUT, "screenshot-4.png"));
}

async function screenshot5() {
  // Row layout with WYSIWYG (rich fields stacked)
  const W = 1200, H = 900;
  let s = adminShell(W, H, { title: "Edit Post · Products", subtitle: "Featured" });
  s += card(W * 0.175, H * 0.19, W * 0.58, H * 0.74);
  s += `<text x="${W * 0.21}" y="${H * 0.245}" font-family="${FONT}" font-size="${Math.round(W * 0.016)}" font-weight="600" fill="${C.text}">Featured Products</text>`;
  s += `<text x="${W * 0.21}" y="${H * 0.272}" font-family="${FONT}" font-size="${Math.round(W * 0.011)}" fill="${C.muted}">Row layout · auto-detects rich fields</text>`;

  const rows = [["Product One", "WYSIWYG editor with full formatting toolbar"], ["Product Two", "WYSIWYG editor with full formatting toolbar"]];
  let ry = H * 0.31;
  rows.forEach(([title, wdesc]) => {
    // row container
    s += `<rect x="${W * 0.21}" y="${ry}" width="${W * 0.5}" height="${H * 0.2}" fill="${C.bg}" stroke="${C.border}" rx="4"/>`;
    // handle + order
    s += `<rect x="${W * 0.21}" y="${ry}" width="${W * 0.015}" height="${H * 0.2}" fill="#ececee"/>`;
    s += `<text x="${W * 0.21 + W * 0.0075}" y="${ry + H * 0.025}" font-family="${FONT}" font-size="${Math.round(W * 0.0095)}" fill="${C.muted}" text-anchor="middle">1</text>`;
    s += `<text x="${W * 0.235}" y="${ry + H * 0.045}" font-family="${FONT}" font-size="${Math.round(W * 0.013)}" font-weight="600" fill="${C.text}">${title}</text>`;
    // toolbar imitation
    const toolH = H * 0.03;
    s += `<rect x="${W * 0.235}" y="${ry + H * 0.07}" width="${W * 0.45}" height="${toolH}" fill="${C.bgRow}" stroke="${C.border}" rx="3"/>`;
    const tools = ["B", "I", "U", "⌘", "•"];
    tools.forEach((t, i) => {
      s += `<text x="${W * 0.245 + i * W * 0.018}" y="${ry + H * 0.093}" font-family="${FONT}" font-size="${Math.round(W * 0.0105)}" font-weight="${t === "B" ? 700 : 400}" fill="${C.text}">${t}</text>`;
    });
    s += `<rect x="${W * 0.235}" y="${ry + H * 0.103}" width="${W * 0.45}" height="${H * 0.045}" fill="${C.bg}" stroke="${C.border}" rx="3"/>`;
    s += `<text x="${W * 0.242}" y="${ry + H * 0.13}" font-family="${FONT}" font-size="${Math.round(W * 0.0095)}" fill="#c3c4c7">${wdesc}</text>`;
    // remove/dup
    s += `<rect x="${W * 0.665}" y="${ry + H * 0.015}" width="${Math.round(W * 0.016)}" height="${Math.round(W * 0.016)}" rx="${Math.round(W * 0.008)}" fill="${C.bg}" stroke="${C.border}"/>`;
    s += `<rect x="${W * 0.665}" y="${ry + H * 0.045}" width="${Math.round(W * 0.016)}" height="${Math.round(W * 0.016)}" rx="${Math.round(W * 0.008)}" fill="${C.bg}" stroke="${C.border}"/>`;
    ry += H * 0.22;
  });
  s += `<rect x="${W * 0.21}" y="${ry}" width="${W * 0.16}" height="${H * 0.035}" fill="${C.primary}" rx="3"/>`;
  s += `<text x="${W * 0.235}" y="${ry + H * 0.024}" font-family="${FONT}" font-size="${Math.round(W * 0.0115)}" fill="#fff">＋ Add Product</text>`;
  await renderSvg(wrapSvg(s, SW, SH), path.join(OUT, "screenshot-5.png"));
}

async function renderSvg(svg, outPath) {
  const widthMatch = svg.match(/<svg[^>]*width="([\d.]+)"/);
  const svgOnly = svg.replace(/<(\?xml[^>]*\?>)/, "");
  const resvg = new Resvg(svgOnly, {
    fitTo: { mode: "width", value: parseFloat(widthMatch[1]) },
  });
  const pngData = resvg.render();
  await sharp(pngData.asPng()).png().toFile(outPath);
  console.log("✓ " + path.basename(outPath));
}

function wrapSvg(content, w, h) {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">${content}</svg>`;
}

(async () => {
  await renderIcon();
  await renderBanner();
  await screenshot1();
  await screenshot2();
  await screenshot3();
  await screenshot4();
  await screenshot5();
  // High-DPI screenshot variants used by WP.org for retina (optional)
  for (const file of ["screenshot-1", "screenshot-2", "screenshot-3", "screenshot-4", "screenshot-5"]) {
    const src = path.join(OUT, `${file}.png`);
    await sharp(src).resize(2400, 1800).png().toFile(path.join(OUT, `${file}-2x.png`));
    console.log("✓ " + path.basename(`${file}-2x.png`));
  }
  console.log("Done. Assets written to " + OUT);
})();