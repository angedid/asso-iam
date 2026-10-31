<?php

namespace Tests\Unit;

use App\Constances;
use App\Domain\Model\PasswordResetInvitation;
use App\Domain\Model\UserRegistrationInvitation;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class InvitationModelsTest extends TestCase
{
    public function test_user_registration_invitation_valid_when_active_and_not_expired(): void
    {
        $invitation = new UserRegistrationInvitation([
            'active' => true,
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        $this->assertTrue($invitation->isValid());
    }

    public function test_user_registration_invitation_invalid_when_inactive(): void
    {
        $invitation = new UserRegistrationInvitation([
            'active' => false,
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        $this->assertFalse($invitation->isValid());
    }

    public function test_user_registration_invitation_invalid_when_expired(): void
    {
        $invitation = new UserRegistrationInvitation([
            'active' => true,
            'expires_at' => Carbon::now()->subDays(1),
        ]);

        $this->assertFalse($invitation->isValid());
    }

    public function test_password_reset_invitation_valid_for_usage_returns_zero(): void
    {
        $invitation = new PasswordResetInvitation([
            'active' => true,
            'expires_at' => Carbon::now()->addHours(2),
            'state' => Constances::PENDING,
            'used' => false,
        ]);

        $this->assertSame(0, $invitation->isValidForUsage());
    }

    public function test_password_reset_invitation_returns_1_when_inactive(): void
    {
        $invitation = new PasswordResetInvitation([
            'active' => false,
            'expires_at' => Carbon::now()->addHours(2),
            'state' => Constances::PENDING,
            'used' => false,
        ]);

        $this->assertSame(1, $invitation->isValidForUsage());
    }

    public function test_password_reset_invitation_returns_2_when_expired(): void
    {
        $invitation = new PasswordResetInvitation([
            'active' => true,
            'expires_at' => Carbon::now()->subMinutes(10),
            'state' => Constances::PENDING,
            'used' => false,
        ]);

        $this->assertSame(2, $invitation->isValidForUsage());
    }

    public function test_password_reset_invitation_returns_3_when_state_not_pending(): void
    {
        $invitation = new PasswordResetInvitation([
            'active' => true,
            'expires_at' => Carbon::now()->addHours(2),
            'state' => Constances::CONFIRM,
            'used' => false,
        ]);

        $this->assertSame(3, $invitation->isValidForUsage());
    }

    public function test_password_reset_invitation_returns_4_when_already_used(): void
    {
        $invitation = new PasswordResetInvitation([
            'active' => true,
            'expires_at' => Carbon::now()->addHours(2),
            'state' => Constances::PENDING,
            'used' => true,
        ]);

        $this->assertSame(4, $invitation->isValidForUsage());
    }
}
