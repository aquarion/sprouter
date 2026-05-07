import { useCallback, useRef, useState } from "react";
import { flushSync } from "react-dom";
import { Head } from "@inertiajs/react";
import { gsap } from "gsap";
import { PostCard } from "@/components/feed/PostCard";
import { useAutoAdvance } from "@/hooks/useAutoAdvance";
import { useFeedQueue } from "@/hooks/useFeedQueue";
import type { Post } from "@/types/post";

export default function Feed({
	initialPosts,
	initialCursor,
}: {
	initialPosts: Post[];
	initialCursor: string | null;
}) {
	const { current, advance } = useFeedQueue({ initialPosts, initialCursor });
	const [paused, setPaused] = useState(false);
	const currentRef = useRef<HTMLDivElement>(null);
	const transitioningRef = useRef(false);

	const handleAdvance = useCallback(() => {
		const el = currentRef.current;
		if (transitioningRef.current || !el) return;
		transitioningRef.current = true;

		gsap.to(el, {
			scale: 1.3,
			filter: "blur(8px)",
			opacity: 0,
			duration: 0.3,
			ease: "power2.in",
			onComplete: () => {
				// Swap content while screen is dark so zoom-in reveals the new post
				flushSync(() => advance());
				gsap.fromTo(
					el,
					{ scale: 0.7, filter: "blur(8px)", opacity: 0 },
					{
						scale: 1,
						filter: "blur(0px)",
						opacity: 1,
						duration: 0.3,
						ease: "power2.out",
						onComplete: () => { transitioningRef.current = false; },
					},
				);
			},
		});
	}, [advance]);

	const { progress } = useAutoAdvance({
		duration: 8000,
		paused,
		onAdvance: handleAdvance,
	});

	if (!current) {
		return (
			<div className="flex h-screen items-center justify-center bg-black text-white">
				<p className="text-sm opacity-50">
					No posts — connect an account in Settings.
				</p>
			</div>
		);
	}

	return (
		<>
			<Head title="Feed" />
			<div className="h-screen w-screen overflow-hidden bg-black">
				<div ref={currentRef} className="h-full w-full">
					<PostCard
						post={current}
						progress={progress}
						paused={paused}
						onTogglePause={() => setPaused((p) => !p)}
					/>
				</div>
			</div>
		</>
	);
}
