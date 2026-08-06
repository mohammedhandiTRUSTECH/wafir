<?php

namespace App\Jobs;

use App\Http\Services\ERPService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Hash;

class ERPGetUsersJob implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
    }

    /**
     * Optional mirror of ERP GetSalespersons into local users (for auth/mobile only).
     * Dashboard sales data is read live from the ERP API, not this table.
     */
    public function handle(ERPService $erp): void
    {
        $users = $erp->getSalespersons(1, false);

        foreach ($users as $user) {
            $oid = $user['oid'];
            $name = $user['name'];
            $erpId = $user['id'];
            $whsid = $user['whsid'];

            $isSupervisor = str_contains($name, 'مشرف');
            $roleId = $isSupervisor ? Role::ROLES[1]['id'] : Role::ROLES[0]['id'];

            $existing = User::query()->where('erp_id', $erpId)->first();
            if (!$existing && $oid) {
                $existing = User::query()->where('oid', $oid)->first();
            }

            if ($existing) {
                $existing->update([
                    'name' => $name,
                    'erp_id' => $erpId,
                    'oid' => $oid ?: $existing->oid,
                    'WHSid' => $whsid,
                    'role_id' => $isSupervisor ? $roleId : $existing->role_id,
                ]);
                continue;
            }

            // Skip creating empty-OID duplicates when a same-named person with OID already exists
            if (!$oid && User::query()->where('name', $name)->whereNotNull('oid')->exists()) {
                continue;
            }

            User::query()->create([
                'name' => $name,
                'oid' => $oid,
                'erp_id' => $erpId,
                'WHSid' => $whsid,
                'is_active' => true,
                'username' => (string) $erpId,
                'password' => Hash::make((string) $erpId),
                'role_id' => $roleId,
            ]);
        }
    }
}
