import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import { DEFAULT_STATUS_URL, getPaymentStatus } from "../src/dischub.js";

const ENV_KEYS = [
    "DISCHUB_API_KEY",
    "DISCHUB_RECIPIENT",
    "DISCHUB_RECIPIENT_EMAIL",
    "DISCHUB_STATUS_URL",
    "DISCHUB_TIMEOUT",
] as const;

const originalEnvironment = new Map<string, string | undefined>();

beforeEach(() => {
    originalEnvironment.clear();

    for (const key of ENV_KEYS) {
        originalEnvironment.set(key, process.env[key]);
        delete process.env[key];
    }

    vi.unstubAllGlobals();
});

afterEach(() => {
    for (const key of ENV_KEYS) {
        const value = originalEnvironment.get(key);

        if (value === undefined) {
            delete process.env[key];
        } else {
            process.env[key] = value;
        }
    }

    vi.unstubAllGlobals();
});

describe("getPaymentStatus", () => {
    it("returns a configuration error when the API key is missing", async () => {
        const result = await getPaymentStatus("order-1");

        expect(result.ok).toBe(false);
        expect(result.error).toContain("DISCHUB_API_KEY");
    });

    it("returns a configuration error when the recipient is missing", async () => {
        process.env.DISCHUB_API_KEY = "secret";

        const result = await getPaymentStatus("order-1");

        expect(result.ok).toBe(false);
        expect(result.error).toContain("DISCHUB_RECIPIENT");
    });

    it("posts the order and recipient to the status endpoint", async () => {
        process.env.DISCHUB_API_KEY = "secret";
        process.env.DISCHUB_RECIPIENT = "merchant@example.com";

        const fetchMock = vi.fn().mockResolvedValue(
            new Response(JSON.stringify({ status: "success", paid: true }), {
                status: 200,
                headers: { "Content-Type": "application/json" },
            }),
        );
        vi.stubGlobal("fetch", fetchMock);

        const result = await getPaymentStatus("order-1");

        expect(result.ok).toBe(true);
        expect(result.data).toEqual({ status: "success", paid: true });

        const call = fetchMock.mock.calls[0] as [string, RequestInit];
        expect(call[0]).toBe(DEFAULT_STATUS_URL);
        expect(call[1].method).toBe("POST");
        expect(call[1].body).toBe(
            JSON.stringify({ order_id: "order-1", recipient: "merchant@example.com" }),
        );

        const headers = call[1].headers as Record<string, string>;
        expect(headers["X-Api-Key"]).toBe("secret");
    });

    it("honours a recipient override and a custom status URL", async () => {
        process.env.DISCHUB_API_KEY = "secret";
        process.env.DISCHUB_RECIPIENT = "merchant@example.com";
        process.env.DISCHUB_STATUS_URL = "https://example.com/status";

        const fetchMock = vi.fn().mockResolvedValue(
            new Response("{}", { status: 200, headers: { "Content-Type": "application/json" } }),
        );
        vi.stubGlobal("fetch", fetchMock);

        await getPaymentStatus("order-2", "override@example.com");

        const call = fetchMock.mock.calls[0] as [string, RequestInit];
        expect(call[0]).toBe("https://example.com/status");
        expect(call[1].body).toBe(
            JSON.stringify({ order_id: "order-2", recipient: "override@example.com" }),
        );
    });

    it("returns HTTP errors with the response body", async () => {
        process.env.DISCHUB_API_KEY = "secret";
        process.env.DISCHUB_RECIPIENT = "merchant@example.com";

        vi.stubGlobal(
            "fetch",
            vi.fn().mockResolvedValue(
                new Response(JSON.stringify({ message: "not found" }), {
                    status: 404,
                    headers: { "Content-Type": "application/json" },
                }),
            ),
        );

        const result = await getPaymentStatus("missing-order");

        expect(result.ok).toBe(false);
        expect(result.status).toBe(404);
        expect(result.data).toEqual({ message: "not found" });
        expect(result.error).toContain("404");
    });

    it("does not leak the API key in network errors", async () => {
        process.env.DISCHUB_API_KEY = "super-secret-key";
        process.env.DISCHUB_RECIPIENT = "merchant@example.com";

        vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("network down")));

        const result = await getPaymentStatus("order-1");

        expect(result.ok).toBe(false);
        expect(result.error).toBe("network down");
        expect(JSON.stringify(result)).not.toContain("super-secret-key");
    });

    it("times out slow requests", async () => {
        process.env.DISCHUB_API_KEY = "secret";
        process.env.DISCHUB_RECIPIENT = "merchant@example.com";
        process.env.DISCHUB_TIMEOUT = "0.01";

        vi.stubGlobal(
            "fetch",
            vi.fn(
                (_url: string, init: RequestInit) =>
                    new Promise((_resolve, reject) => {
                        init.signal?.addEventListener("abort", () => {
                            const error = new Error("The operation was aborted");
                            error.name = "AbortError";
                            reject(error);
                        });
                    }),
            ),
        );

        const result = await getPaymentStatus("order-1");

        expect(result.ok).toBe(false);
        expect(result.error).toContain("timed out");
    });
});
