/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * ESBuild configuration for Yandex SmartCaptcha Yii2 widget.
 *
 * src/ts/index.ts → dist/js/yandex-smart-captcha.js
 *
 * Экспортирует в window:
 *   YandexSmartCaptcha        — менеджер виджетов (register/reset/execute);
 *   __yandexSmartCaptchaOnload — колбэк для ?onload= в captcha.js.
 */

import * as esbuild from 'esbuild';
import { fileURLToPath } from 'url';
import * as path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const isWatch = process.argv.includes('--watch');

const buildOptions = {
    entryPoints: [path.join(__dirname, 'src/ts/index.ts')],
    outfile: path.join(__dirname, 'dist/js/yandex-smart-captcha.js'),
    bundle: true,
    format: 'iife',
    target: 'es2020',
    sourcemap: true,
    minify: true,
    treeShaking: true,
    platform: 'browser',
    tsconfig: path.join(__dirname, 'tsconfig.json'),
    logLevel: 'info',
};

const build = async () => {
    if (isWatch) {
        const ctx = await esbuild.context(buildOptions);
        await ctx.watch();
        console.log('Watching TS... (yandex-smart-captcha.js)');
    } else {
        await esbuild.build(buildOptions);
        console.log('Build completed: dist/js/yandex-smart-captcha.js');
    }
};

build().catch(() => process.exit(1));
