<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'agent']);
        Role::firstOrCreate(['name' => 'client']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@filipinotracks.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('admin1234'),
            ]
        );
        $admin->assignRole('admin');

        $staff = User::firstOrCreate(
            ['email' => 'staff@filipinotracks.com'],
            ['name' => 'Sample Staff', 'password' => Hash::make('staff1234')]
        );
        $staff->syncRoles(['staff']);

        $client = User::firstOrCreate(
            ['email' => 'client@filipinotracks.com'],
            ['name' => 'Sample Client', 'password' => Hash::make('client1234')]
        );
        $client->syncRoles(['client']);
    }
}
