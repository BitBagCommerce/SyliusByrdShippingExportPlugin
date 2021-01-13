<?php

/*
 * This file was created by the developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace spec\BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest;

use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\GenerateTokenByrdRequest;
use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\InvalidCredentialsException;
use PhpSpec\ObjectBehavior;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GenerateTokenByrdRequestSpec extends ObjectBehavior
{
    function let(HttpClientInterface $httpClient): void
    {
        $this->beConstructedWith("http://byrd-api-fake-url");
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(GenerateTokenByrdRequest::class);
    }

    function it_returns_get_request_method(): void
    {
        $this->getRequestMethod()->shouldReturn("POST");
    }

    function it_returns_request_url(): void
    {
        $this->getRequestUrl()->shouldReturn("http://byrd-api-fake-url/login");
    }

    function it_throws_exception_when_no_credentials_were_configured(): void
    {
        $this->shouldThrow(InvalidCredentialsException::class)->during('buildRequest', [null]);
    }

    function it_doesnt_throw_exception_when_credentials_were_configured(): void
    {
        $this->setCredentials("username", "password");
        $this->shouldNotThrow()->during('buildRequest', [null]);
    }

    function it_returns_request(): void
    {
        $this->setCredentials("username", "password");
        $this->buildRequest(null)->shouldReturn([
            "headers" => [
                "Accept" => "application/json",
                "Content-Type" => "application/json",
            ],
            "body" => '{"username":"username","password":"password"}',
        ]);
    }

    function it_returns_request_ignoring_token_passed_as_parameter(): void
    {
        $this->setCredentials("username", "password");
        $this->buildRequest("authorization-token")->shouldReturn([
            "headers" => [
                "Accept" => "application/json",
                "Content-Type" => "application/json",
            ],
            "body" => '{"username":"username","password":"password"}',
        ]);
    }
}
