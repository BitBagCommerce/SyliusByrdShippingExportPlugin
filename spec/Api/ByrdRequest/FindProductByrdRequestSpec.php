<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace spec\BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest;

use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\FindProductByrdRequest;
use PhpSpec\ObjectBehavior;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FindProductByrdRequestSpec extends ObjectBehavior
{
    function let(HttpClientInterface $httpClient): void
    {
        $this->beConstructedWith("http://byrd-api-fake-url");
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(FindProductByrdRequest::class);
    }

    function it_returns_get_request_method(): void
    {
        $this->getRequestMethod()->shouldReturn("GET");
    }

    function it_returns_request_url(): void
    {
        $this->getRequestUrl()->shouldReturn("http://byrd-api-fake-url/warehouse/products");
    }

    function it_builds_request_without_using_authorization_token(): void
    {
        $this->buildRequest("authorization-token")->shouldReturn(["headers" => [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
        ]]);
    }

    function it_accepts_nullable_authorization_token(): void
    {
        $this->buildRequest(null)->shouldReturn(["headers" => [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
        ]]);
    }
}
