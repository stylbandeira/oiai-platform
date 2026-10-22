<?php

namespace Tests\Unit\Authorization;

use App\Authorization\UserPermissions;
use App\Models\User;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    public function test_admin_receives_every_registered_permission(): void
    {
        $admin = User::factory()->make(['type' => User::TYPE_ADMIN]);

        $this->assertSame(UserPermissions::all(), $admin->permissions());
        $this->assertTrue($admin->hasPermission(UserPermissions::PRODUCT_CREATE));
    }

    public function test_client_cannot_insert_products(): void
    {
        $client = User::factory()->make(['type' => User::TYPE_CLIENT]);

        $this->assertSame([], $client->permissions());
        $this->assertFalse($client->hasPermission(UserPermissions::PRODUCT_CREATE));
    }

    public function test_permissions_are_derived_from_profile_for_existing_and_new_users(): void
    {
        $existingClient = User::factory()->make(['type' => User::TYPE_CLIENT]);
        $newAdmin = User::factory()->make(['type' => User::TYPE_ADMIN]);

        $this->assertFalse($existingClient->hasPermission(UserPermissions::PRODUCT_CREATE));
        $this->assertTrue($newAdmin->hasPermission(UserPermissions::PRODUCT_CREATE));
    }
}
