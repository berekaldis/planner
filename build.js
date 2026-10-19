/**
 * Kaldis Coffee PLC - Production Asset Builder
 * Minifies CSS & JS, bundles, and synchronizes assets to public/assets
 */

const fs = require('fs');
const path = require('path');

const ROOT = __dirname;
const ASSETS_DIR = path.join(ROOT, 'assets');
const PUBLIC_ASSETS_DIR = path.join(ROOT, 'public', 'assets');

function formatBytes(bytes) {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function ensureDir(dir) {
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
}

function copyFile(src, dest) {
  ensureDir(path.dirname(dest));
  fs.copyFileSync(src, dest);
}

function copyDirRecursive(src, dest) {
  if (!fs.existsSync(src)) return;
  ensureDir(dest);
  const entries = fs.readdirSync(src, { withFileTypes: true });
  for (const entry of entries) {
    const srcPath = path.join(src, entry.name);
    const destPath = path.join(dest, entry.name);
    if (entry.isDirectory()) {
      copyDirRecursive(srcPath, destPath);
    } else {
      fs.copyFileSync(srcPath, destPath);
    }
  }
}

async function buildCSS() {
  const cssSrc = path.join(ASSETS_DIR, 'css', 'style.css');
  const cssMinOut = path.join(ASSETS_DIR, 'css', 'style.min.css');
  const publicCssOut = path.join(PUBLIC_ASSETS_DIR, 'css', 'style.css');
  const publicCssMinOut = path.join(PUBLIC_ASSETS_DIR, 'css', 'style.min.css');

  if (!fs.existsSync(cssSrc)) {
    console.warn('⚠ style.css not found in assets/css');
    return;
  }

  const rawCSS = fs.readFileSync(cssSrc, 'utf8');
  const origSize = Buffer.byteLength(rawCSS, 'utf8');
  let minifiedCSS = '';

  try {
    const CleanCSS = require('clean-css');
    const res = new CleanCSS({
      level: 1,
      inline: false
    }).minify(rawCSS);
    minifiedCSS = res.styles || rawCSS;
  } catch (err) {
    // Fallback simple minification
    minifiedCSS = rawCSS
      .replace(/\/\*[\s\S]*?\*\//g, '')
      .replace(/\s+/g, ' ')
      .replace(/\s*([{}:;,])\s*/g, '$1')
      .replace(/;\}/g, '}')
      .trim();
  }

  fs.writeFileSync(cssMinOut, minifiedCSS, 'utf8');
  copyFile(cssSrc, publicCssOut);
  copyFile(cssMinOut, publicCssMinOut);

  const minSize = Buffer.byteLength(minifiedCSS, 'utf8');
  const savings = (((origSize - minSize) / origSize) * 100).toFixed(1);
  console.log(`✔ CSS: style.css (${formatBytes(origSize)}) -> style.min.css (${formatBytes(minSize)}, -${savings}%)`);
}

async function buildJS() {
  const jsSrc = path.join(ASSETS_DIR, 'js', 'app.js');
  const jsMinOut = path.join(ASSETS_DIR, 'js', 'app.min.js');
  const publicJsOut = path.join(PUBLIC_ASSETS_DIR, 'js', 'app.js');
  const publicJsMinOut = path.join(PUBLIC_ASSETS_DIR, 'js', 'app.min.js');

  if (!fs.existsSync(jsSrc)) {
    console.warn('⚠ app.js not found in assets/js');
    return;
  }

  const rawJS = fs.readFileSync(jsSrc, 'utf8');
  const origSize = Buffer.byteLength(rawJS, 'utf8');
  let minifiedJS = '';

  try {
    const terser = require('terser');
    const res = await terser.minify(rawJS, {
      compress: true,
      mangle: true
    });
    minifiedJS = res.code || rawJS;
  } catch (err) {
    // Fallback safe minification
    minifiedJS = rawJS
      .replace(/\/\*[\s\S]*?\*\//g, '')
      .replace(/^\s*\/\/.*$/gm, '')
      .replace(/\n\s*\n/g, '\n')
      .trim();
  }

  fs.writeFileSync(jsMinOut, minifiedJS, 'utf8');
  copyFile(jsSrc, publicJsOut);
  copyFile(jsMinOut, publicJsMinOut);

  const minSize = Buffer.byteLength(minifiedJS, 'utf8');
  const savings = (((origSize - minSize) / origSize) * 100).toFixed(1);
  console.log(`✔ JS:  app.js (${formatBytes(origSize)}) -> app.min.js (${formatBytes(minSize)}, -${savings}%)`);
}

function syncImages() {
  const imagesSrc = path.join(ASSETS_DIR, 'images');
  const publicImagesDest = path.join(PUBLIC_ASSETS_DIR, 'images');
  copyDirRecursive(imagesSrc, publicImagesDest);
  console.log(`✔ Images synchronized to public/assets/images`);
}

async function main() {
  const startTime = Date.now();
  console.log(`\n☕ Kaldis Coffee PLC &mdash; Production Asset Builder`);
  console.log(`────────────────────────────────────────────────────`);

  await buildCSS();
  await buildJS();
  syncImages();

  const elapsed = Date.now() - startTime;
  console.log(`────────────────────────────────────────────────────`);
  console.log(`✨ Build completed successfully in ${elapsed}ms!\n`);
}

main().catch(err => {
  console.error('Build failed:', err);
  process.exit(1);
});
