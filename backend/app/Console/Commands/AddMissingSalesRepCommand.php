<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AddMissingSalesRepCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-missing-sales-rep-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = [
            [
                'name' => ['en' => 'Mohamed Abdelati Hasan', 'ar' => 'محمد عبدالعاطي حسن'],
                'oid' => '23d1b157-ae31-43ac-9abc-9dedbd0a4e57',
                'employee_id' => 'E000263',
                'password' => 'E000263',
                'role_id' => 1,
                'is_active' => true,
                'from_erp' => true
            ],
            [
                'name' => ['en' => 'Moustafa Mahmoud gafar', 'ar' => 'مصطفى محمود محمد جعفر'],
                'oid' => '9f5105a6-b8de-40a8-9eda-d26a32855a1c',
                'employee_id' => 'E000266',
                'password' => 'E000266',
                'role_id' => 1,
                'is_active' => true,
                'from_erp' => true
            ],
            [
                'name' => ['en' => 'Mahmoud sobhy saad', 'ar' => 'محمود صبحي عبدالحليم سعد'],
                'oid' => '2c6c5bc6-2318-46fa-a22c-1626d8218485',
                'employee_id' => 'E000265',
                'password' => 'E000265',
                'role_id' => 1,
                'is_active' => true,
                'from_erp' => true
            ],
            [
                'name' => ['en' => 'Ahmed Sabry Salama', 'ar' => 'احمد صبري محمود سلامه'],
                'oid' => 'ac53f0f4-73e0-4221-942d-151e1a399e1c',
                'employee_id' => 'E000355',
                'password' => 'E000355',
                'role_id' => 1,
                'is_active' => true,
                'from_erp' => true
            ],
        ];

        foreach ($users as $user) {
            $checkExist = User::query()->where('oid', $user['oid'])->first();
            if (!$checkExist) {
                User::query()->create($user);
            }

        }
        dump('Done');
    }
}
