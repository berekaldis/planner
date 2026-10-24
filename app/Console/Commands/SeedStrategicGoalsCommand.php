<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SeedStrategicGoalsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kaldis:seed-strategic-goals {--force : Run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear and re-seed the 58 official strategic goals G01-G58 with department assignments and monthly activations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->isProduction() && !$this->option('force')) {
            if (!$this->confirm('This will clear and reset the 58 annual strategic goals. Continue?')) {
                $this->warn('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info('Resetting and seeding the 58 official strategic goals...');

        // 1. Ensure required departments exist
        $departmentsToAdd = [
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Retail Operations', 'code' => 'OPS'],
            ['name' => 'Marketing & Brand Strategy', 'code' => 'MKT'],
            ['name' => 'Supply Chain & Procurement', 'code' => 'SCM'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Finance & Accounting', 'code' => 'FIN'],
            ['name' => 'Quality Assurance & Roastery', 'code' => 'QAR'],
            ['name' => 'Project Management', 'code' => 'PM'],
            ['name' => 'Training & Development', 'code' => 'TND'],
            ['name' => 'Product R&D & Innovation', 'code' => 'RND'],
            ['name' => 'OSH & Environmental Safety', 'code' => 'SAFE'],
            ['name' => 'Logistics & Maintenance', 'code' => 'LOG'],
            ['name' => 'Internal Audit & Compliance', 'code' => 'AUD'],
            ['name' => 'Business Intelligence & Analytics', 'code' => 'BI'],
            ['name' => 'Food & Beverage', 'code' => 'FB'],
            ['name' => 'Business Development', 'code' => 'BD'],
        ];

        foreach ($departmentsToAdd as $d) {
            $exists = DB::table('departments')->where('department_code', $d['code'])->exists();
            if (!$exists) {
                DB::table('departments')->insert([
                    'department_name' => $d['name'],
                    'department_code' => $d['code'],
                    'active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $deptMap = DB::table('departments')->pluck('id', 'department_code')->toArray();
        $adminUser = DB::table('users')->where('username', 'admin')->first();
        $adminId = $adminUser ? $adminUser->id : 1;
        $planningYear = kaldis_setting('current_planning_year', '2019 E.C.');

        // 2. Clear existing strategic goals & activations safely
        $this->output->write('Clearing existing annual goals and activations... ');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('monthly_strategy_activations')->truncate();
        DB::table('annual_goal_departments')->truncate();
        DB::table('annual_goals')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->info('DONE.');

        // 3. Load clean 58 goals data
        $goals = array (
  0 => 
  array (
    'code' => 'G01',
    'title' => 'Renovate 2 branches per month to new Kaldi\'s/EFDA standards; visible logo added to each',
    'dod' => '24 branches renovated by year-end; logo visible at every renovated branch',
    'target' => '24 branches renovated by year-end; logo visible at every renovated branch',
    'priority' => 'HIGH',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'PM',
    'all_depts' => 
    array (
      0 => 'PM',
    ),
    'notes' => 'Project Manager',
  ),
  1 => 
  array (
    'code' => 'G02',
    'title' => 'Open 5 new stores (excluding replacements for closed branches)',
    'dod' => '5 new stores trading by year-end',
    'target' => '5 new stores trading by year-end',
    'priority' => 'HIGH',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'PM',
    'all_depts' => 
    array (
      0 => 'PM',
      1 => 'OPS',
      2 => 'MKT',
    ),
    'notes' => 'Project Manager / Operations / Marketing',
  ),
  2 => 
  array (
    'code' => 'G03',
    'title' => 'Rebrand 12 branches with new branding and interior design',
    'dod' => '12 branches fully rebranded by year-end',
    'target' => '12 branches fully rebranded by year-end',
    'priority' => 'HIGH',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'PM',
    'all_depts' => 
    array (
      0 => 'PM',
      1 => 'MKT',
    ),
    'notes' => 'Project Manager / Marketing',
  ),
  3 => 
  array (
    'code' => 'G04',
    'title' => 'All staff trained: new manuals, PowerPoints, pre/post assessments, follow-up trainings per QA guideline',
    'dod' => '100% of staff assessed pre and post; follow-up training calendar running',
    'target' => '100% of staff assessed pre and post; follow-up training calendar running',
    'priority' => 'HIGH',
    'perspective' => 'Learning & Growth',
    'weight' => 1.72,
    'primary_dept' => 'TND',
    'all_depts' => 
    array (
      0 => 'TND',
    ),
    'notes' => 'Training & Development',
  ),
  4 => 
  array (
    'code' => 'G05',
    'title' => 'All frontline staff inspected 2x per month; reward/probation protocol applied',
    'dod' => 'Every frontline staff member has 2 inspection records per month; actions logged',
    'target' => 'Every frontline staff member has 2 inspection records per month; actions logged',
    'priority' => 'HIGH',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'QAR',
    'all_depts' => 
    array (
      0 => 'QAR',
    ),
    'notes' => 'Quality Assurance',
  ),
  5 => 
  array (
    'code' => 'G06',
    'title' => 'Branch managers and shift leaders complete AM + PM checklists daily',
    'dod' => 'Checklist completion rate visible daily; misses escalated',
    'target' => 'Checklist completion rate visible daily; misses escalated',
    'priority' => 'HIGH',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'OPS',
    'all_depts' => 
    array (
      0 => 'OPS',
    ),
    'notes' => 'Operations',
  ),
  6 => 
  array (
    'code' => 'G07',
    'title' => 'Roasted & packed coffee sold at branches and supermarkets',
    'dod' => 'Product live at all branches + supermarket accounts opened',
    'target' => 'Product live at all branches + supermarket accounts opened',
    'priority' => 'HIGH',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'MKT',
    'all_depts' => 
    array (
      0 => 'MKT',
      1 => 'SCM',
      2 => 'LOG',
    ),
    'notes' => 'Marketing / Import-Export',
  ),
  7 => 
  array (
    'code' => 'G08',
    'title' => '1 franchise store launched',
    'dod' => 'First franchise location open and trading',
    'target' => 'First franchise location open and trading',
    'priority' => 'MEDIUM',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'BD',
    'all_depts' => 
    array (
      0 => 'BD',
    ),
    'notes' => 'DGM / Business Development',
  ),
  8 => 
  array (
    'code' => 'G09',
    'title' => 'Kitchens removed from branches; fast-heating ovens installed; prep moved to production',
    'dod' => 'All branch kitchens converted; central production supplying',
    'target' => 'All branch kitchens converted; central production supplying',
    'priority' => 'HIGH',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'OPS',
    'all_depts' => 
    array (
      0 => 'OPS',
      1 => 'SCM',
      2 => 'QAR',
    ),
    'notes' => 'Operations / Import / Production',
  ),
  9 => 
  array (
    'code' => 'G10',
    'title' => 'Soda discontinued; replaced with fizzy drinks',
    'dod' => 'Soda phased out at all branches; fizzy drink line live',
    'target' => 'Soda phased out at all branches; fizzy drink line live',
    'priority' => 'MEDIUM',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'MKT',
    'all_depts' => 
    array (
      0 => 'MKT',
      1 => 'SCM',
      2 => 'OPS',
    ),
    'notes' => 'Marketing / Import / Operations',
  ),
  10 => 
  array (
    'code' => 'G11',
    'title' => '30+ gym partnerships with member discount card',
    'dod' => '30 gyms signed (~3 per month run-rate)',
    'target' => '30 gyms signed (~3 per month run-rate)',
    'priority' => 'MEDIUM',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'MKT',
    'all_depts' => 
    array (
      0 => 'MKT',
    ),
    'notes' => 'Marketing',
  ),
  11 => 
  array (
    'code' => 'G12',
    'title' => 'Coffee subscription program implemented',
    'dod' => 'Subscription live with enrollment tracked monthly',
    'target' => 'Subscription live with enrollment tracked monthly',
    'priority' => 'MEDIUM',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'MKT',
    'all_depts' => 
    array (
      0 => 'MKT',
    ),
    'notes' => 'Marketing',
  ),
  12 => 
  array (
    'code' => 'G13',
    'title' => 'Live on 3 delivery apps',
    'dod' => 'Orders flowing from 3 apps',
    'target' => 'Orders flowing from 3 apps',
    'priority' => 'MEDIUM',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'MKT',
    'all_depts' => 
    array (
      0 => 'MKT',
      1 => 'IT',
      2 => 'OPS',
    ),
    'notes' => 'Marketing / IT / Operations',
  ),
  13 => 
  array (
    'code' => 'G14',
    'title' => '40+ event partnerships (catering, booths)',
    'dod' => '40 events done (~4 per month run-rate)',
    'target' => '40 events done (~4 per month run-rate)',
    'priority' => 'MEDIUM',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'MKT',
    'all_depts' => 
    array (
      0 => 'MKT',
      1 => 'OPS',
    ),
    'notes' => 'Marketing / Operations',
  ),
  14 => 
  array (
    'code' => 'G15',
    'title' => 'Bundle deals available at cafes (coffee + pastry)',
    'dod' => 'Bundles on menu at all branches',
    'target' => 'Bundles on menu at all branches',
    'priority' => 'MEDIUM',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'MKT',
    'all_depts' => 
    array (
      0 => 'MKT',
    ),
    'notes' => 'Marketing',
  ),
  15 => 
  array (
    'code' => 'G16',
    'title' => 'Loyalty program, customer database, birthday gift system solidified',
    'dod' => 'System live; birthday gifts sending automatically',
    'target' => 'System live; birthday gifts sending automatically',
    'priority' => 'MEDIUM',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'MKT',
    'all_depts' => 
    array (
      0 => 'MKT',
      1 => 'IT',
    ),
    'notes' => 'Marketing / IT',
  ),
  16 => 
  array (
    'code' => 'G17',
    'title' => 'Seasonal menus: rainy season, spring/new year, mango season',
    'dod' => 'Each seasonal menu launched on time',
    'target' => 'Each seasonal menu launched on time',
    'priority' => 'MEDIUM',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'MKT',
    'all_depts' => 
    array (
      0 => 'MKT',
    ),
    'notes' => 'Marketing',
  ),
  17 => 
  array (
    'code' => 'G18',
    'title' => '4 strategic partnerships with trendy companies',
    'dod' => '4 partnerships signed (1 per quarter)',
    'target' => '4 partnerships signed (1 per quarter)',
    'priority' => 'MEDIUM',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'MKT',
    'all_depts' => 
    array (
      0 => 'MKT',
    ),
    'notes' => 'Marketing',
  ),
  18 => 
  array (
    'code' => 'G19',
    'title' => 'Recipe on file for every menu item, approved and uploaded to CNET without error',
    'dod' => '100% menu coverage; zero recipe errors on CNET',
    'target' => '100% menu coverage; zero recipe errors on CNET',
    'priority' => 'HIGH',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'FB',
    'all_depts' => 
    array (
      0 => 'FB',
      1 => 'QAR',
    ),
    'notes' => 'F&B (Tseday) / Production',
  ),
  19 => 
  array (
    'code' => 'G20',
    'title' => 'Cost % goal set per department (e.g., hot beverage 21-24%)',
    'dod' => 'Targets published; tracked monthly on dashboard',
    'target' => 'Targets published; tracked monthly on dashboard',
    'priority' => 'MEDIUM',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'FB',
    'all_depts' => 
    array (
      0 => 'FB',
    ),
    'notes' => 'F&B (Tseday)',
  ),
  20 => 
  array (
    'code' => 'G21',
    'title' => 'Price adjustments per standard every 3 months',
    'dod' => '4 pricing reviews completed on schedule',
    'target' => '4 pricing reviews completed on schedule',
    'priority' => 'MEDIUM',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'FB',
    'all_depts' => 
    array (
      0 => 'FB',
    ),
    'notes' => 'F&B (Tseday)',
  ),
  21 => 
  array (
    'code' => 'G22',
    'title' => 'COGS dashboard set on SIV (not recipe)',
    'dod' => 'Dashboard live; monthly SIV vs recipe gap reviewed',
    'target' => 'Dashboard live; monthly SIV vs recipe gap reviewed',
    'priority' => 'HIGH',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'SCM',
    'all_depts' => 
    array (
      0 => 'SCM',
      1 => 'FIN',
      2 => 'BI',
    ),
    'notes' => 'Supply Chain / Finance / BI',
  ),
  22 => 
  array (
    'code' => 'G23',
    'title' => 'Financial analyst team hired: balance sheet, cash flow, investment planning, cost analysis',
    'dod' => 'Team in seat with JDs/KPIs; monthly analysis pack delivered',
    'target' => 'Team in seat with JDs/KPIs; monthly analysis pack delivered',
    'priority' => 'HIGH',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'FIN',
    'all_depts' => 
    array (
      0 => 'FIN',
    ),
    'notes' => 'Finance',
  ),
  23 => 
  array (
    'code' => 'G24',
    'title' => 'Standard set for SIV cost percentage',
    'dod' => 'Standard published; branches measured against it',
    'target' => 'Standard published; branches measured against it',
    'priority' => 'MEDIUM',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'SCM',
    'all_depts' => 
    array (
      0 => 'SCM',
      1 => 'FIN',
    ),
    'notes' => 'Supply Chain / Finance',
  ),
  24 => 
  array (
    'code' => 'G25',
    'title' => 'Contract renewals every 3 months with price research',
    'dod' => 'All supplier contracts on 3-month cycle; renewal log kept',
    'target' => 'All supplier contracts on 3-month cycle; renewal log kept',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'SCM',
    'all_depts' => 
    array (
      0 => 'SCM',
    ),
    'notes' => 'Supply Chain',
  ),
  25 => 
  array (
    'code' => 'G26',
    'title' => 'Minimum 3 proformas collected and stored for every purchase',
    'dod' => 'Zero purchases without 3 proformas on file',
    'target' => 'Zero purchases without 3 proformas on file',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'SCM',
    'all_depts' => 
    array (
      0 => 'SCM',
    ),
    'notes' => 'Supply Chain',
  ),
  26 => 
  array (
    'code' => 'G27',
    'title' => 'Purchase/supplier database created for all items',
    'dod' => 'Database live covering every item Kaldi\'s buys',
    'target' => 'Database live covering every item Kaldi\'s buys',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'SCM',
    'all_depts' => 
    array (
      0 => 'SCM',
      1 => 'BI',
    ),
    'notes' => 'Supply Chain / BI',
  ),
  27 => 
  array (
    'code' => 'G28',
    'title' => 'Raw material reception centralized at production (except milk, bottled bevs) with QA control',
    'dod' => 'Centralized reception running; quality + quantity signed at intake',
    'target' => 'Centralized reception running; quality + quantity signed at intake',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'QAR',
    'all_depts' => 
    array (
      0 => 'QAR',
      1 => 'OPS',
    ),
    'notes' => 'Production / QA / Operations',
  ),
  28 => 
  array (
    'code' => 'G29',
    'title' => 'AI cameras at major areas + significant number of branches: reception, recipes, processes',
    'dod' => 'Cameras live; weekly exception report per branch feeding QA protocol',
    'target' => 'Cameras live; weekly exception report per branch feeding QA protocol',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'IT',
    'all_depts' => 
    array (
      0 => 'IT',
      1 => 'BI',
    ),
    'notes' => 'IT / BI',
  ),
  29 => 
  array (
    'code' => 'G30',
    'title' => 'Improve reception at main store + production: standard quality, correct quantity, signed',
    'dod' => 'Reception protocol documented; discrepancies logged and falling',
    'target' => 'Reception protocol documented; discrepancies logged and falling',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'SCM',
    'all_depts' => 
    array (
      0 => 'SCM',
    ),
    'notes' => 'Supply Chain',
  ),
  30 => 
  array (
    'code' => 'G31',
    'title' => 'CNET revised: essential steps cannot be skipped or edited (e.g., \'received\' vouchers)',
    'dod' => 'Voucher acknowledgements locked system-wide',
    'target' => 'Voucher acknowledgements locked system-wide',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'IT',
    'all_depts' => 
    array (
      0 => 'IT',
    ),
    'notes' => 'IT',
  ),
  31 => 
  array (
    'code' => 'G32',
    'title' => 'Solve daily branch inventory counting issues (errors/dishonesty)',
    'dod' => 'Count accuracy verified by spot audit; cost and on-hand inventory trusted',
    'target' => 'Count accuracy verified by spot audit; cost and on-hand inventory trusted',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'OPS',
    'all_depts' => 
    array (
      0 => 'OPS',
      1 => 'FB',
    ),
    'notes' => 'Operations / F&B',
  ),
  32 => 
  array (
    'code' => 'G33',
    'title' => 'Juice & hot beverage recipe training: consistent execution at branch',
    'dod' => 'QA inspections confirm recipe adherence at branch level',
    'target' => 'QA inspections confirm recipe adherence at branch level',
    'priority' => 'MEDIUM',
    'perspective' => 'Learning & Growth',
    'weight' => 1.72,
    'primary_dept' => 'FB',
    'all_depts' => 
    array (
      0 => 'FB',
      1 => 'TND',
    ),
    'notes' => 'F&B / Training & Development',
  ),
  33 => 
  array (
    'code' => 'G34',
    'title' => 'Theft control mechanism defined and running',
    'dod' => 'Named metric + protocol live; incidents tracked',
    'target' => 'Named metric + protocol live; incidents tracked',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'FB',
    'all_depts' => 
    array (
      0 => 'FB',
      1 => 'QAR',
      2 => 'IT',
    ),
    'notes' => 'F&B / QA / IT',
  ),
  34 => 
  array (
    'code' => 'G35',
    'title' => '\'Too Good To Go\' discount program for items nearing damage',
    'dod' => 'Program live; wastage value recovered tracked monthly',
    'target' => 'Program live; wastage value recovered tracked monthly',
    'priority' => 'MEDIUM',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'MKT',
    'all_depts' => 
    array (
      0 => 'MKT',
    ),
    'notes' => 'Marketing',
  ),
  35 => 
  array (
    'code' => 'G36',
    'title' => 'Quotas controlled strictly by AI + regional managers, automated to limit wastage',
    'dod' => 'Quota adjustments automated at multiple daily points; wastage trending down',
    'target' => 'Quota adjustments automated at multiple daily points; wastage trending down',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'OPS',
    'all_depts' => 
    array (
      0 => 'OPS',
      1 => 'BI',
    ),
    'notes' => 'Operations / BI',
  ),
  36 => 
  array (
    'code' => 'G37',
    'title' => 'Loan money injected as working capital into 2 accounts: Expense Account + Cost Account',
    'dod' => 'Accounts open; opening balances funded from loan',
    'target' => 'Accounts open; opening balances funded from loan',
    'priority' => 'HIGH',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'FIN',
    'all_depts' => 
    array (
      0 => 'FIN',
    ),
    'notes' => 'Finance',
  ),
  37 => 
  array (
    'code' => 'G38',
    'title' => 'Separate Sales Account receives all revenue; only owner has access',
    'dod' => 'Sales account live; access restricted to owner',
    'target' => 'Sales account live; access restricted to owner',
    'priority' => 'HIGH',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'FIN',
    'all_depts' => 
    array (
      0 => 'FIN',
    ),
    'notes' => 'Finance / Owner',
  ),
  38 => 
  array (
    'code' => 'G39',
    'title' => 'Month-end: Expense + Cost accounts replenished from Sales minus profit',
    'dod' => 'Monthly cycle running; profit withdrawn first, budgets funded after',
    'target' => 'Monthly cycle running; profit withdrawn first, budgets funded after',
    'priority' => 'HIGH',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'FIN',
    'all_depts' => 
    array (
      0 => 'FIN',
    ),
    'notes' => 'Finance',
  ),
  39 => 
  array (
    'code' => 'G40',
    'title' => 'Finance owns accurate planning — replenishment from real monthly budget',
    'dod' => 'Planning accuracy tracked as a Finance KPI',
    'target' => 'Planning accuracy tracked as a Finance KPI',
    'priority' => 'HIGH',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'FIN',
    'all_depts' => 
    array (
      0 => 'FIN',
    ),
    'notes' => 'Finance',
  ),
  40 => 
  array (
    'code' => 'G41',
    'title' => 'Safeguards: 5% tolerance built into replenishment; 2-4 week cost float; profit draw method defined (fixed vs % of sales); CNET mirrors account separation; monthly cash calendar with fixed dates',
    'dod' => 'All 5 safeguards agreed in writing before month 1',
    'target' => 'All 5 safeguards agreed in writing before month 1',
    'priority' => 'HIGH',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'FIN',
    'all_depts' => 
    array (
      0 => 'FIN',
      1 => 'IT',
    ),
    'notes' => 'Finance / IT / Owner',
  ),
  41 => 
  array (
    'code' => 'G42',
    'title' => 'Budget vs expense variance held at 5% or less; departments budget monthly; finance controls except emergencies',
    'dod' => 'Variance report monthly; any dept over 5% explains in person',
    'target' => 'Variance report monthly; any dept over 5% explains in person',
    'priority' => 'HIGH',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'FIN',
    'all_depts' => 
    array (
      0 => 'FIN',
    ),
    'notes' => 'Finance / All Depts',
  ),
  42 => 
  array (
    'code' => 'G43',
    'title' => 'JDs + trackable KPIs for all staff; monthly evaluations with action (train or terminate)',
    'dod' => 'Every termination/promotion cites a KPI score; monthly evaluation pack delivered',
    'target' => 'Every termination/promotion cites a KPI score; monthly evaluation pack delivered',
    'priority' => 'HIGH',
    'perspective' => 'Learning & Growth',
    'weight' => 1.72,
    'primary_dept' => 'HR',
    'all_depts' => 
    array (
      0 => 'HR',
    ),
    'notes' => 'HR / DGM',
  ),
  43 => 
  array (
    'code' => 'G44',
    'title' => 'Updated organizational structure on CNET',
    'dod' => 'Structure live and current on CNET',
    'target' => 'Structure live and current on CNET',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'HR',
    'all_depts' => 
    array (
      0 => 'HR',
    ),
    'notes' => 'HR',
  ),
  44 => 
  array (
    'code' => 'G45',
    'title' => 'Annual leave: all departments ensure staff take days off; no payout without justification',
    'dod' => 'Leave balance report monthly; payouts require written justification',
    'target' => 'Leave balance report monthly; payouts require written justification',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'HR',
    'all_depts' => 
    array (
      0 => 'HR',
    ),
    'notes' => 'HR / All Depts',
  ),
  45 => 
  array (
    'code' => 'G46',
    'title' => 'Fixed assets registered; maintenance per item tracked; replace when expense too high',
    'dod' => 'Asset register complete; replace-vs-repair threshold alerts firing',
    'target' => 'Asset register complete; replace-vs-repair threshold alerts firing',
    'priority' => 'MEDIUM',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'FIN',
    'all_depts' => 
    array (
      0 => 'FIN',
    ),
    'notes' => 'Finance',
  ),
  46 => 
  array (
    'code' => 'G47',
    'title' => 'Lease contract management: all agreements updated, renewed on time',
    'dod' => 'Zero expired leases; renewal calendar maintained',
    'target' => 'Zero expired leases; renewal calendar maintained',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'FIN',
    'all_depts' => 
    array (
      0 => 'FIN',
    ),
    'notes' => 'Finance',
  ),
  47 => 
  array (
    'code' => 'G48',
    'title' => 'Cross-training at branches: display and barista interchangeable, esp. small branches',
    'dod' => 'Small branches staffed with cross-trained teams; labor cost per branch falling',
    'target' => 'Small branches staffed with cross-trained teams; labor cost per branch falling',
    'priority' => 'MEDIUM',
    'perspective' => 'Learning & Growth',
    'weight' => 1.72,
    'primary_dept' => 'OPS',
    'all_depts' => 
    array (
      0 => 'OPS',
      1 => 'TND',
    ),
    'notes' => 'Operations / Training & Dev',
  ),
  48 => 
  array (
    'code' => 'G49',
    'title' => 'Maintenance department moved under Logistics',
    'dod' => 'Reporting line changed; handover complete',
    'target' => 'Reporting line changed; handover complete',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'LOG',
    'all_depts' => 
    array (
      0 => 'LOG',
    ),
    'notes' => 'Logistics',
  ),
  49 => 
  array (
    'code' => 'G50',
    'title' => 'Maintenance providers audited; 3-month contracts with current prices and strict KPIs',
    'dod' => 'All providers on 3-month contracts; quarterly audit log kept',
    'target' => 'All providers on 3-month contracts; quarterly audit log kept',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'LOG',
    'all_depts' => 
    array (
      0 => 'LOG',
    ),
    'notes' => 'Logistics',
  ),
  50 => 
  array (
    'code' => 'G51',
    'title' => 'COGS dashboard (SIV-based) with cost % vs targets per department/branch',
    'dod' => 'Live dashboard; drift flagged automatically',
    'target' => 'Live dashboard; drift flagged automatically',
    'priority' => 'HIGH',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'BI',
    'all_depts' => 
    array (
      0 => 'BI',
      1 => 'SCM',
      2 => 'FIN',
    ),
    'notes' => 'BI / Supply Chain / Finance',
  ),
  51 => 
  array (
    'code' => 'G52',
    'title' => 'Budget vs expense variance dashboard per department (5% rule)',
    'dod' => 'Monthly pack feeds account replenishment decision',
    'target' => 'Monthly pack feeds account replenishment decision',
    'priority' => 'HIGH',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'BI',
    'all_depts' => 
    array (
      0 => 'BI',
      1 => 'FIN',
    ),
    'notes' => 'BI / Finance',
  ),
  52 => 
  array (
    'code' => 'G53',
    'title' => 'Staff KPI & evaluation tracking dashboard',
    'dod' => 'Monthly scores visible; improvement/termination triggers automated',
    'target' => 'Monthly scores visible; improvement/termination triggers automated',
    'priority' => 'MEDIUM',
    'perspective' => 'Learning & Growth',
    'weight' => 1.72,
    'primary_dept' => 'BI',
    'all_depts' => 
    array (
      0 => 'BI',
      1 => 'HR',
    ),
    'notes' => 'BI / HR',
  ),
  53 => 
  array (
    'code' => 'G54',
    'title' => 'Fixed asset & maintenance cost tracker with replace-vs-repair alerts',
    'dod' => 'Cumulative spend per asset visible; alerts firing',
    'target' => 'Cumulative spend per asset visible; alerts firing',
    'priority' => 'MEDIUM',
    'perspective' => 'Financial',
    'weight' => 1.72,
    'primary_dept' => 'BI',
    'all_depts' => 
    array (
      0 => 'BI',
      1 => 'FIN',
    ),
    'notes' => 'BI / Finance',
  ),
  54 => 
  array (
    'code' => 'G55',
    'title' => 'AI camera analytics: reception, recipe adherence, process, theft, quota signals',
    'dod' => 'Weekly exception report per branch feeding reward/probation protocol',
    'target' => 'Weekly exception report per branch feeding reward/probation protocol',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'BI',
    'all_depts' => 
    array (
      0 => 'BI',
      1 => 'IT',
      2 => 'QAR',
    ),
    'notes' => 'BI / IT / QA',
  ),
  55 => 
  array (
    'code' => 'G56',
    'title' => 'Branch inventory data quality: validation rules, camera cross-checks, spot audits',
    'dod' => 'Count data trusted; error rate published monthly',
    'target' => 'Count data trusted; error rate published monthly',
    'priority' => 'MEDIUM',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'BI',
    'all_depts' => 
    array (
      0 => 'BI',
      1 => 'OPS',
    ),
    'notes' => 'BI / Operations',
  ),
  56 => 
  array (
    'code' => 'G57',
    'title' => 'Loyalty/customer database structured for analysis (with subscription + birthday data)',
    'dod' => 'Customer behavior reports produced, not just stored data',
    'target' => 'Customer behavior reports produced, not just stored data',
    'priority' => 'MEDIUM',
    'perspective' => 'Customer',
    'weight' => 1.72,
    'primary_dept' => 'BI',
    'all_depts' => 
    array (
      0 => 'BI',
      1 => 'MKT',
      2 => 'IT',
    ),
    'notes' => 'BI / Marketing / IT',
  ),
  57 => 
  array (
    'code' => 'G58',
    'title' => 'BI operating rhythm: daily alerts, weekly branch reports, monthly packs, quarterly benchmarks',
    'dod' => 'Rhythm documented and delivering on schedule',
    'target' => 'Rhythm documented and delivering on schedule',
    'priority' => 'HIGH',
    'perspective' => 'Internal Process',
    'weight' => 1.72,
    'primary_dept' => 'BI',
    'all_depts' => 
    array (
      0 => 'BI',
    ),
    'notes' => 'BI',
  ),
);
        $this->info("Inserting " . count($goals) . " strategic goals G01 to G58...");

        $ethiopianMonths = [
            'Hamle', 'Nehase', 'Pagumen', 'Meskerem', 'Tikimt', 'Hidar',
            'Tahsas', 'Tir', 'Yakatit', 'Megabit', 'Miazia', 'Ginbot', 'Sene'
        ];

        $insertedCount = 0;
        $activationsCount = 0;

        foreach ($goals as $g) {
            $primaryDeptCode = $g['primary_dept'];
            $primaryDeptId = $deptMap[$primaryDeptCode] ?? ($deptMap['OPS'] ?? 1);

            $goalId = DB::table('annual_goals')->insertGetId([
                'goal_code' => $g['code'],
                'title' => $g['title'],
                'definition_of_done' => $g['dod'],
                'responsible_department_id' => $primaryDeptId,
                'annual_target' => $g['target'],
                'priority' => $g['priority'],
                'notes' => $g['notes'],
                'status' => 'ACTIVE',
                'year' => $planningYear,
                'perspective' => $g['perspective'],
                'weight' => $g['weight'],
                'created_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $insertedCount++;

            // Link all involved departments
            $linkedDeptIds = [];
            foreach ($g['all_depts'] as $dCode) {
                if (isset($deptMap[$dCode])) {
                    $linkedDeptIds[] = $deptMap[$dCode];
                }
            }
            $linkedDeptIds = array_unique($linkedDeptIds);

            foreach ($linkedDeptIds as $dId) {
                $isPrimary = ($dId === $primaryDeptId) ? 1 : 0;
                DB::table('annual_goal_departments')->insert([
                    'annual_goal_id' => $goalId,
                    'department_id' => $dId,
                    'is_primary' => $isPrimary,
                    'created_at' => now(),
                ]);

                // Seed monthly activations across all months for owner departments (default INACTIVE/UN-ACTIVE)
                foreach ($ethiopianMonths as $m) {
                    DB::table('monthly_strategy_activations')->insert([
                        'annual_goal_id' => $goalId,
                        'department_id' => $dId,
                        'year' => $planningYear,
                        'month' => $m,
                        'active' => 'NO',
                        'activated_by' => $adminId,
                        'activated_at' => now(),
                        'updated_by' => $adminId,
                        'updated_at' => now(),
                    ]);
                    $activationsCount++;
                }
            }
        }

        Artisan::call('optimize:clear');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Goals Seeded (G01 - G58)', $insertedCount],
                ['Department Pivot Links', DB::table('annual_goal_departments')->count()],
                ['Monthly Strategy Activations', $activationsCount],
            ]
        );

        $this->info('Strategic goals reset and re-seeded successfully!');
        return Command::SUCCESS;
    }
}
