import { context, transform } from 'esbuild';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { watch } from 'node:fs';
import postcss from 'postcss';
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

const production = process.argv.includes('--production');
const watching = process.argv.includes('--watch');
const cssInput = 'resources/css/plugin.css';
const cssOutput = 'resources/dist/plugin.css';

await mkdir('resources/dist', { recursive: true });

async function buildCss() {
    const result = await postcss([
        tailwindcss('./tailwind.config.js'),
        autoprefixer,
    ]).process(await readFile(cssInput, 'utf8'), { from: cssInput, to: cssOutput });

    const css = production
        ? (await transform(result.css, { loader: 'css', minify: true })).code
        : result.css;

    await writeFile(cssOutput, css);
}

const javascript = await context({
    entryPoints: ['resources/js/plugin.js'],
    outfile: 'resources/dist/plugin.js',
    bundle: true,
    format: 'iife',
    target: ['es2020'],
    minify: production,
    legalComments: 'linked',
});

await Promise.all([javascript.rebuild(), buildCss()]);

if (watching) {
    await javascript.watch();
    let pending = Promise.resolve();
    const rebuildCss = () => {
        pending = pending.then(buildCss).catch(console.error);
    };
    watch('resources/css', { recursive: true }, rebuildCss);
    watch('resources/views', { recursive: true }, rebuildCss);
    watch('tailwind.config.js', rebuildCss);
    console.log('Watching navigation JavaScript, styles, and views...');
} else {
    await javascript.dispose();
}
