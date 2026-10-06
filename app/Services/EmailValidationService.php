<?php

namespace App\Services;

class EmailValidationService
{
    /**
     * Vérifie si le domaine d'une adresse email possède des enregistrements MX (serveur mail).
     * Ne garantit pas que la boîte existe, mais détecte les domaines inexistants ou mal orthographiés.
     */
    public function domainHasMxRecords(string $email): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }
        $at = strpos($email, '@');
        if ($at === false || $at === strlen($email) - 1) {
            return false;
        }
        $domain = substr($email, $at + 1);
        if ($domain === '' || strpos($domain, '.') === false) {
            return false;
        }
        return count($this->getMxRecords($domain)) > 0;
    }

    /**
     * Retourne les enregistrements MX du domaine (triés par priorité).
     * @return array<string, int> [ 'host' => priority, ... ]
     */
    public function getMxRecords(string $domain): array
    {
        if (!function_exists('dns_get_record')) {
            return [];
        }
        $records = @dns_get_record($domain, DNS_MX);
        if ($records === false || !is_array($records)) {
            return [];
        }
        $mx = [];
        foreach ($records as $r) {
            if (isset($r['target'], $r['pri'])) {
                $mx[$r['target']] = (int) $r['pri'];
            }
        }
        asort($mx);
        return $mx;
    }

    /**
     * Pour une liste d'emails, retourne ceux dont le domaine n'a pas de MX.
     * @param array<string>|iterable $emails
     * @return array<string>
     */
    public function getEmailsWithInvalidDomain($emails): array
    {
        $invalid = [];
        $checked = [];
        foreach ($emails as $email) {
            $email = trim((string) $email);
            if ($email === '') {
                continue;
            }
            $domain = $this->extractDomain($email);
            if ($domain === null) {
                $invalid[] = $email;
                continue;
            }
            if (!isset($checked[$domain])) {
                $checked[$domain] = $this->domainHasMxRecords($email);
            }
            if (!$checked[$domain]) {
                $invalid[] = $email;
            }
        }
        return array_values(array_unique($invalid));
    }

    private function extractDomain(string $email): ?string
    {
        $at = strpos($email, '@');
        if ($at === false || $at === strlen($email) - 1) {
            return null;
        }
        return strtolower(trim(substr($email, $at + 1)));
    }
}
