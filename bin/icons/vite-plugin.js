import fs from 'node:fs/promises';
import { generateAppleTouchIcon } from './generate-apple-touch-icon.js';
import { generateWebIcons } from './generate-web-icons.js';

const CONFIG_PATH = 'resources/branding/icon-config.json';

export function iconGenerationPlugin() {
    let viteMode;

    return {
        name: 'bloom-icon-generation',
        configResolved(config) {
            viteMode = config.mode;
        },
        async buildStart() {
            const iconConfig = JSON.parse(await fs.readFile(CONFIG_PATH, 'utf-8'));
            const backgroundColor = iconConfig.backgroundColors?.[viteMode] ?? iconConfig.backgroundColor;
            const config = { ...iconConfig, backgroundColor };

            await generateWebIcons(config);
            await generateAppleTouchIcon(config);
        },
    };
}
