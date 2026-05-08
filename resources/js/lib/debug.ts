// biome-ignore lint/suspicious/noExplicitAny: dynamic property access required
/**
 * Debug utilities exposed to window for console access
 * Only available when APP_DEBUG=true
 */

export function setupDebugWindow() {
	if (!(window as any).__APP_DEBUG) {
		return;
	}

	(window as any).__DEBUG = {
		/**
		 * Dump current feed queue and post cache
		 */
		dumpFeedQueue(this: any) {
			const store = this._feedStore;

			if (!store) {
				console.warn(
					"Feed store not initialized. Make sure you call setupFeedDebug() from Feed.tsx",
				);

				return;
			}

			console.group("📺 Feed Queue State");
			console.log("Current post:", store.current);
			console.log("Queue length:", store.queue.length);
			console.table(
				store.queue.map((p: any) => ({
					id: p.id,
					source: p.source,
					author: p.author_name,
					body: `${p.body.substring(0, 40)}...`,
					created: p.created_at,
				})),
			);
			console.log("Cursor:", store.cursor);
			console.groupEnd();
		},

		/**
		 * Show detailed info for a specific post
		 */
		inspectPost(index: number) {
			const store = this._feedStore;

			if (!store?.queue[index]) {
				console.warn(`Post at index ${index} not found`);

				return;
			}

			console.group(`📄 Post #${index}`);
			console.table(store.queue[index]);
			console.groupEnd();
		},

		/**
		 * Export all posts as JSON
		 */
		exportPosts() {
			const store = this._feedStore;

			if (!store) {
				console.warn("Feed store not initialized");

				return;
			}

			const all = store.current ? [store.current, ...store.queue] : store.queue;
			const json = JSON.stringify(all, null, 2);
			console.log(json);
			// Copy to clipboard for easy export
			navigator.clipboard.writeText(json).then(() => {
				console.log("✅ Copied to clipboard!");
			});
		},
	};
}

/**
 * Register feed state with debug utilities
 * Call this from your Feed component
 */
export function registerFeedDebug(state: {
	current: any;
	queue: any[];
	cursor: string | null;
}) {
	if (!(window as any).__APP_DEBUG) {
		return;
	}

	(window as any).__DEBUG._feedStore = state;
}
