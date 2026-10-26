<?php

namespace Ngoni\DischubPhp;

use Ngoni\DischubPhp\NonRedirect\NonRedirectIntegration;
use Ngoni\DischubPhp\Redirect\RedirectIntegration;

class DischubService
{
    protected RedirectIntegration $redirect;

    protected NonRedirectIntegration $nonRedirect;

    public function __construct(
        RedirectIntegration $redirect,
        NonRedirectIntegration $nonRedirect
    ) {
        $this->redirect = $redirect;
        $this->nonRedirect = $nonRedirect;
    }

    /**
     * The redirect integration (customer is redirected to Dischub).
     */
    public function redirect(): RedirectIntegration
    {
        return $this->redirect;
    }

    /**
     * The non-redirect integration (EcoCash, InnBucks, Omari, ZimSwitch).
     */
    public function nonRedirect(): NonRedirectIntegration
    {
        return $this->nonRedirect;
    }

    /**
     * Backwards-compatible alias for redirect()->createOrder().
     */
    public function createOrder(array $data): array
    {
        return $this->redirect->createOrder($data);
    }

    /**
     * Backwards-compatible alias for redirect()->getPaymentUrl().
     */
    public function getPaymentUrl($orderId): string
    {
        return $this->redirect->getPaymentUrl($orderId);
    }

    /**
     * Backwards-compatible alias for redirect()->getPaymentStatus().
     */
    public function getPaymentStatus($orderId): array
    {
        return $this->redirect->getPaymentStatus($orderId);
    }
}
