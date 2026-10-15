<?php
declare(strict_types=1);

namespace Victor\ImmutableQuote\Test\Unit\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\TestCase;
use Victor\ImmutableQuote\Plugin\PreventCartModificationPlugin;
use Victor\ImmutableQuote\Service\QuoteMutationGuard;

class PreventCartModificationPluginTest extends TestCase
{
    private $mutationGuardMock;
    private PreventCartModificationPlugin $plugin;

    protected function setUp(): void
    {
        $this->mutationGuardMock = $this->createMock(QuoteMutationGuard::class);
        $this->plugin = new PreventCartModificationPlugin($this->mutationGuardMock);
    }

    public function testBeforeSaveDelegatesToMutationGuardForExistingQuote(): void
    {
        $repositoryMock = $this->createMock(CartRepositoryInterface::class);
        $cartMock = $this->createMock(CartInterface::class);
        $cartMock->method('getId')->willReturn(15);

        $this->mutationGuardMock->expects($this->once())
            ->method('validateMutation')
            ->with(15, QuoteMutationGuard::CONTEXT_COMMERCIAL);

        $result = $this->plugin->beforeSave($repositoryMock, $cartMock);
        $this->assertNull($result);
    }

    public function testBeforeSaveBypassesWhenQuoteIdIsZero(): void
    {
        $repositoryMock = $this->createMock(CartRepositoryInterface::class);
        $cartMock = $this->createMock(CartInterface::class);
        $cartMock->method('getId')->willReturn(0);

        $this->mutationGuardMock->expects($this->never())->method('validateMutation');

        $result = $this->plugin->beforeSave($repositoryMock, $cartMock);
        $this->assertNull($result);
    }
}
