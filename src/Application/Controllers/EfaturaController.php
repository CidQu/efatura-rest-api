<?php

namespace App\Application\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Mlevent\Fatura\Models\InvoiceModel;
use Mlevent\Fatura\Models\InvoiceItemModel;
use Mlevent\Fatura\Enums\Currency;
use Mlevent\Fatura\Enums\InvoiceType;
use Mlevent\Fatura\Enums\Unit;
use App\Application\Services\GibService;

class EfaturaController
{
    private GibService $gibService;

    public function __construct(GibService $gibService)
    {
        $this->gibService = $gibService;
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        try {
            $this->gibService->login($username, $password);

            $userData = $this->gibService->getUserData();

            $currency = match(strtoupper($data['currency'] ?? 'TRY')) {
                'USD' => Currency::USD,
                'EUR' => Currency::EUR,
                'GBP' => Currency::GBP,
                default => Currency::TRY
            };

            $invoiceType = match(strtoupper($data['invoiceType'] ?? 'SATIS')) {
                'IADE' => InvoiceType::Iade,
                'ISTISNA' => InvoiceType::Istisna,
                'OZELMATRAH' => InvoiceType::OzelMatrah,
                'TEVKIFAT' => InvoiceType::Tevkifat,
                'KONAKLAMA' => InvoiceType::KonaklamaVergisi,
                default => InvoiceType::Satis
            };

            $invoice = new InvoiceModel(
                tarih: $data['invoiceDate'] ?? date('d/m/Y'),
                saat: $data['invoiceTime'] ?? date('H:i:s'),
                paraBirimi: $currency,
                dovizKuru: $data['exchangeRate'] ?? 0,
                faturaTipi: $invoiceType,
                vknTckn: $userData['taxNumber'] ?? '11111111110',
                vergiDairesi: $userData['taxOffice'] ?? '',
                aliciUnvan: $data['buyer']['name'] ?? '',
                aliciAdi: $data['buyer']['firstName'] ?? '',
                aliciSoyadi: $data['buyer']['lastName'] ?? '',
                mahalleSemtIlce: $data['buyer']['district'] ?? '',
                sehir: $data['buyer']['city'] ?? '',
                ulke: $data['buyer']['country'] ?? 'Türkiye',
                adres: $data['buyer']['address'] ?? '',
                siparisNumarasi: $data['orderNumber'] ?? '',
                siparisTarihi: $data['orderDate'] ?? '',
                irsaliyeNumarasi: $data['dispatchNumber'] ?? '',
                irsaliyeTarihi: $data['dispatchDate'] ?? '',
                fisNo: $data['receiptNumber'] ?? '',
                fisTarihi: $data['receiptDate'] ?? '',
                fisSaati: $data['receiptTime'] ?? '',
                fisTipi: $data['receiptType'] ?? '',
                zRaporNo: $data['zReportNumber'] ?? '',
                okcSeriNo: $data['okcSerialNumber'] ?? '',
                binaAdi: $data['buildingName'] ?? '',
                binaNo: $data['buildingNumber'] ?? '',
                kapiNo: $data['doorNumber'] ?? '',
                kasabaKoy: $data['village'] ?? '',
                postaKodu: $data['postalCode'] ?? '',
                tel: $data['buyer']['phoneNumber'] ?? '',
                fax: $data['buyer']['fax'] ?? '',
                eposta: $data['buyer']['email'] ?? '',
                not: $data['note'] ?? ''
            );

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $unit = match(strtoupper($item['unit'] ?? 'ADET')) {
                        'KG' => Unit::Kgm,
                        'LT' => Unit::Ltr,
                        'M3' => Unit::M3,
                        'MT' => Unit::Mtr,
                        'ADET' => Unit::Adet,
                        'M2' => Unit::M2,
                        'SAAT' => Unit::Saat,
                        'GUN' => Unit::Gun,
                        'AY' => Unit::Ay,
                        'YIL' => Unit::Yil,
                        default => Unit::Adet
                    };

                    $invoice->addItem(
                        new InvoiceItemModel(
                            malHizmet: $item['name'] ?? '',
                            miktar: $item['quantity'] ?? 1,
                            birim: $unit,
                            birimFiyat: $item['unitPrice'] ?? 0,
                            kdvOrani: $item['vatRate'] ?? 18,
                            iskontoOrani: $item['discountRate'] ?? 0,
                            iskontoTipi: $item['discountType'] ?? 'İskonto',
                            iskontoNedeni: $item['discountReason'] ?? ''
                        )
                    );
                }
            }

            // Create draft invoice
            if ($this->gibService->createDraft($invoice)) {
                $uuid = $invoice->getUuid();
                $this->gibService->logout();
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'uuid' => $uuid
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $this->gibService->logout();
                throw new \Exception('Failed to create draft invoice');
            }
        } catch (\Exception $e) {
            $this->gibService->logout();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'details' => $this->gibService->isTestMode() ? 'Test mode is active' : 'Production mode is active'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    public function downloadPdf(Request $request, Response $response, array $args): Response
    {
        $uuid = $args['uuid'] ?? '';
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        try {
            $this->gibService->login($username, $password);
            $downloadUrl = $this->gibService->getDownloadURL($uuid);
            $this->gibService->logout();

            $response->getBody()->write(json_encode([
                'success' => true,
                'downloadUrl' => $downloadUrl
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->gibService->logout();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'details' => $this->gibService->isTestMode() ? 'Test mode is active' : 'Production mode is active'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    public function deleteDraft(Request $request, Response $response, array $args): Response
    {
        $uuid = $args['uuid'] ?? '';
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        try {
            $this->gibService->login($username, $password);
            $result = $this->gibService->deleteDraft($uuid);
            $this->gibService->logout();

            $response->getBody()->write(json_encode([
                'success' => true,
                'result' => $result
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->gibService->logout();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'details' => $this->gibService->isTestMode() ? 'Test mode is active' : 'Production mode is active'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    public function startSmsVerification(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        try {
            $this->gibService->login($username, $password);
            $operationId = $this->gibService->startSmsVerification();
            $this->gibService->logout();
            $response->getBody()->write(json_encode([
                'success' => true,
                'operationId' => $operationId
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->gibService->logout();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'details' => $this->gibService->isTestMode() ? 'Test mode is active' : 'Production mode is active'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function signDraft(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $operationId = $data['operationId'] ?? '';
        $smsCode = $data['smsCode'] ?? '';
        $uuid = $data['uuid'] ?? '';
        try {
            $this->gibService->login($username, $password);
            $result = $this->gibService->signDraft($smsCode, $operationId, [$uuid]);
            $rowCount = $this->gibService->rowCount();
            $this->gibService->logout();
            $response->getBody()->write(json_encode([
                'success' => $result,
                'rowCount' => $rowCount
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->gibService->logout();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'details' => $this->gibService->isTestMode() ? 'Test mode is active' : 'Production mode is active'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
} 