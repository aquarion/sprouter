function balanceParagraph(text: string, minLines = 1): string[] {
	const words = text.split(/\s+/).filter(Boolean);

	if (words.length === 0) {
		return [];
	}

	const numLines = Math.min(8, Math.max(minLines, Math.ceil(words.length / 4)));
	const targetChars = Math.ceil(text.length / numLines);

	const lines: string[] = [];
	let current = "";

	for (const word of words) {
		if (!current) {
			current = word;
		} else if (current.length + 1 + word.length <= targetChars) {
			current += ` ${word}`;
		} else {
			lines.push(current);
			current = word;
		}
	}

	if (current) {
		lines.push(current);
	}

	return lines;
}

export function splitIntoLines(text: string): string[] {
	const paragraphs = text.split("\n").filter(Boolean);

	if (paragraphs.length === 0) {
		return [];
	}

	// Single-paragraph posts keep the minimum-2-lines visual balance.
	// Multi-paragraph posts allow 1 line per paragraph so short paragraphs
	// don't force excess lines that make the block tall and thin.
	const minLines = paragraphs.length === 1 ? 2 : 1;

	return paragraphs.flatMap((p) => balanceParagraph(p, minLines));
}
