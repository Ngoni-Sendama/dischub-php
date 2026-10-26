import { describe, expect, it } from "vitest";

import { validatePayment } from "../src/validator.js";

describe("validatePayment", () => {
    it("accepts a redirect payload and normalizes values", () => {
        const result = validatePayment({
            integration: "redirect",
            order_id: 123,
            sender: "+263780070488",
            amount: "12.50",
            currency: "usd",
            mode: "LIVE",
            callback_url: "https://example.com/dischub/callback",
            redirect_url: "https://example.com/paid",
        });

        expect(result.valid).toBe(true);
        expect(result.errors).toEqual([]);
        expect(result.normalized).toMatchObject({
            integration: "redirect",
            order_id: "123",
            sender: "+263780070488",
            amount: 12.5,
            currency: "USD",
            mode: "live",
            callback_url: "https://example.com/dischub/callback",
            redirect_url: "https://example.com/paid",
        });
    });

    it("accepts all documented sender formats", () => {
        for (const sender of ["+263780070488", "0263780070488", "0780070488"]) {
            const result = validatePayment({
                order_id: "order-1",
                sender,
                amount: 1,
                currency: "USD",
            });

            expect(result.valid, sender).toBe(true);
        }
    });

    it("rejects invalid sender formats", () => {
        const result = validatePayment({
            order_id: "order-1",
            sender: "263780070488",
            amount: 1,
            currency: "USD",
        });

        expect(result.valid).toBe(false);
        expect(result.errors).toContainEqual(
            expect.objectContaining({ field: "sender" }),
        );
    });

    it("validates ecoCash numbers", () => {
        const valid = validatePayment({
            integration: "non_redirect",
            payment_method: "ecocash",
            order_id: "order-1",
            sender: "0780070488",
            amount: 1,
            currency: "USD",
            ecocash_number: "+263780000000",
        });

        expect(valid.valid).toBe(true);

        const invalid = validatePayment({
            integration: "non_redirect",
            payment_method: "ecocash",
            order_id: "order-1",
            sender: "0780070488",
            amount: 1,
            currency: "USD",
            ecocash_number: "263780000000",
        });

        expect(invalid.valid).toBe(false);
        expect(invalid.errors).toContainEqual(
            expect.objectContaining({ field: "ecocash_number" }),
        );
    });

    it("validates omari numbers", () => {
        const valid = validatePayment({
            integration: "non_redirect",
            payment_method: "omari",
            order_id: "order-1",
            sender: "0780070488",
            amount: 1,
            currency: "USD",
            omari_number: "263780000000",
        });

        expect(valid.valid).toBe(true);

        const invalid = validatePayment({
            integration: "non_redirect",
            payment_method: "omari",
            order_id: "order-1",
            sender: "0780070488",
            amount: 1,
            currency: "USD",
            omari_number: "+263780000000",
        });

        expect(invalid.valid).toBe(false);
        expect(invalid.errors).toContainEqual(
            expect.objectContaining({ field: "omari_number" }),
        );
    });

    it("requires a payment method for non-redirect payments", () => {
        const result = validatePayment({
            integration: "non_redirect",
            order_id: "order-1",
            sender: "0780070488",
            amount: 1,
            currency: "USD",
        });

        expect(result.valid).toBe(false);
        expect(result.errors).toContainEqual(
            expect.objectContaining({ field: "payment_method" }),
        );
    });

    it("rejects callback URLs that are not https", () => {
        const result = validatePayment({
            order_id: "order-1",
            sender: "0780070488",
            amount: 1,
            currency: "USD",
            callback_url: "http://example.com/callback",
        });

        expect(result.valid).toBe(false);
        expect(result.errors).toContainEqual(
            expect.objectContaining({ field: "callback_url" }),
        );
    });

    it("rejects order IDs longer than 30 characters", () => {
        const result = validatePayment({
            order_id: "x".repeat(31),
            sender: "0780070488",
            amount: 1,
            currency: "USD",
        });

        expect(result.valid).toBe(false);
        expect(result.errors).toContainEqual(
            expect.objectContaining({ field: "order_id" }),
        );
    });

    it("collects multiple errors", () => {
        const result = validatePayment({
            order_id: "",
            sender: "invalid",
            amount: 0,
            currency: "GBP",
        });

        expect(result.valid).toBe(false);
        expect(new Set(result.errors.map((error) => error.field))).toEqual(
            new Set(["order_id", "sender", "amount", "currency"]),
        );
    });
});
