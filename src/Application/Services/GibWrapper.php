<?php

namespace App\Application\Services;

use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceModel;

class GibWrapper
{
    private Gib $gib;

    public function __construct()
    {
        $this->gib = new Gib();
    }

    public function setCredentials(?string $username = null, ?string $password = null): self
    {
        $this->gib->setCredentials($username, $password);
        return $this;
    }

    public function setTestCredentials(?string $username = null, ?string $password = null): self
    {
        $this->gib->setTestCredentials($username, $password);
        return $this;
    }

    public function setToken(?string $token = null): self
    {
        $this->gib->setToken($token);
        return $this;
    }

    public function login(?string $username = null, ?string $password = null): self
    {
        $this->gib->login($username, $password);
        return $this;
    }

    public function logout(): self
    {
        $this->gib->logout();
        return $this;
    }

    public function getUserData(): array
    {
        return $this->gib->getUserData();
    }

    public function createDraft(InvoiceModel $invoice): bool
    {
        return $this->gib->createDraft($invoice);
    }

    public function getDownloadURL(string $uuid): string
    {
        return $this->gib->getDownloadURL($uuid);
    }

    public function deleteDraft(string $uuid): bool
    {
        return $this->gib->deleteDraft([$uuid]);
    }
    
    public function startSmsVerification(): ?string
    {
        return $this->gib->startSmsVerification();
    }

    public function completeSmsVerification(string $smsCode, string $operationId, array $uuids): bool
    {
        return $this->gib->completeSmsVerification($smsCode, $operationId, $uuids);
    }

    public function saveToDisk(string $uuid, ?string $dirName = null, ?string $fileName = null): string|bool
    {
        return $this->gib->saveToDisk($uuid, $dirName, $fileName);
    }

    public function getHtml(string $uuid, bool $signed = true): mixed
    {
        return $this->gib->getHtml($uuid, $signed);
    }

    public function getToken(): ?string
    {
        return $this->gib->getToken();
    }
} 