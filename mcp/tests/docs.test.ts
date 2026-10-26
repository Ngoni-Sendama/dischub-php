import { describe, expect, it } from "vitest";

import { findDoc, getDocIndex, searchDocs } from "../src/docs.js";

describe("docs", () => {
    it("indexes the README and official integration docs", () => {
        const index = getDocIndex();

        expect(index.some((doc) => doc.slug === "readme")).toBe(true);
        expect(index.some((doc) => doc.slug.includes("ecocash"))).toBe(true);
        expect(index.some((doc) => doc.slug.includes("zimswitch"))).toBe(true);
    });

    it("finds the phone number format section", () => {
        const results = searchDocs("phone number formats");

        expect(results.length).toBeGreaterThan(0);
        expect(results.some((result) => result.slug === "readme")).toBe(true);
    });

    it("finds a document by slug", () => {
        const doc = findDoc("readme");

        expect(doc).not.toBeNull();
        expect(doc?.content).toContain("Dischub");
    });

    it("finds the official EcoCash document by file name", () => {
        const doc = findDoc("ecocash");

        expect(doc).not.toBeNull();
        expect(doc?.content.toLowerCase()).toContain("ecocash");
    });

    it("returns no results for a blank query", () => {
        expect(searchDocs("   ")).toEqual([]);
    });

    it("respects the result limit", () => {
        const results = searchDocs("payment", 2);

        expect(results.length).toBeLessThanOrEqual(2);
    });
});
