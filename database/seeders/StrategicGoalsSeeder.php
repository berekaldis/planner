<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class StrategicGoalsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->command) {
            $this->command->info('Executing kaldis:seed-strategic-goals...');
            Artisan::call('kaldis:seed-strategic-goals', ['--force' => true], $this->command->getOutput());
        } else {
            Artisan::call('kaldis:seed-strategic-goals', ['--force' => true]);
        }
    }
}
