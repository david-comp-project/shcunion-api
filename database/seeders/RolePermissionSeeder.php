<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Daftar semua permissions
        $permissions = [
            "open-dashboard",
            "open-profile",
            "edit-profile",
            "edit-setting-preference",
            "edit-setting",
            "open-project",
            "open-project-detail",
            "edit-project-detail",
            "edit-evaluation-detail",
            "approve-volunteer",
            "upload-supporting-document",
            "update-in-active-project",
            "withdrawal-donation",
            "delete-project",
            "create-project",
            "send-private-chat",
            "send-group-chat",
            "open-private-chat",
            "open-group-chat",
            "leave-group-chat",
            "report-user-chat",
            "mute-private-notification",
            "mute-group-notification",
            "open-project-main-detail",
            "download-project-main-document",
            "open-calendar",
            "send-donation",
            "join-volunteer",
            "open-management-project",
            "open-management-account",
            "store-evaluation-project",
            "update-review-project",
            "approve-evaluation",
            "reject-evaluation",
            "delete-evaluation",
            "approve-evaluation-status",
            "open-modal-withdrawal",
            "update-withdrawal-status",
            "upload-transaction-proof",
            "open-user-detail",
            "open-suspend-modal",
            "suspend-user",
            "delete-account-user"
        ];

        // Buat Permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Daftar role dan permissions
        $rolePermissions = [
            "admin" => $permissions, // Admin punya semua permissions
            "verified" => [
                "open-dashboard",
                "open-profile",
                "edit-profile",
                "edit-setting-preference",
                "edit-setting",
                "open-project",
                "open-project-detail",
                "edit-project-detail",
                "edit-evaluation-detail",
                "approve-volunteer",
                "upload-supporting-document",
                "withdrawal-donation",
                "create-project",
                "send-private-chat",
                "send-group-chat",
                "open-private-chat",
                "open-group-chat",
                "leave-group-chat",
                "report-user-chat",
                "mute-private-notification",
                "mute-group-notification",
                "open-project-main-detail",
                "download-project-main-document",
                "open-calendar",
                "send-donation",
                "join-volunteer"
            ],
            "active" => [
                "open-dashboard",
                "open-profile",
                "edit-profile",
                "edit-setting-preference",
                "edit-setting",
                "open-project",
                "open-project-detail",
                "edit-project-detail",
                "edit-evaluation-detail",
                "approve-volunteer",
                "create-project",
                "open-private-chat",
                "open-group-chat",
                "leave-group-chat",
                "report-user-chat",
                "mute-private-notification",
                "mute-group-notification",
                "open-project-main-detail",
                "download-project-main-document",
                "open-calendar",
                "send-donation",
                "join-volunteer"
            ],
            "reported" => [
                "open-dashboard",
                "open-profile",
                "edit-profile",
                "edit-setting-preference",
                "edit-setting",
                "open-project",
                "open-project-detail",
                "edit-project-detail",
                "edit-evaluation-detail",
                "approve-volunteer",
                "open-private-chat",
                "open-group-chat",
                "report-user-chat",
                "mute-private-notification",
                "mute-group-notification",
                "open-project-main-detail",
                "download-project-main-document",
                "open-calendar"
            ],
            "suspended" => [
                "open-dashboard",
                "open-profile",
                "open-project",
                "open-project-detail",
                "open-private-chat",
                "open-group-chat",
                "open-calendar"
            ]
        ];

        // Buat Roles dan Assign Permissions
        foreach ($rolePermissions as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePerms);
        }
        
    }
}
