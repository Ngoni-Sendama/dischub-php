export const CURRENCIES = ["USD", "ZWG"] as const;
export const MODES = ["test", "live"] as const;
export const PAYMENT_METHODS = ["ecocash", "innbucks", "omari", "zimswitch"] as const;

export const SENDER_PATTERN = /^(?:\+263|0263|0)7\d{8}$/;
export const ECOCASH_NUMBER_PATTERN = /^(?:\+263|0)7\d{8}$/;
export const OMARI_NUMBER_PATTERN = /^2637\d{8}$/;

export type Integration = "redirect" | "non_redirect";
export type PaymentMethod = (typeof PAYMENT_METHODS)[number];

export interface PaymentInput {
    integration?: string;
    payment_method?: string;
    order_id?: string | number;
    sender?: string;
    amount?: string | number;
    currency?: string;
    callback_url?: string | null;
    redirect_url?: string | null;
    mode?: string;
    ecocash_number?: string;
    omari_number?: string;
}

export interface ValidationIssue {
    field: string;
    message: string;
}

export interface PaymentValidationResult {
    valid: boolean;
    errors: ValidationIssue[];
    normalized: Record<string, unknown>;
}

function addError(errors: ValidationIssue[], field: string, message: string): void {
    errors.push({ field, message });
}

function stringValue(value: unknown, field: string, errors: ValidationIssue[]): string | undefined {
    if (typeof value === "number" && Number.isFinite(value)) {
        return String(value);
    }

    if (typeof value !== "string") {
        addError(errors, field, `${field} is required.`);
        return undefined;
    }

    const trimmed = value.trim();

    if (trimmed === "") {
        addError(errors, field, `${field} is required.`);
        return undefined;
    }

    return trimmed;
}

function optionalString(value: unknown): string | undefined {
    if (typeof value !== "string") {
        return undefined;
    }

    const trimmed = value.trim();

    return trimmed === "" ? undefined : trimmed;
}

function isNumericString(value: string): boolean {
    return /^[+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?$/.test(value);
}

export function validateOrderId(
    value: unknown,
    errors: ValidationIssue[],
): string | undefined {
    if (typeof value === "number" && Number.isInteger(value)) {
        value = String(value);
    }

    if (typeof value !== "string") {
        addError(errors, "order_id", "order_id must be posted as a string.");
        return undefined;
    }

    const trimmed = value.trim();

    if (trimmed === "") {
        addError(errors, "order_id", "order_id is required.");
        return undefined;
    }

    if (Array.from(trimmed).length > 30) {
        addError(errors, "order_id", "order_id must be 30 characters or less.");
        return undefined;
    }

    return trimmed;
}

function validatePhone(
    value: unknown,
    field: string,
    pattern: RegExp,
    accepted: string,
    errors: ValidationIssue[],
): string | undefined {
    const normalized = stringValue(value, field, errors);

    if (normalized === undefined) {
        return undefined;
    }

    if (!pattern.test(normalized)) {
        addError(
            errors,
            field,
            `invalid ${field} phone number format. Accepted formats: ${accepted}.`,
        );
        return undefined;
    }

    return normalized;
}

function validateAmount(
    value: unknown,
    errors: ValidationIssue[],
): number | undefined {
    if (value === undefined || value === null) {
        addError(errors, "amount", "amount is required.");
        return undefined;
    }

    const normalized =
        typeof value === "number"
            ? value
            : typeof value === "string" && isNumericString(value.trim())
              ? Number(value.trim())
              : Number.NaN;

    if (!Number.isFinite(normalized)) {
        addError(errors, "amount", "amount must be a valid number.");
        return undefined;
    }

    if (normalized <= 0 || normalized >= 100000) {
        addError(errors, "amount", "amount must be greater than 0 and less than 100000.");
        return undefined;
    }

    return normalized;
}

function validateUrl(
    value: unknown,
    field: string,
    schemes: string[],
    errors: ValidationIssue[],
): string | undefined {
    const normalized = optionalString(value);

    if (normalized === undefined) {
        return undefined;
    }

    let parsed: URL;

    try {
        parsed = new URL(normalized);
    } catch {
        addError(
            errors,
            field,
            `${field} must be a valid URL using the ${schemes.join(" or ")} scheme.`,
        );
        return undefined;
    }

    const scheme = parsed.protocol.replace(/:$/, "").toLowerCase();

    if (!schemes.includes(scheme) || parsed.hostname === "") {
        addError(
            errors,
            field,
            `${field} must be a valid URL using the ${schemes.join(" or ")} scheme.`,
        );
        return undefined;
    }

    if (parsed.username !== "" || parsed.password !== "") {
        addError(errors, field, `${field} must not include credentials.`);
        return undefined;
    }

    return normalized;
}

export function validatePayment(input: PaymentInput): PaymentValidationResult {
    const errors: ValidationIssue[] = [];
    const normalized: Record<string, unknown> = {};

    const integration =
        input.integration === undefined ? "redirect" : input.integration.toLowerCase().trim();

    if (integration !== "redirect" && integration !== "non_redirect") {
        addError(errors, "integration", "integration must be redirect or non_redirect.");
    } else {
        normalized.integration = integration;
    }

    const orderId = validateOrderId(input.order_id, errors);

    if (orderId !== undefined) {
        normalized.order_id = orderId;
    }

    const sender = validatePhone(
        input.sender,
        "sender",
        SENDER_PATTERN,
        "+263780070488, 0263780070488 or 0780070488",
        errors,
    );

    if (sender !== undefined) {
        normalized.sender = sender;
    }

    const amount = validateAmount(input.amount, errors);

    if (amount !== undefined) {
        normalized.amount = amount;
    }

    const currencyValue = stringValue(input.currency, "currency", errors);

    if (currencyValue !== undefined) {
        const currency = currencyValue.toUpperCase();

        if (!CURRENCIES.includes(currency as (typeof CURRENCIES)[number])) {
            addError(errors, "currency", "currency must be one of: USD, ZWG.");
        } else {
            normalized.currency = currency;
        }
    }

    if (integration === "redirect") {
        const modeValue =
            input.mode === undefined ? "test" : stringValue(input.mode, "mode", errors);

        if (modeValue !== undefined) {
            const mode = modeValue.toLowerCase();

            if (!MODES.includes(mode as (typeof MODES)[number])) {
                addError(errors, "mode", "mode must be either test or live.");
            } else {
                normalized.mode = mode;
            }
        }

        const callbackUrl = validateUrl(input.callback_url, "callback_url", ["https"], errors);

        if (callbackUrl !== undefined) {
            normalized.callback_url = callbackUrl;
        }

        const redirectUrl = validateUrl(input.redirect_url, "redirect_url", ["http", "https"], errors);

        if (redirectUrl !== undefined) {
            normalized.redirect_url = redirectUrl;
        }
    }

    if (integration === "non_redirect") {
        const methodValue = stringValue(input.payment_method, "payment_method", errors);

        if (methodValue !== undefined) {
            const paymentMethod = methodValue.toLowerCase();

            if (!PAYMENT_METHODS.includes(paymentMethod as PaymentMethod)) {
                addError(
                    errors,
                    "payment_method",
                    `payment_method must be one of: ${PAYMENT_METHODS.join(", ")}.`,
                );
            } else {
                normalized.payment_method = paymentMethod;

                if (paymentMethod === "ecocash") {
                    const ecocashNumber = validatePhone(
                        input.ecocash_number,
                        "ecocash_number",
                        ECOCASH_NUMBER_PATTERN,
                        "+263780000000 or 0780000000",
                        errors,
                    );

                    if (ecocashNumber !== undefined) {
                        normalized.ecocash_number = ecocashNumber;
                    }
                }

                if (paymentMethod === "omari") {
                    const omariNumber = validatePhone(
                        input.omari_number,
                        "omari_number",
                        OMARI_NUMBER_PATTERN,
                        "263780000000",
                        errors,
                    );

                    if (omariNumber !== undefined) {
                        normalized.omari_number = omariNumber;
                    }
                }

                if (paymentMethod === "zimswitch") {
                    const callbackUrl = validateUrl(
                        input.callback_url,
                        "callback_url",
                        ["https"],
                        errors,
                    );

                    if (callbackUrl !== undefined) {
                        normalized.callback_url = callbackUrl;
                    }
                }
            }
        }
    }

    return {
        valid: errors.length === 0,
        errors,
        normalized,
    };
}
