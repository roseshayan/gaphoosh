const fs = require('fs');
const path = require('path');
const root = path.resolve(__dirname, '..');
const files = ['public/assets/app.css', 'public/assets/app.js'];
let ok = true;
for (const file of files) {
  const text = fs.readFileSync(path.join(root, file), 'utf8');
  if (/https?:\/\//i.test(text)) {
    console.error(`External URL found in ${file}`);
    ok = false;
  }
}
if (!fs.existsSync(path.join(root, 'public', 'assets', 'img', 'logo.png'))) {
  console.error('Logo is missing.');
  ok = false;
}
if (!ok) process.exit(1);
console.log('No external CDN URLs found in assets.');
