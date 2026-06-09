import { useCallback, useReducer } from 'react';
import type { Post } from '@/types/post';

type State = { current: Post | null; queue: Post[] };

function makeReducer(initialPosts: Post[]) {
    return (state: State): State => {
        if (state.queue.length === 0) {
            return {
                current: initialPosts[0] ?? null,
                queue: initialPosts.slice(1),
            };
        }

        const [next, ...rest] = state.queue;

        return { current: next, queue: rest };
    };
}

export function useWelcomeQueue(initialPosts: Post[]) {
    const [state, dispatch] = useReducer(makeReducer(initialPosts), {
        current: initialPosts[0] ?? null,
        queue: initialPosts.slice(1),
    });

    const advance = useCallback(() => dispatch(), []);

    return { current: state.current, queue: state.queue, advance };
}
