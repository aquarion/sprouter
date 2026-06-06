#!/usr/bin/env node
/* global process, Buffer */

import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const ICON_DIR = 'resources/branding/bloom.icon';
const JSON_PATH = path.join(ICON_DIR, 'icon.json');
const OUTPUT_PATH = 'resources/icons/apple-touch-icon.png';

function parseP3Color(p3Str) {
    const match = p3Str.match(/display-p3:([\d.]+),([\d.]+),([\d.]+),([\d.]+)/);

    if (!match) {
return { r: 96, g: 33, b: 163 };
}

    const [, r, g, b] = match.map(Number);

    // Apple's icon tool saves the gamma-encoded P3 components directly into the sRGB
    // container without gamut conversion. We replicate that here to match its output.
    return { r: Math.round(r * 255), g: Math.round(g * 255), b: Math.round(b * 255) };
}

async function generate() {
    if (!fs.existsSync(JSON_PATH)) {
        console.error(`Error: ${JSON_PATH} not found`);
        process.exit(1);
    }

    const data = JSON.parse(fs.readFileSync(JSON_PATH, 'utf-8'));
    const p3Color = data.fill?.['automatic-gradient'] || '';
    const rgb = parseP3Color(p3Color);
    const hex = `#${rgb.r.toString(16).padStart(2, '0')}${rgb.g.toString(16).padStart(2, '0')}${rgb.b.toString(16).padStart(2, '0')}`;

    console.log(`Parsed color: ${p3Color} -> ${hex}`);

    const size = 1024;

    // Quintic superellipse (n=5) path generator matching Apple's squircle shape
    const generateSquirclePath = (size, n) => {
        const margin = 0; // Apple's icon squircle has no inset margin
        const r = (size - margin * 2) / 2;
        const center = size / 2;
        let path = `M ${r + center},${center} `;

        for (let i = 0; i <= 360; i++) {
            const angle = (i * Math.PI) / 180;
            const cos = Math.cos(angle);
            const sin = Math.sin(angle);
            const x = Math.pow(Math.abs(cos), 2 / n) * r * Math.sign(cos) + center;
            const y = Math.pow(Math.abs(sin), 2 / n) * r * Math.sign(sin) + center;
            path += `L ${x},${y} `;
        }

        path += 'Z';

        return path;
    };

    const squirclePath = `
        <svg width="${size}" height="${size}" viewBox="0 0 1024 1024">
            <path d="${generateSquirclePath(1024, 5)}" fill="white" />
        </svg>
    `;
    const squircleMask = Buffer.from(squirclePath);

    // Background gradient: Apple's "automatic-gradient" lightens the top by ~40 RGB units.
    // The gradient reaches the base colour at ~70% of the height and stays flat below that.
    const GRADIENT_LIFT = 40;
    const baseColor = `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})`;
    const topColor = `rgb(${Math.min(255, rgb.r + GRADIENT_LIFT)}, ${Math.min(255, rgb.g + GRADIENT_LIFT)}, ${Math.min(255, rgb.b + GRADIENT_LIFT)})`;

    const bgGradient = `
        <svg width="${size}" height="${size}" viewBox="0 0 1024 1024">
            <linearGradient id="grad" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" style="stop-color:${topColor}" />
                <stop offset="70%" style="stop-color:${baseColor}" />
                <stop offset="100%" style="stop-color:${baseColor}" />
            </linearGradient>
            <rect width="1024" height="1024" fill="url(#grad)" />
        </svg>
    `;

    const composites = [];

    // Background with mask
    const bg = await sharp(Buffer.from(bgGradient))
        .composite([{ input: squircleMask, blend: 'dest-in' }])
        .png()
        .toBuffer();
    composites.push({ input: bg, top: 0, left: 0 });

    for (const group of data.groups || []) {
        for (const layer of group.layers || []) {
            const imgPath = path.join(ICON_DIR, 'Assets', layer['image-name']);

            if (!fs.existsSync(imgPath)) {
continue;
}

            const scale = layer.position?.scale || 1.0;
            // In Apple's icon JSON, scale is a "coverage" fraction. Apple's renderer maps
            // this to an effective layer size using a power curve (exponent ~0.35 empirically
            // matches Xcode's output across the observable scale range).
            const renderedScale = Math.pow(scale, 0.35);
            const layerSize = Math.round(size * renderedScale);
            const layerOffset = (size - layerSize) / 2;

            const originalSvg = fs.readFileSync(imgPath, 'utf-8');
            const pathMatch = originalSvg.match(/<path d="([^"]+)"/);

            if (!pathMatch) {
continue;
}

            const d = pathMatch[1];

            // Apple's translucency is a frosted-glass blend, not simple fill-opacity.
            // The interior petal pixels in the reference output match ~0.70 opacity for
            // translucency=0.5; specular highlights then push bright edges toward white.
            const translucencyValue = group.translucency?.enabled ? (group.translucency.value ?? 0.5) : 1.0;
            const layerOpacity = Math.min(1.0, 0.4 + translucencyValue * 0.55);

            const glassGlyphSvg = `
                <svg width="${layerSize}" height="${layerSize}" viewBox="0 0 1200 1200">
                    <defs>
                        <filter id="liquidGlass" x="-15%" y="-15%" width="130%" height="130%">
                            <!-- Outer bloom: narrows to edge only, not flooding the interior -->
                            <feGaussianBlur in="SourceAlpha" stdDeviation="14" result="glowBlur" />
                            <feFlood flood-color="white" flood-opacity="0.3" result="glowFill" />
                            <feComposite in="glowFill" in2="glowBlur" operator="in" result="outerGlow" />

                            <!-- Specular highlight: focused top-left, doesn't wash out interior -->
                            <feGaussianBlur in="SourceAlpha" stdDeviation="16" result="bump" />
                            <feSpecularLighting in="bump" surfaceScale="6" specularConstant="3" specularExponent="25" lighting-color="white" result="spec">
                                <fePointLight x="-300" y="-500" z="900" />
                            </feSpecularLighting>
                            <feComposite in="spec" in2="SourceAlpha" operator="in" result="specLight" />

                            <feMerge>
                                <feMergeNode in="outerGlow" />
                                <feMergeNode in="SourceGraphic" />
                                <feMergeNode in="specLight" />
                            </feMerge>
                        </filter>
                    </defs>
                    <path d="${d}" fill="white" fill-opacity="${layerOpacity}" filter="url(#liquidGlass)" />
                </svg>
            `;

            const layerImg = await sharp(Buffer.from(glassGlyphSvg))
                .png()
                .toBuffer();

            composites.push({ input: layerImg, top: Math.round(layerOffset), left: Math.round(layerOffset) });
        }
    }

    // Inner edge glow: Apple's squircle has a ~20px bright specular highlight along
    // all edges, clipped to the squircle boundary.
    // feMorphology erode creates a border ring; Gaussian blur softens it inward.
    const edgeGlowSvg = `
        <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
            <defs>
                <filter id="edgeGlow" x="0%" y="0%" width="100%" height="100%">
                    <feMorphology in="SourceAlpha" operator="erode" radius="16" result="eroded" />
                    <feComposite in="SourceAlpha" in2="eroded" operator="arithmetic" k2="1" k3="-1" result="ring" />
                    <feGaussianBlur in="ring" stdDeviation="7" result="soft" />
                    <feFlood flood-color="white" flood-opacity="0.6" result="white" />
                    <feComposite in="white" in2="soft" operator="in" result="glow" />
                    <feComposite in="glow" in2="SourceAlpha" operator="in" />
                </filter>
            </defs>
            <path d="${generateSquirclePath(size, 5)}" fill="white" filter="url(#edgeGlow)" />
        </svg>
    `;
    const edgeGlow = await sharp(Buffer.from(edgeGlowSvg)).png().toBuffer();
    composites.push({ input: edgeGlow, top: 0, left: 0 });

    // Directional corner specular: Apple's TL corner has a stronger, crisper highlight.
    // surfaceScale=51 compensates for librsvg normalising bump gradients by 255.
    // Low lz=80 makes n=(0,0,1) interior near-zero; outward TL corner normals align
    // with the light, creating a crisp highlight that zeroes out at TR/BR corners.
    const cornerSpecSvg = `
        <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
            <defs>
                <filter id="cornerSpec" x="0%" y="0%" width="100%" height="100%">
                    <feGaussianBlur in="SourceAlpha" stdDeviation="12" result="bump" />
                    <feSpecularLighting in="bump" surfaceScale="51" specularConstant="0.65" specularExponent="8" lighting-color="white" result="spec">
                        <fePointLight x="-100" y="-100" z="80" />
                    </feSpecularLighting>
                    <feComposite in="spec" in2="SourceAlpha" operator="in" />
                </filter>
            </defs>
            <path d="${generateSquirclePath(size, 5)}" fill="white" filter="url(#cornerSpec)" />
        </svg>
    `;
    const cornerSpec = await sharp(Buffer.from(cornerSpecSvg)).png().toBuffer();
    composites.push({ input: cornerSpec, top: 0, left: 0 });

    await sharp({
        create: { width: size, height: size, channels: 4, background: { r: 0, g: 0, b: 0, alpha: 0 } }
    })
    .composite(composites)
    .toFile(OUTPUT_PATH);

    console.log(`Generated ${OUTPUT_PATH}`);
}

generate().catch(console.error);
