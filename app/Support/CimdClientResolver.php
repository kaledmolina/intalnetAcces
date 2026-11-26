<?php

namespace App\Support;

use App\Models\OAuthClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Client ID Metadata Documents (CIMD): el `client_id` es una URL https que
 * apunta a un JSON con la identidad del cliente (nombre, redirect_uris...).
 *
 * Claude y ChatGPT lo prefieren cuando el servidor lo anuncia, así que aquí
 * se descarga y valida el documento. DCR sigue disponible como respaldo.
 */
class CimdClientResolver
{
    public const MAX_DOCUMENT_BYTES = 65_536;

    /**
     * Descarga, valida y guarda el cliente descrito por el documento.
     */
    public function resolve(string $clientId): ?OAuthClient
    {
        if (! $this->isDocumentUrl($clientId)) {
            return null;
        }

        $document = $this->fetchDocument($clientId);

        if ($document === null) {
            return null;
        }

        // El documento debe identificarse con la misma URL que lo publica.
        if (($document['client_id'] ?? null) !== $clientId) {
            return null;
        }

        $redirectUris = array_values(array_filter(
            is_array($document['redirect_uris'] ?? null) ? $document['redirect_uris'] : [],
            fn (mixed $uri): bool => is_string($uri) && McpOAuth::isValidRedirectUri($uri),
        ));

        if ($redirectUris === [] || ! $this->supportsPublicClient($document)) {
            return null;
        }

        $name = trim((string) ($document['client_name'] ?? ''));
        $name = $name !== '' ? $name : (McpOAuth::displayDestination($clientId) ?: 'Cliente MCP');

        return OAuthClient::updateOrCreate(
            ['client_id' => $clientId],
            [
                'name' => Str::limit($name, 120, ''),
                'redirect_uris' => $redirectUris,
            ],
        );
    }

    public function isDocumentUrl(string $clientId): bool
    {
        if ($clientId === '' || strlen($clientId) > 1024) {
            return false;
        }

        $parts = parse_url($clientId);

        return $parts !== false
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && ! empty($parts['host'])
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }

    /**
     * ChatGPT publica private_key_jwt como método preferido pero anuncia
     * `none` en su lista, y elegirá `none` porque es lo único que ofrecemos
     * en los metadatos del servidor de autorización.
     *
     * @param  array<string, mixed>  $document
     */
    private function supportsPublicClient(array $document): bool
    {
        $methods = $document['token_endpoint_auth_methods_supported'] ?? null;

        if (is_array($methods) && $methods !== []) {
            return in_array('none', $methods, true);
        }

        return (string) ($document['token_endpoint_auth_method'] ?? 'none') === 'none';
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchDocument(string $url): ?array
    {
        if (! $this->isPublicHttpsUrl($url)) {
            return null;
        }

        try {
            $response = Http::withOptions([
                'allow_redirects' => false,
                'connect_timeout' => 4,
            ])
                ->timeout(6)
                ->acceptJson()
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        $body = $response->successful() ? $response->body() : '';

        if ($body === '' || strlen($body) > self::MAX_DOCUMENT_BYTES) {
            return null;
        }

        $document = json_decode($body, true);

        return is_array($document) ? $document : null;
    }

    /**
     * Solo https, sin credenciales, puerto 443 y con todas sus IP públicas.
     * Evita que el servidor descargue documentos de la red interna (SSRF).
     */
    protected function isPublicHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            return false;
        }

        if (isset($parts['port']) && (int) $parts['port'] !== 443) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            return false;
        }

        $addresses = $this->resolveAddresses($host);

        if ($addresses === []) {
            return false;
        }

        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    protected function resolveAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $addresses = [];

        foreach (dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if (is_string($ip) && $ip !== '') {
                $addresses[] = $ip;
            }
        }

        if ($addresses === []) {
            $fallback = gethostbynamel($host);

            if (is_array($fallback)) {
                $addresses = $fallback;
            }
        }

        return array_values(array_unique($addresses));
    }
}
