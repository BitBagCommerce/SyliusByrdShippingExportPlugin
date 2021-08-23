<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace spec\BitBag\SyliusByrdShippingExportPlugin\Api\Model;

use BitBag\SyliusByrdShippingExportPlugin\Api\Model\ByrdProduct;
use PhpSpec\ObjectBehavior;

class ByrdProductSpec extends ObjectBehavior
{
    function let(): void
    {
        $this->beConstructedWith("12", "ProductName", "ProductDescription");
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(ByrdProduct::class);
    }

    function it_returns_identifier(): void
    {
        $this->getId()->shouldReturn("12");
    }

    function it_returns_product_name(): void
    {
        $this->getName()->shouldReturn("ProductName");
    }

    function it_returns_product_description(): void
    {
        $this->getDescription()->shouldReturn("ProductDescription");
    }

    function it_returns_nullable_product_description(): void
    {
        $this->beConstructedWith("12", "ProductName", null);

        $this->getDescription()->shouldReturn(null);
    }

}
