import { readFileSync, readdirSync } from "node:fs";
import { basename, join, relative, resolve } from "node:path";
import { fileURLToPath } from "node:url";

export interface DocSection {
    title: string;
    breadcrumb: string;
    content: string;
    anchor: string;
}

export interface DocFile {
    slug: string;
    title: string;
    path: string;
    content: string;
    sections: DocSection[];
}

export interface DocSummary {
    slug: string;
    title: string;
    path: string;
}

export interface SearchResult {
    slug: string;
    title: string;
    breadcrumb: string;
    path: string;
    snippet: string;
    score: number;
}

const docsRoot = resolve(fileURLToPath(new URL("../docs", import.meta.url)));

let cachedDocs: DocFile[] | undefined;

function slugify(value: string): string {
    return value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
}

function walkMarkdownFiles(directory: string): string[] {
    const files: string[] = [];

    for (const entry of readdirSync(directory, { withFileTypes: true })) {
        const fullPath = join(directory, entry.name);

        if (entry.isDirectory()) {
            files.push(...walkMarkdownFiles(fullPath));
        } else if (entry.isFile() && entry.name.toLowerCase().endsWith(".md")) {
            files.push(fullPath);
        }
    }

    return files.sort();
}

function tokenize(value: string): string[] {
    return [
        ...new Set(
            value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, " ")
                .split(" ")
                .map((token) => token.trim())
                .filter((token) => token.length >= 2),
        ),
    ];
}

function makeSnippet(content: string, tokens: string[], phrase: string): string {
    const normalized = content.replace(/\s+/g, " ").trim();

    if (normalized === "") {
        return "";
    }

    const haystack = normalized.toLowerCase();
    const candidates = [phrase, ...tokens].filter((candidate) => candidate.length > 1);
    let index = -1;

    for (const candidate of candidates) {
        const found = haystack.indexOf(candidate);

        if (found !== -1 && (index === -1 || found < index)) {
            index = found;
        }
    }

    const start = Math.max(0, index === -1 ? 0 : index - 120);
    const end = Math.min(normalized.length, start + 400);
    const prefix = start > 0 ? "..." : "";
    const suffix = end < normalized.length ? "..." : "";

    return `${prefix}${normalized.slice(start, end)}${suffix}`;
}

function parseSections(content: string, docTitle: string): DocSection[] {
    const lines = content.replace(/\r\n/g, "\n").split("\n");
    const sections: DocSection[] = [];
    const headings: Array<{ level: number; title: string }> = [];
    let currentTitle = docTitle;
    let currentBreadcrumb = docTitle;
    let buffer: string[] = [];
    let inFence = false;

    const flush = (): void => {
        const body = buffer.join("\n").trim();

        if (body !== "") {
            sections.push({
                title: currentTitle,
                breadcrumb: currentBreadcrumb,
                content: body,
                anchor: slugify(currentBreadcrumb),
            });
        }

        buffer = [];
    };

    for (const line of lines) {
        if (/^\s*(```|~~~)/.test(line)) {
            inFence = !inFence;
        }

        const heading = inFence ? null : line.match(/^(#{1,6})\s+(.+?)\s*#*\s*$/);

        if (heading === null) {
            buffer.push(line);
            continue;
        }

        flush();

        const level = heading[1]?.length ?? 1;
        const title = heading[2]?.trim() ?? docTitle;

        while (headings.length > 0 && (headings[headings.length - 1]?.level ?? 0) >= level) {
            headings.pop();
        }

        headings.push({ level, title });
        currentTitle = title;
        currentBreadcrumb = headings.map((item) => item.title).join(" > ");
    }

    flush();

    if (sections.length === 0) {
        sections.push({
            title: docTitle,
            breadcrumb: docTitle,
            content: content.trim(),
            anchor: slugify(docTitle),
        });
    }

    return sections;
}

function loadDoc(filePath: string): DocFile {
    const content = readFileSync(filePath, "utf8").replace(/\r\n/g, "\n");
    const relativePath = relative(docsRoot, filePath).replace(/\\/g, "/");
    const slug = relativePath
        .replace(/\.md$/i, "")
        .split("/")
        .map((segment) => slugify(segment))
        .filter((segment) => segment !== "")
        .join("/");
    const heading = content.match(/^#\s+(.+?)\s*#*\s*$/m);
    const title = heading?.[1]?.trim() || basename(relativePath, ".md");

    return {
        slug,
        title,
        path: `docs/${relativePath}`,
        content,
        sections: parseSections(content, title),
    };
}

export function getDocs(): DocFile[] {
    if (cachedDocs !== undefined) {
        return cachedDocs;
    }

    try {
        cachedDocs = walkMarkdownFiles(docsRoot).map(loadDoc);
    } catch {
        cachedDocs = [];
    }

    return cachedDocs;
}

export function getDocIndex(): DocSummary[] {
    return getDocs()
        .map((doc) => ({ slug: doc.slug, title: doc.title, path: doc.path }))
        .sort((left, right) => left.slug.localeCompare(right.slug));
}

export function findDoc(topic: string): DocFile | null {
    const normalized = topic
        .toLowerCase()
        .replace(/\.md$/, "")
        .replace(/\\/g, "/")
        .replace(/^dischub:\/\/docs\//, "")
        .replace(/^\/+/, "")
        .trim();

    if (normalized === "") {
        return null;
    }

    const docs = getDocs();
    const exact = docs.find((doc) => {
        const fileName = doc.slug.split("/").pop();

        return (
            doc.slug === normalized ||
            fileName === normalized ||
            doc.title.toLowerCase() === normalized
        );
    });

    if (exact !== undefined) {
        return exact;
    }

    return (
        docs.find(
            (doc) =>
                doc.slug.includes(normalized) || doc.title.toLowerCase().includes(normalized),
        ) ?? null
    );
}

export function searchDocs(query: string, limit = 5): SearchResult[] {
    const phrase = query.toLowerCase().trim();
    const tokens = tokenize(phrase);

    if (phrase === "" || tokens.length === 0) {
        return [];
    }

    const maxResults = Math.min(Math.max(limit, 1), 10);
    const results: SearchResult[] = [];

    for (const doc of getDocs()) {
        for (const section of doc.sections) {
            const titleText = `${doc.title} ${section.title} ${section.breadcrumb}`.toLowerCase();
            const contentText = section.content.toLowerCase();
            let score = 0;

            if (titleText.includes(phrase) || contentText.includes(phrase)) {
                score += 10;
            }

            for (const token of tokens) {
                if (titleText.includes(token)) {
                    score += 5;
                }

                if (contentText.includes(token)) {
                    score += 1;
                }
            }

            if (score === 0) {
                continue;
            }

            results.push({
                slug: doc.slug,
                title: section.title,
                breadcrumb: section.breadcrumb,
                path: doc.path,
                snippet: makeSnippet(section.content, tokens, phrase),
                score,
            });
        }
    }

    return results
        .sort(
            (left, right) =>
                right.score - left.score ||
                left.slug.localeCompare(right.slug) ||
                left.breadcrumb.localeCompare(right.breadcrumb),
        )
        .slice(0, maxResults);
}
