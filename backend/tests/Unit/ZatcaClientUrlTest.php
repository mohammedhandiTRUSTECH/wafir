<?php

namespace Tests\Unit;

use App\Integrations\Zatca\ZatcaClient;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Pure unit tests for ZatcaClient URL construction and date normalisation.
 * No real HTTP requests are made.
 */
class ZatcaClientUrlTest extends TestCase
{
    private ZatcaClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('erp.base_url',             'http://example.com/ZatcaIntegrationApi/api/Zatca/');
        Config::set('erp.authentication_route', '/Authentication/Authenticate');
        Config::set('erp.users_list_route',     'GetSalespersons?CompanyID=');
        Config::set('erp.sales_per_month_url',  'http://example.com/ZatcaIntegrationApi/api/Zatca/GetNetSales/');
        Config::set('erp.authentication_username', 'test_user');
        Config::set('erp.authentication_password', 'test_pass');

        $this->client = new ZatcaClient();
    }

    // -------------------------------------------------------------------------
    // authUrl
    // -------------------------------------------------------------------------

    public function test_auth_url_combines_base_and_route(): void
    {
        $url = $this->client->authUrl();

        $this->assertStringContainsString('Authentication/Authenticate', $url);
        $this->assertStringStartsWith('http://example.com/', $url);
    }

    // -------------------------------------------------------------------------
    // usersUrl
    // -------------------------------------------------------------------------

    public function test_users_url_appends_company_id(): void
    {
        $url = $this->client->usersUrl(1);

        $this->assertStringContainsString('GetSalespersons', $url);
        $this->assertStringContainsString('CompanyID=1', $url);
    }

    public function test_users_url_uses_supplied_company_id(): void
    {
        $url = $this->client->usersUrl(5);

        $this->assertStringContainsString('CompanyID=5', $url);
    }

    // -------------------------------------------------------------------------
    // netSalesUrl – query parameters
    // -------------------------------------------------------------------------

    public function test_net_sales_url_contains_sales_person_id(): void
    {
        $url = $this->client->netSalesUrl('OID-123', '2025-01-01', '2025-01-31');

        $this->assertStringContainsString('SalesPersonID=OID-123', $url);
    }

    public function test_net_sales_url_contains_company_id(): void
    {
        $url = $this->client->netSalesUrl('OID-123', '2025-01-01', '2025-01-31', 2);

        $this->assertStringContainsString('CompanyID=2', $url);
    }

    public function test_net_sales_url_defaults_to_company_1(): void
    {
        $url = $this->client->netSalesUrl('OID-123', '2025-01-01', '2025-01-31');

        $this->assertStringContainsString('CompanyID=1', $url);
    }

    public function test_net_sales_url_contains_from_and_to_date(): void
    {
        $url = $this->client->netSalesUrl('OID-123', '2025-03-15', '2025-03-20');

        $this->assertStringContainsString('FromDate=', $url);
        $this->assertStringContainsString('ToDate=', $url);
    }

    public function test_net_sales_url_url_encodes_special_chars_in_oid(): void
    {
        $url = $this->client->netSalesUrl('OID 1/2', '2025-01-01', '2025-01-31');

        $this->assertStringNotContainsString('OID 1/2', $url);
        $this->assertStringContainsString('SalesPersonID=', $url);
    }

    // -------------------------------------------------------------------------
    // Date normalisation – erpFromDate / erpToDate
    // -------------------------------------------------------------------------

    public function test_erp_from_date_uses_midnight(): void
    {
        $result = $this->client->erpFromDate('2025-06-01');

        $this->assertSame('2025-06-01T00:00:00', $result);
    }

    public function test_erp_to_date_uses_end_of_day(): void
    {
        $result = $this->client->erpToDate('2025-06-30');

        $this->assertSame('2025-06-30T23:59:59', $result);
    }

    public function test_erp_from_date_normalises_full_datetime_input(): void
    {
        // A full datetime string should still produce a midnight anchor for that date
        $result = $this->client->erpFromDate('2025-06-01 14:30:00');

        $this->assertSame('2025-06-01T00:00:00', $result);
    }

    public function test_erp_to_date_normalises_full_datetime_input(): void
    {
        $result = $this->client->erpToDate('2025-06-30 08:00:00');

        $this->assertSame('2025-06-30T23:59:59', $result);
    }

    // -------------------------------------------------------------------------
    // netSalesUrl date bounds are inclusive (midnight / end-of-day)
    // -------------------------------------------------------------------------

    public function test_net_sales_url_from_date_is_midnight(): void
    {
        $url = $this->client->netSalesUrl('X', '2025-04-01', '2025-04-30');

        $this->assertStringContainsString(urlencode('2025-04-01T00:00:00'), $url);
    }

    public function test_net_sales_url_to_date_is_end_of_day(): void
    {
        $url = $this->client->netSalesUrl('X', '2025-04-01', '2025-04-30');

        $this->assertStringContainsString(urlencode('2025-04-30T23:59:59'), $url);
    }
}
