import { expect, it, vi } from 'vitest';
import { arc } from './arc';

function makeWords(texts: string[]) {
    return texts.map((t) => ({ textContent: t }));
}

function makeTl() {
    const tl = { set: vi.fn(), to: vi.fn() };
    tl.set.mockReturnValue(tl);
    tl.to.mockReturnValue(tl);

    return tl;
}

it('picks the longest non-mention word as the arc focal word', () => {
    const tl = makeTl();
    const words = makeWords(['@averylongmention', 'extraordinary', 'fox']);

    // biome-ignore lint/suspicious/noExplicitAny: test harness requires any types
    arc(tl as any, words as any, null as any);

    const focalCall = tl.set.mock.calls.find(([, opts]) => opts?.scale === 2.5);
    expect(focalCall?.[0].textContent).toBe('extraordinary');
});

it('picks the longest non-hashtag word as the arc focal word', () => {
    const tl = makeTl();
    const words = makeWords(['#superlonghashtag', 'wonderful', 'day']);

    // biome-ignore lint/suspicious/noExplicitAny: test harness requires any types
    arc(tl as any, words as any, null as any);

    const focalCall = tl.set.mock.calls.find(([, opts]) => opts?.scale === 2.5);
    expect(focalCall?.[0].textContent).toBe('wonderful');
});

it('falls back to full word list when all words are mentions or hashtags', () => {
    const tl = makeTl();
    const words = makeWords(['#longesthashtag', '@mention']);

    // biome-ignore lint/suspicious/noExplicitAny: test harness requires any types
    arc(tl as any, words as any, null as any);

    const focalCall = tl.set.mock.calls.find(([, opts]) => opts?.scale === 2.5);
    expect(focalCall?.[0].textContent).toBe('#longesthashtag');
});
