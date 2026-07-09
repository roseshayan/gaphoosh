const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const fontDir = path.join(root, 'public', 'assets', 'fonts');
fs.mkdirSync(fontDir, { recursive: true });

const sourceDir = path.join(root, 'node_modules', '@fontsource', 'vazirmatn', 'files');
if (fs.existsSync(sourceDir)) {
  const files = fs.readdirSync(sourceDir).filter((file) => file.endsWith('.woff2'));
  const regular = files.find((file) => /400-normal/.test(file)) || files.find((file) => /400/.test(file));
  const bold = files.find((file) => /700-normal/.test(file)) || files.find((file) => /700/.test(file));
  if (regular) fs.copyFileSync(path.join(sourceDir, regular), path.join(fontDir, 'vazirmatn-regular.woff2'));
  if (bold) fs.copyFileSync(path.join(sourceDir, bold), path.join(fontDir, 'vazirmatn-bold.woff2'));
  console.log('Vazir/Vazirmatn font files copied to public/assets/fonts.');
} else {
  console.warn('Font package not found. Run npm install first.');
}

const assets = ['public/assets/app.css', 'public/assets/app.js', 'public/assets/img/logo.png'];
for (const file of assets) {
  const full = path.join(root, file);
  if (!fs.existsSync(full)) throw new Error(`${file} not found`);
  const stat = fs.statSync(full);
  console.log(`${file}: ${(stat.size / 1024).toFixed(1)} KB`);
}
console.log('Assets are local. No CDN required.');
