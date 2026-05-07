import type { Post } from "@/types/post";

export function Attribution({ post }: { post: Post }) {
	return (
		<a
			href={post.original_url}
			target="_blank"
			rel="noopener noreferrer"
			className="flex min-w-0 flex-1 items-center gap-2 text-left"
		>
			<img
				src={post.author_avatar}
				alt={post.author_name}
				className="h-7 w-7 flex-shrink-0 rounded-full object-cover"
			/>
			<div className="min-w-0 flex-1">
				<p className="truncate text-xs font-bold text-white">
					{post.author_name}
				</p>
				<p className="truncate text-[0.65rem] text-white/50">
					{post.author_handle} · tap to open ↗
				</p>
			</div>
		</a>
	);
}
