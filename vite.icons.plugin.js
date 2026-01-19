import fs from 'fs/promises';
import path from 'path';
import { glob } from 'glob';
import { getIconsCSS } from '@iconify/utils';

async function collectIconUsage() {
  const root = process.cwd();
  const files = await glob(
    [
      'resources/**/*.{blade.php,php,js,jsx,ts,tsx,vue,json,html,md,scss,css}',
      'resources/**/menu/**/*.json'
    ],
    { cwd: root, nodir: true, ignore: ['resources/assets/vendor/fonts/**'] }
  );

  const icons = new Set();
  const tablerClassRegex = /tabler-([a-z0-9-]+)/gi;
  const iconAttrRegex = /tabler:([a-z0-9-]+)/gi;

  for (const relativePath of files) {
    try {
      const filePath = path.resolve(root, relativePath);
      const content = await fs.readFile(filePath, 'utf8');

      let match;
      while ((match = tablerClassRegex.exec(content)) !== null) {
        icons.add(match[1]);
      }

      while ((match = iconAttrRegex.exec(content)) !== null) {
        icons.add(match[1]);
      }
    } catch (error) {
      console.warn(`WARNING: Failed to scan icon usage in ${relativePath}: ${error.message}`);
    }
  }

  try {
    const manualListPath = path.resolve(root, 'resources/iconify.extra.json');
    const manualRaw = await fs.readFile(manualListPath, 'utf8');
    const manualIcons = JSON.parse(manualRaw);
    if (Array.isArray(manualIcons)) {
      manualIcons.forEach(icon => icons.add(icon));
    }
  } catch (error) {
    if (error.code !== 'ENOENT') {
      console.warn('WARNING: Could not load resources/iconify.extra.json:', error.message);
    }
  }

  return Array.from(icons);
}

export default function iconifyPlugin() {
  return {
    name: 'vite-iconify-plugin',
    apply: 'build',

    async buildStart() {
      console.log('INFO: Generating iconify CSS file...');

      try {
        const iconSetPaths = [path.resolve(process.cwd(), 'node_modules/@iconify/json/json/tabler.json')];
        const usedIcons = await collectIconUsage();
        console.log(`INFO: Detected ${usedIcons.length} unique tabler icons in project sources.`);

        const iconSets = await Promise.all(
          iconSetPaths.map(async filePath => {
            const data = await fs.readFile(filePath, 'utf-8');
            return JSON.parse(data);
          })
        );

        const cssChunks = iconSets.map(iconSet => {
          const availableIcons = new Set(Object.keys(iconSet.icons));
          const requestedIcons = usedIcons.filter(name => availableIcons.has(name));

          if (requestedIcons.length === 0) {
            console.warn(`WARNING: No icon usage detected for prefix "${iconSet.prefix}". Falling back to full icon set.`);
          }

          const iconsToEmit = requestedIcons.length > 0 ? requestedIcons : Array.from(availableIcons);

          const missing = usedIcons.filter(name => !availableIcons.has(name));
          if (missing.length > 0) {
            console.warn(
              `WARNING: ${missing.length} icon(s) referenced but not found in the ${iconSet.prefix} set: ${missing.join(', ')}`
            );
          }

          console.log(`INFO: Emitting ${iconsToEmit.length} icon(s) from the ${iconSet.prefix} set (of ${availableIcons.size}).`);

          return getIconsCSS(iconSet, iconsToEmit, {
            iconSelector: '.{prefix}-{name}',
            commonSelector: '.ti',
            format: 'expanded'
          });
        });

        const allIcons = cssChunks.join('\n');

        const outputPath = path.resolve(process.cwd(), 'resources/assets/vendor/fonts/iconify/iconify.css');
        const dir = path.dirname(outputPath);
        await fs.mkdir(dir, { recursive: true });
        await fs.writeFile(outputPath, allIcons, 'utf8');

        console.log(`SUCCESS: Iconify CSS generated at: ${outputPath}`);

        const additionalFiles = [
          {
            name: 'fontawesome',
            filesPath: path.resolve(process.cwd(), 'node_modules/@fortawesome/fontawesome-free/webfonts'),
            destPath: path.resolve(process.cwd(), 'resources/assets/vendor/fonts/fontawesome')
          },
          {
            name: 'flags',
            filesPath: path.resolve(process.cwd(), 'node_modules/flag-icons/flags'),
            destPath: path.resolve(process.cwd(), 'resources/assets/vendor/fonts/flags')
          }
        ];

        for (const file of additionalFiles) {
          await fs.mkdir(file.destPath, { recursive: true });
          const items = await fs.readdir(file.filesPath, { withFileTypes: true });
          for (const item of items) {
            const srcPath = path.join(file.filesPath, item.name);
            const destPath = path.join(file.destPath, item.name);
            if (item.isDirectory()) {
              await fs.mkdir(destPath, { recursive: true });
              const subItems = await fs.readdir(srcPath);
              for (const subItem of subItems) {
                await fs.copyFile(path.join(srcPath, subItem), path.join(destPath, subItem));
              }
            } else {
              await fs.copyFile(srcPath, destPath);
            }
          }
        }
      } catch (error) {
        console.error('ERROR: Error generating Iconify CSS or copying additional files:', error);
      }
    }
  };
}
