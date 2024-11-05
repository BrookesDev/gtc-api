<?php

namespace Database\Seeders;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plans = [
            [
                "name" => "Starter Plan",
                "monthly" => 10000,
                "no_of_accounts" => 1,
                "use" => "Use Only For Personal.",
                "auto" => "All Auto Match Features",
                "manual" => "Manal Matching Features",
                "reconciliation" => "Reconciliation Report PDF",
                "description" => "Account",
            ],
            [
                "name" => "Standard Plan",
                "monthly" => 20000,
                "no_of_accounts" => 10,
                "use" => "Use Only For Personal.",
                "auto" => "All Auto Match Features",
                "manual" => "Manal Match",
                "reconciliation" => "Reconciliation",
                "description" => "Accounts",
            ],
            [
                "name" => "Business Plan",
                "monthly" => 30000,
                "no_of_accounts" => 0,
                "use" => "Use For Commercial.",
                "auto" => "All Auto Match Features",
                "manual" => "Manal Matching Features",
                "reconciliation" => "Reconciliation Report PDF",
                "description" => "Unlimited accounts",
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }

    }
}
