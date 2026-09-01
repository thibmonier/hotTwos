<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Reminder;

use App\Domain\Reminder\ReminderChannel;
use App\Domain\Reminder\ReminderRule;
use App\Domain\Tenant\TenantId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ReminderRuleTest extends TestCase
{
    public function testDefaultRuleIsDiscreetAndActive(): void
    {
        $rule = ReminderRule::default(TenantId::generate());

        self::assertSame(1, $rule->initialDelayDays());
        self::assertSame(3, $rule->frequencyDays());
        self::assertSame(ReminderChannel::IN_APP, $rule->channel());
        self::assertTrue($rule->escalationEnabled());
        self::assertTrue($rule->isActive());
    }

    public function testReconfigureUpdatesParameters(): void
    {
        $rule = ReminderRule::default(TenantId::generate());

        $rule->reconfigure(2, 5, ReminderChannel::BOTH, false);

        self::assertSame(2, $rule->initialDelayDays());
        self::assertSame(5, $rule->frequencyDays());
        self::assertSame(ReminderChannel::BOTH, $rule->channel());
        self::assertFalse($rule->escalationEnabled());
    }

    public function testDeactivateAndActivateToggleGlobalSwitch(): void
    {
        $rule = ReminderRule::default(TenantId::generate());

        $rule->deactivate();
        self::assertFalse($rule->isActive());

        $rule->activate();
        self::assertTrue($rule->isActive());
    }

    public function testFrequencyBelowOneIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ReminderRule(TenantId::generate(), 1, 0, ReminderChannel::IN_APP, true, true);
    }

    public function testDelayAboveBoundIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ReminderRule(TenantId::generate(), 31, 3, ReminderChannel::IN_APP, true, true);
    }

    public function testChannelInclusionHelpers(): void
    {
        self::assertTrue(ReminderChannel::BOTH->includesInApp());
        self::assertTrue(ReminderChannel::BOTH->includesEmail());
        self::assertTrue(ReminderChannel::EMAIL->includesEmail());
        self::assertFalse(ReminderChannel::EMAIL->includesInApp());
        self::assertTrue(ReminderChannel::IN_APP->includesInApp());
        self::assertFalse(ReminderChannel::IN_APP->includesEmail());
    }
}
