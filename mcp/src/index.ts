#!/usr/bin/env node

import "dotenv/config.js";

import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";

import { createDischubServer } from "./server.js";

async function main(): Promise<void> {
    const server = createDischubServer();
    const transport = new StdioServerTransport();

    await server.connect(transport);
}

main().catch((error: unknown) => {
    console.error("dischub-mcp failed to start:", error);
    process.exit(1);
});
