import type { Post } from "@/types/post";

const COLORS = {
	mastodon: "#6364ff",
	bluesky: "#0085ff",
} as const;

export function SourceBadge({ post }: { post: Post }) {
	const label =
		post.source === "mastodon"
			? (post.author_handle.split("@").pop() ?? "mastodon")
			: "bsky.app";

	return (
		<div className="flex items-center gap-1.5 self-start rounded-full bg-white/10 px-2.5 py-1 text-xs text-white/60">
			<span
				className="h-1.5 w-1.5 rounded-full"
				style={{ background: COLORS[post.source] }}
			/>
			{label}
		</div>
	);
}
