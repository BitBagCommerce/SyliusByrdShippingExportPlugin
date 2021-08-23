<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace spec\BitBag\SyliusByrdShippingExportPlugin\Api;

use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\GenerateTokenByrdRequestInterface;
use BitBag\SyliusByrdShippingExportPlugin\Api\RequestSender;
use PhpSpec\ObjectBehavior;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RequestSenderSpec extends ObjectBehavior
{
    function let(HttpClientInterface $httpClient): void
    {
        $this->beConstructedWith($httpClient);
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(RequestSender::class);
    }

    function it_sends_request(
        GenerateTokenByrdRequestInterface $request,
        ResponseInterface $response,
        HttpClientInterface $httpClient
    ): void {
        $request->getRequestMethod()->willReturn('POST');
        $request->getRequestUrl()->willReturn('//request-url');

        $request->buildRequest(null)->willReturn([
            'somedata' => false,
        ]);

        $httpClient->request("POST", "//request-url", [
            'somedata' => false,
        ])->willReturn($response);

        $this->send($request)->shouldReturn($response);
    }

    function it_sends_authorized_request(
        GenerateTokenByrdRequestInterface $request,
        ResponseInterface $response,
        HttpClientInterface $httpClient
    ): void {
        $request->getRequestMethod()->willReturn('POST');
        $request->getRequestUrl()->willReturn('//request-url');

        $request->buildRequest("authorization-token")->willReturn([
            'somedata' => false,
        ]);

        $httpClient->request("POST", "//request-url", [
            'somedata' => false,
            'headers' => [
                'Authorization' => 'Bearer authorization-token'
            ],
        ])->willReturn($response);

        $this->sendAuthorized($request, "authorization-token")->shouldReturn($response);
    }
}
