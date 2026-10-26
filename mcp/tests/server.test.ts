import { Client } from "@modelcontextprotocol/sdk/client/index.js";
import { InMemoryTransport } from "@modelcontextprotocol/sdk/inMemory.js";
import { describe, expect, it } from "vitest";

import { createDischubServer } from "../src/server.js";

async function connect(): Promise<{ client: Client; close: () => Promise<void> }> {
    const server = createDischubServer();
    const client = new Client({ name: "dischub-mcp-test", version: "0.0.0" });
    const [clientTransport, serverTransport] = InMemoryTransport.createLinkedPair();

    await Promise.all([server.connect(serverTransport), client.connect(clientTransport)]);

    return {
        client,
        close: async () => {
            await client.close();
            await server.close();
        },
    };
}

function textOf(result: Awaited<ReturnType<Client["callTool"]>>): string {
    return (result.content as Array<{ type: string; text?: string }>)
        .map((block) => block.text ?? "")
        .join("\n");
}

describe("MCP server", () => {
    it("exposes only read-only tools and documentation resources", async () => {
        const connection = await connect();

        try {
            const tools = await connection.client.listTools();
            const names = tools.tools.map((tool) => tool.name).sort();

            expect(names).toEqual([
                "dischub_get_doc",
                "dischub_get_payment_status",
                "dischub_search_docs",
                "dischub_validate_payment",
            ]);
            expect(names.some((name) => /create|refund|capture/.test(name))).toBe(false);

            const resources = await connection.client.listResources();

            expect(resources.resources.some((resource) => resource.uri === "dischub://docs")).toBe(
                true,
            );
            expect(
                resources.resources.some((resource) =>
                    resource.uri.startsWith("dischub://docs/readme"),
                ),
            ).toBe(true);
        } finally {
            await connection.close();
        }
    });

    it("serves documentation search over MCP", async () => {
        const connection = await connect();

        try {
            const result = await connection.client.callTool({
                name: "dischub_search_docs",
                arguments: { query: "ecocash" },
            });

            expect(result.isError).toBeFalsy();
            expect(textOf(result).toLowerCase()).toContain("ecocash");
        } finally {
            await connection.close();
        }
    });

    it("validates payment input over MCP", async () => {
        const connection = await connect();

        try {
            const result = await connection.client.callTool({
                name: "dischub_validate_payment",
                arguments: {
                    order_id: "order-1",
                    sender: "0780070488",
                    amount: 1,
                    currency: "USD",
                },
            });

            expect(result.isError).toBeFalsy();
            expect(textOf(result)).toContain("valid");
        } finally {
            await connection.close();
        }
    });

    it("returns an error for an unknown document", async () => {
        const connection = await connect();

        try {
            const result = await connection.client.callTool({
                name: "dischub_get_doc",
                arguments: { topic: "does-not-exist" },
            });

            expect(result.isError).toBe(true);
            expect(textOf(result)).toContain("No document found");
        } finally {
            await connection.close();
        }
    });
});
