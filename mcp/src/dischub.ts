export const DEFAULT_STATUS_URL = "https://dischub.co.zw/api/payment/status/3/step/";
export const DEFAULT_INITIATE_URL = "https://dischub.co.zw/api/payment/initiate/";
export const USER_AGENT = "dischub-mcp/0.1.2";

export interface PaymentStatusResult {
    ok: boolean;
    order_id: string;
    status: number;
    data?: unknown;
    error?: string;
}

function environmentValue(name: string): string | undefined {
    const value = process.env[name];

    if (value === undefined) {
        return undefined;
    }

    const trimmed = value.trim();

    return trimmed === "" ? undefined : trimmed;
}

function timeoutMs(): number {
    const raw = environmentValue("DISCHUB_TIMEOUT");
    const seconds = raw === undefined ? 30 : Number(raw);

    if (!Number.isFinite(seconds) || seconds <= 0) {
        return 30_000;
    }

    return Math.min(seconds, 120) * 1000;
}

export async function getPaymentStatus(
    orderId: string,
    recipientOverride?: string,
): Promise<PaymentStatusResult> {
    const apiKey = environmentValue("DISCHUB_API_KEY");

    if (apiKey === undefined) {
        return {
            ok: false,
            order_id: orderId,
            status: 0,
            error: "DISCHUB_API_KEY is not configured for the MCP server.",
        };
    }

    const recipient =
        recipientOverride?.trim() ||
        environmentValue("DISCHUB_RECIPIENT") ||
        environmentValue("DISCHUB_RECIPIENT_EMAIL");

    if (recipient === undefined || recipient === "") {
        return {
            ok: false,
            order_id: orderId,
            status: 0,
            error: "DISCHUB_RECIPIENT is not configured for the MCP server.",
        };
    }

    const url = environmentValue("DISCHUB_STATUS_URL") ?? DEFAULT_STATUS_URL;

    try {
        new URL(url);
    } catch {
        return {
            ok: false,
            order_id: orderId,
            status: 0,
            error: "DISCHUB_STATUS_URL must be a valid URL.",
        };
    }

    const controller = new AbortController();
    const timeout = timeoutMs();
    const timer = setTimeout(() => controller.abort(), timeout);

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "User-Agent": USER_AGENT,
                "X-Api-Key": apiKey,
            },
            body: JSON.stringify({
                order_id: orderId,
                recipient,
            }),
            signal: controller.signal,
        });

        const text = await response.text();
        let data: unknown = text;

        if (text.trim() !== "") {
            try {
                data = JSON.parse(text);
            } catch {
                data = text;
            }
        }

        if (!response.ok) {
            return {
                ok: false,
                order_id: orderId,
                status: response.status,
                data,
                error: `Dischub returned HTTP ${response.status}.`,
            };
        }

        return {
            ok: true,
            order_id: orderId,
            status: response.status,
            data,
        };
    } catch (error) {
        if (error instanceof Error && error.name === "AbortError") {
            return {
                ok: false,
                order_id: orderId,
                status: 0,
                error: `Dischub request timed out after ${Math.round(timeout / 1000)} seconds.`,
            };
        }

        return {
            ok: false,
            order_id: orderId,
            status: 0,
            error: error instanceof Error ? error.message : "Unknown Dischub request error.",
        };
    } finally {
        clearTimeout(timer);
    }
}

export interface PaymentInitiationResult {
    ok: boolean;
    order_id?: string;
    transaction_reference?: string;
    payment_url?: string;
    status_code?: number;
    error?: string;
}

export async function createPayment(
    orderId: string,
    sender: string,
    amount: number,
    paymentMethod: string,
    recipientOverride?: string,
): Promise<PaymentInitiationResult> {
    const apiKey = environmentValue("DISCHUB_API_KEY");

    if (apiKey === undefined) {
        return {
            ok: false,
            error: "DISCHUB_API_KEY is not configured. Set it in your .env file.",
        };
    }

    const recipient =
        recipientOverride?.trim() ||
        environmentValue("DISCHUB_RECIPIENT") ||
        environmentValue("DISCHUB_RECIPIENT_EMAIL");

    if (recipient === undefined || recipient === "") {
        return {
            ok: false,
            error: "DISCHUB_RECIPIENT is not configured. Set it in your .env file.",
        };
    }

    const url = environmentValue("DISCHUB_INITIATE_URL") ?? DEFAULT_INITIATE_URL;

    try {
        new URL(url);
    } catch {
        return {
            ok: false,
            error: "DISCHUB_INITIATE_URL must be a valid URL.",
        };
    }

    const controller = new AbortController();
    const timeout = timeoutMs();
    const timer = setTimeout(() => controller.abort(), timeout);

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "User-Agent": USER_AGENT,
                "X-Api-Key": apiKey,
            },
            body: JSON.stringify({
                order_id: orderId,
                sender,
                amount,
                payment_method: paymentMethod,
                recipient,
            }),
            signal: controller.signal,
        });

        const text = await response.text();
        let data: unknown = text;

        if (text.trim() !== "") {
            try {
                data = JSON.parse(text);
            } catch {
                data = text;
            }
        }

        if (!response.ok) {
            return {
                ok: false,
                status_code: response.status,
                error: `Dischub returned HTTP ${response.status}.`,
            };
        }

        const result = typeof data === "object" && data !== null ? (data as Record<string, unknown>) : {};

        return {
            ok: true,
            order_id: orderId,
            transaction_reference: result.transaction_ref as string | undefined,
            payment_url: result.payment_url as string | undefined,
            status_code: response.status,
        };
    } catch (error) {
        if (error instanceof Error && error.name === "AbortError") {
            return {
                ok: false,
                error: `Dischub request timed out after ${Math.round(timeout / 1000)} seconds.`,
            };
        }

        return {
            ok: false,
            error: error instanceof Error ? error.message : "Unknown Dischub request error.",
        };
    } finally {
        clearTimeout(timer);
    }
}
