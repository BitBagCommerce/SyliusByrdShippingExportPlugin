<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace spec\BitBag\SyliusByrdShippingExportPlugin\Api\Exception;

use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\AuthorizationIssueException;
use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\ByrdApiException;
use PhpSpec\ObjectBehavior;

class AuthorizationIssueExceptionSpec extends ObjectBehavior
{
    function it_is_initializable(): void
    {
        $this->shouldHaveType(AuthorizationIssueException::class);
        $this->shouldHaveType(ByrdApiException::class);
    }
}
