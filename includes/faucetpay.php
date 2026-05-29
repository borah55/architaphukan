<?php
/**
 * FaucetPay API client.
 *
 * Docs: https://faucetpay.io/merchant/webmaster/api
 *
 * Endpoints used:
 *   POST https://faucetpay.io/api/v1/send
 *   POST https://faucetpay.io/api/v1/checkbalance
 *   POST https://faucetpay.io/api/v1/currencies
 *   POST https://faucetpay.io/api/v1/payouts
 */
if (!defined('SITE_ROOT')) { http_response_code(500); exit; }

class FaucetPay
{
    const BASE = 'https://faucetpay.io/api/v1/';

    private string $apiKey;
    private string $currency;

    public function __construct(?string $apiKey = null, ?string $currency = null)
    {
        $this->apiKey   = $apiKey   ?? (string) setting('faucetpay_api_key', '');
        $this->currency = $currency ?? (string) setting('faucetpay_currency', 'DOGE');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Send a payout to a FaucetPay user.
     *
     * @param string $toEmail  Recipient FaucetPay email or wallet address.
     * @param string $amount   Amount in main unit (e.g. DOGE) as decimal string.
     * @param string $ip       User IP for FaucetPay anti-bot logs.
     * @return array           ['ok'=>bool, 'data'=>array, 'error'=>string]
     */
    public function send(string $toEmail, string $amount, string $ip = ''): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'FaucetPay API key not configured', 'data' => []];
        }
        return $this->call('send', [
            'api_key'   => $this->apiKey,
            'amount'    => $amount,          // main unit
            'to'        => $toEmail,
            'currency'  => $this->currency,
            'ip_address'=> $ip,
        ]);
    }

    public function checkBalance(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'FaucetPay API key not configured', 'data' => []];
        }
        return $this->call('checkbalance', [
            'api_key'  => $this->apiKey,
            'currency' => $this->currency,
        ]);
    }

    public function currencies(): array
    {
        return $this->call('currencies', []);
    }

    public function payouts(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'FaucetPay API key not configured', 'data' => []];
        }
        return $this->call('payouts', ['api_key' => $this->apiKey]);
    }

    /**
     * Low level POST call to FaucetPay.
     */
    private function call(string $endpoint, array $params): array
    {
        $url = self::BASE . ltrim($endpoint, '/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body  = curl_exec($ch);
        $err   = curl_error($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return ['ok' => false, 'error' => 'cURL error: ' . $err, 'data' => [], 'http_code' => $code];
        }

        $j = json_decode($body, true);
        if (!is_array($j)) {
            return ['ok' => false, 'error' => 'Invalid JSON from FaucetPay', 'raw' => $body, 'data' => [], 'http_code' => $code];
        }

        // FaucetPay returns "status": 200 on success and a "message" / "payout_id".
        $status = isset($j['status']) ? (int) $j['status'] : 0;
        $ok = ($status === 200);
        return [
            'ok'        => $ok,
            'error'     => $ok ? '' : (string) ($j['message'] ?? 'FaucetPay error'),
            'data'      => $j,
            'http_code' => $code,
        ];
    }
}
