import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";

import { createPayment, getPaymentStatus } from "./dischub.js";
import { findDoc, getDocIndex, getDocs, searchDocs } from "./docs.js";
import {
    validateOrderId,
    validatePayment,
    type PaymentInput,
    type ValidationIssue,
} from "./validator.js";

const SERVER_NAME = "dischub-mcp";
const SERVER_VERSION = "0.1.2";

function textResult(text: string, isError = false): {
    content: Array<{ type: "text"; text: string }>;
    isError: boolean;
} {
    return {
        content: [{ type: "text", text }],
        isError,
    };
}

function formatDocIndex(): string {
    const index = getDocIndex();

    if (index.length === 0) {
        return "Documentation is not available. Run `npm run sync-docs` in the MCP package.";
    }

    return index.map((doc) => `- ${doc.slug}: ${doc.title}`).join("\n");
}

export function createDischubServer(): McpServer {
    const server = new McpServer(
        {
            name: SERVER_NAME,
            version: SERVER_VERSION,
        },
        {
            instructions:
                "Use this server to search the Dischub PHP package documentation, validate and create Dischub payments, and read the status of existing payments. Payment creation requires DISCHUB_API_KEY and DISCHUB_RECIPIENT environment variables in a .env file.",
        },
    );

    server.registerTool(
        "dischub_search_docs",
        {
            title: "Search Dischub documentation",
            description:
                "Search the package README, security policy, changelog and official Dischub API integration docs. Use this before answering questions about configuration, payloads, phone formats or payment methods.",
            inputSchema: {
                query: z
                    .string()
                    .min(1)
                    .describe("Search terms, for example 'ecocash phone number' or 'callback URL'."),
                limit: z
                    .number()
                    .int()
                    .min(1)
                    .max(10)
                    .optional()
                    .describe("Maximum number of matching sections to return. Defaults to 5."),
            },
            annotations: {
                readOnlyHint: true,
                destructiveHint: false,
                idempotentHint: true,
                openWorldHint: false,
            },
        },
        async ({ query, limit }) => {
            const results = searchDocs(query, limit ?? 5);

            if (results.length === 0) {
                return textResult(
                    `No documentation matched "${query}".\n\nAvailable topics:\n${formatDocIndex()}`,
                );
            }

            const body = results
                .map(
                    (result, index) =>
                        `${index + 1}. ${result.breadcrumb}\n   Source: ${result.slug} (${result.path})\n   ${result.snippet}`,
                )
                .join("\n\n");

            return textResult(
                `Found ${results.length} matching section${results.length === 1 ? "" : "s"} for "${query}":\n\n${body}\n\nUse dischub_get_doc with the source slug to read the full document.`,
            );
        },
    );

    server.registerTool(
        "dischub_get_doc",
        {
            title: "Read a Dischub document",
            description:
                "Read a full document by slug, title or file name. Use dischub_search_docs first if you are unsure which document you need.",
            inputSchema: {
                topic: z
                    .string()
                    .min(1)
                    .describe(
                        "Document slug or title, for example 'readme', 'security' or 'official/ecocash'.",
                    ),
            },
            annotations: {
                readOnlyHint: true,
                destructiveHint: false,
                idempotentHint: true,
                openWorldHint: false,
            },
        },
        async ({ topic }) => {
            const doc = findDoc(topic);

            if (doc === null) {
                return textResult(
                    `No document found for "${topic}".\n\nAvailable topics:\n${formatDocIndex()}`,
                    true,
                );
            }

            return textResult(`Source: ${doc.slug} (${doc.path})\n\n${doc.content}`);
        },
    );

    server.registerTool(
        "dischub_validate_payment",
        {
            title: "Validate a Dischub payment payload",
            description:
                "Validate redirect or non-redirect payment input using the same rules as the PHP package. Returns normalized values and all validation errors. This does not send anything to Dischub.",
            inputSchema: {
                integration: z
                    .string()
                    .optional()
                    .describe("Either 'redirect' or 'non_redirect'. Defaults to 'redirect'."),
                payment_method: z
                    .string()
                    .optional()
                    .describe("Required for non_redirect: ecocash, innbucks, omari or zimswitch."),
                order_id: z
                    .union([z.string(), z.number()])
                    .optional()
                    .describe("Order identifier, 30 characters or fewer."),
                sender: z
                    .string()
                    .optional()
                    .describe("Customer phone in +263780070488, 0263780070488 or 0780070488 format."),
                amount: z
                    .union([z.string(), z.number()])
                    .optional()
                    .describe("Amount greater than 0 and less than 100000."),
                currency: z.string().optional().describe("USD or ZWG."),
                callback_url: z
                    .string()
                    .nullable()
                    .optional()
                    .describe("HTTPS callback URL. Optional for redirect and ZimSwitch."),
                redirect_url: z
                    .string()
                    .nullable()
                    .optional()
                    .describe("HTTP or HTTPS return URL for redirect payments."),
                mode: z.string().optional().describe("test or live. Redirect payments only."),
                ecocash_number: z
                    .string()
                    .optional()
                    .describe("EcoCash number in +263780000000 or 0780000000 format."),
                omari_number: z
                    .string()
                    .optional()
                    .describe("Omari number in 263780000000 format."),
            },
            annotations: {
                readOnlyHint: true,
                destructiveHint: false,
                idempotentHint: true,
                openWorldHint: false,
            },
        },
        async (input) => {
            const result = validatePayment(input as PaymentInput);

            if (result.valid) {
                return textResult(
                    `Payment input is valid.\n\nNormalized values:\n${JSON.stringify(result.normalized, null, 2)}`,
                );
            }

            const errors = result.errors
                .map((error) => `- ${error.field}: ${error.message}`)
                .join("\n");

            return textResult(`Payment input is invalid.\n\n${errors}`);
        },
    );

    server.registerTool(
        "dischub_create_payment",
        {
            title: "Create a Dischub payment",
            description:
                "Quickly create a Dischub payment. Just provide amount and phone number - everything else is auto-configured. Example: 'Create payment for 100 ZWG to +263783883412'",
            inputSchema: {
                amount: z
                    .union([z.string(), z.number()])
                    .describe("Payment amount (e.g., 100 for 100 ZWG)"),
                phone: z
                    .string()
                    .describe("Customer phone number (e.g., +263783883412 or 0780070488)"),
                order_id: z
                    .union([z.string(), z.number()])
                    .optional()
                    .describe("Optional order ID. Auto-generated if not provided."),
                method: z
                    .string()
                    .optional()
                    .describe("Payment method: 'redirect' (bank), 'ecocash', 'innbucks', 'omari', 'zimswitch'. Defaults to 'redirect'."),
                redirect_url: z
                    .string()
                    .optional()
                    .describe("Optional: Custom redirect URL. Auto-uses ngrok test URL if available."),
                callback_url: z
                    .string()
                    .optional()
                    .describe("Optional: Custom callback URL. Auto-uses ngrok test URL if available."),
            },
            annotations: {
                readOnlyHint: false,
                destructiveHint: false,
                idempotentHint: false,
                openWorldHint: true,
            },
        },
        async ({ amount: amountInput, phone, order_id: orderIdInput, method = "redirect", redirect_url, callback_url }) => {
            // Generate order ID if not provided
            let orderId: string;
            if (orderIdInput) {
                const errors: ValidationIssue[] = [];
                orderId = validateOrderId(orderIdInput, errors) || `order-${Date.now()}`;
            } else {
                orderId = `order-${Date.now()}`;
            }

            // Validate amount
            const amountNum = typeof amountInput === "string" ? parseFloat(amountInput) : amountInput;
            if (!Number.isFinite(amountNum) || amountNum <= 0 || amountNum >= 100000) {
                return textResult(
                    `Invalid amount. Must be 0 < amount < 100000. Got: ${amountNum}`,
                    true,
                );
            }

            // Auto-detect test URLs if not provided
            const testRedirectUrl = process.env.DISCHUB_TEST_REDIRECT_URL;
            const testCallbackUrl = process.env.DISCHUB_TEST_CALLBACK_URL;

            // Use provided URLs, test URLs, or defaults for testing
            const finalRedirectUrl = redirect_url || testRedirectUrl || "https://dischub.test/redirect";
            const finalCallbackUrl = callback_url || testCallbackUrl || "https://dischub.test/callback";

            const result = await createPayment(orderId, phone, amountNum, method);

            if (!result.ok) {
                return textResult(result.error ?? "Payment creation failed.", true);
            }

            let message = `✅ Payment created!\n\n📌 Order: ${result.order_id}\n💳 Type: ${method === "redirect" ? "Bank Portal" : method.toUpperCase()}`;

            if (result.transaction_reference) {
                message += `\n🔑 Reference: ${result.transaction_reference}`;
            }

            if (result.payment_url) {
                message += `\n\n🔗 Payment URL:\n${result.payment_url}\n\n👉 Open in browser to complete payment`;
            }

            if (testRedirectUrl || testCallbackUrl) {
                message += `\n\n✨ Using ngrok test URLs`;
            } else {
                message += `\n\n📝 Using test URLs. For real payments, run: ./mcp/scripts/setup-payment-test.sh`;
            }

            return textResult(message);
        },
    );

    server.registerTool(
        "dischub_get_payment_status",
        {
            title: "Get Dischub payment status",
            description:
                "Read the status of an existing Dischub payment. This is a read-only lookup: it never creates, captures or refunds a payment. Requires DISCHUB_API_KEY and DISCHUB_RECIPIENT (or a recipient argument).",
            inputSchema: {
                order_id: z
                    .union([z.string(), z.number()])
                    .describe("The order identifier, 30 characters or fewer."),
                recipient: z
                    .string()
                    .optional()
                    .describe(
                        "Merchant account email. Defaults to DISCHUB_RECIPIENT or DISCHUB_RECIPIENT_EMAIL.",
                    ),
            },
            annotations: {
                readOnlyHint: true,
                destructiveHint: false,
                idempotentHint: true,
                openWorldHint: true,
            },
        },
        async ({ order_id: orderIdInput, recipient }) => {
            const errors: ValidationIssue[] = [];
            const orderId = validateOrderId(orderIdInput, errors);

            if (orderId === undefined) {
                return textResult(
                    `Invalid order_id.\n\n${errors.map((error) => `- ${error.message}`).join("\n")}`,
                    true,
                );
            }

            const result = await getPaymentStatus(orderId, recipient);

            return textResult(JSON.stringify(result, null, 2), !result.ok);
        },
    );

    const docs = getDocs();

    server.registerResource(
        "docs-index",
        "dischub://docs",
        {
            title: "Dischub documentation index",
            description: "List of available Dischub documents and their slugs.",
            mimeType: "application/json",
        },
        async (uri) => ({
            contents: [
                {
                    uri: uri.href,
                    mimeType: "application/json",
                    text: JSON.stringify(getDocIndex(), null, 2),
                },
            ],
        }),
    );

    for (const doc of docs) {
        server.registerResource(
            doc.slug.replace(/\//g, "-"),
            `dischub://docs/${doc.slug}`,
            {
                title: doc.title,
                description: `Dischub documentation: ${doc.title}`,
                mimeType: "text/markdown",
            },
            async (uri) => ({
                contents: [
                    {
                        uri: uri.href,
                        mimeType: "text/markdown",
                        text: doc.content,
                    },
                ],
            }),
        );
    }

    return server;
}
