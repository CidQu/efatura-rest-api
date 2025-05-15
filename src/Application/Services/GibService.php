<?php

namespace App\Application\Services;

use Mlevent\Fatura\Models\InvoiceModel;
use Mlevent\Fatura\Exceptions\FaturaException;

class GibService
{
    private GibWrapper $gib;
    private bool $isTestMode = false;

    public function __construct()
    {
        $this->gib = new GibWrapper();
    }

    public function login(string $username, string $password): void
    {
        try {
            if ($username === 'admin' && $password === 'admin') {
                $this->isTestMode = true;
                $this->gib->setTestCredentials();
            } else {
                $this->gib->setCredentials($username, $password);
            }
            
            $this->gib->login();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function logout(): void
    {
        try {
            $this->gib->logout();
        } catch (\Exception $e) {
            // Ignore logout errors
        }
    }

    public function getUserData(): array
    {
        try {
            return $this->gib->getUserData();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createDraft(InvoiceModel $invoice): bool
    {
        try {
            return $this->gib->createDraft($invoice);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getDownloadURL(string $uuid): string
    {
        try {
            return $this->gib->getDownloadURL($uuid);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function deleteDraft(string $uuid): bool
    {
        try {
            return $this->gib->deleteDraft($uuid);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function isTestMode(): bool
    {
        return $this->isTestMode;
    }

    public function startSmsVerification(): ?string
    {
        return $this->gib->startSmsVerification();
    }

    public function signDraft(string $smsCode, string $operationId, array $uuids): bool
    {
        return $this->gib->completeSmsVerification($smsCode, $operationId, $uuids);
    }

    public function rowCount(): int
    {
        return 1;
    }
} 