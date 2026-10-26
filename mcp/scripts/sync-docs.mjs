import { copyFile, mkdir, readdir, rm } from "node:fs/promises";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const scriptDir = dirname(fileURLToPath(import.meta.url));
const mcpDir = resolve(scriptDir, "..");
const repoRoot = resolve(mcpDir, "..");
const docsDir = join(mcpDir, "docs");
const officialSource = join(repoRoot, "official-docs");
const officialTarget = join(docsDir, "official");

await rm(docsDir, { recursive: true, force: true });
await mkdir(officialTarget, { recursive: true });

for (const file of ["README.md", "SECURITY.md", "CHANGELOG.md"]) {
    await copyFile(join(repoRoot, file), join(docsDir, file));
}

async function copyMarkdownFiles(source, target) {
    let entries = [];

    try {
        entries = await readdir(source, { withFileTypes: true });
    } catch {
        // The official docs directory is optional outside the source repository.
        return;
    }

    for (const entry of entries) {
        const sourcePath = join(source, entry.name);
        const targetPath = join(target, entry.name);

        if (entry.isDirectory()) {
            await mkdir(targetPath, { recursive: true });
            await copyMarkdownFiles(sourcePath, targetPath);
        } else if (entry.isFile() && entry.name.endsWith(".md")) {
            await copyFile(sourcePath, targetPath);
        }
    }
}

await copyMarkdownFiles(officialSource, officialTarget);

console.error(`Synced Dischub docs to ${docsDir}`);
