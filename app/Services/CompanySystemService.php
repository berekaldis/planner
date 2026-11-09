<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

class CompanySystemService
{
    protected static ?PDO $pdo = null;

    /**
     * Map of known company system department names to Planner department codes.
     */
    protected static array $aliasMap = [
        'it' => 'IT',
        'information technology' => 'IT',
        'operation' => 'OPS',
        'retail operations' => 'OPS',
        'marketing' => 'MKT',
        'marketing & brand strategy' => 'MKT',
        'purchase' => 'SCM',
        'supply chain & procurement' => 'SCM',
        'hr' => 'HR',
        'human resources' => 'HR',
        'finance and accounting' => 'FIN',
        'finance & accounting' => 'FIN',
        'quality assurance' => 'QAR',
        'quality assurance & roastery' => 'QAR',
        'training and development' => 'TND',
        'training & development' => 'TND',
        'logistics' => 'LOG',
        'logistics & maintenance' => 'LOG',
        'internal auditor' => 'AUD',
        'internal audit & compliance' => 'AUD',
        'bi department' => 'BI',
        'business intelligence & analytics' => 'BI',
        'operation/f&b' => 'FB',
        'food & beverage' => 'FB',
        'operation/maintenance' => 'OPER',
        'production' => 'PROD',
        'fixed asset' => 'FIXE',
        'executive team' => 'EXEC',
        'general manager' => 'GENE',
        'branch manager' => 'BRAN',
        'store' => 'STOR',
        'main store' => 'MAIN',
        'lawyer' => 'LAWY',
        'janitor' => 'JANI',
        'kitchen' => 'KITC',
        'laborer' => 'LABO',
        'cashier' => 'CASH',
        'barista' => 'BARI',
        'bar tender' => 'BART',
        'guard' => 'GUAR',
        'film editing' => 'FILM',
        'coffee room' => 'COFF',
        'waiter' => 'WAIT',
    ];

    /**
     * Get an active PDO connection to the Company System database.
     */
    public static function getConnection(): ?PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = env('COMPANY_DB_HOST', env('DB_HOST', '127.0.0.1'));
        $port = env('COMPANY_DB_PORT', '3306');
        $user = env('COMPANY_DB_USERNAME', env('DB_USERNAME', 'root'));
        $pass = env('COMPANY_DB_PASSWORD', env('DB_PASSWORD', ''));

        $primaryDb = config('database.connections.company_system.database')
            ?: env('COMPANY_DB_DATABASE', 'kaldisbp_company_system');

        $candidateDbs = array_values(array_unique(array_filter([
            $primaryDb,
            'kaldisbp_company_system',
            'company_system',
        ])));

        foreach ($candidateDbs as $dbname) {
            try {
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 3,
                ]);

                self::$pdo = $pdo;
                return self::$pdo;
            } catch (Throwable $e) {
                // Log and continue to next candidate
                Log::debug("CompanySystemService: Could not connect to '{$dbname}' on {$host}:{$port} - " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Check if Company System database is reachable.
     */
    public static function isAvailable(): bool
    {
        return self::getConnection() !== null;
    }

    /**
     * Authenticate user credentials against Company System database.
     */
    public static function authenticate(string $identifier, string $password): ?User
    {
        $pdo = self::getConnection();
        if (!$pdo) {
            return null;
        }

        $record = self::findInCompanySystem($identifier);
        if (!$record) {
            return null;
        }

        $hash = $record['password'] ?? '';
        if (empty($hash)) {
            return null;
        }

        $passwordValid = false;
        if (Hash::check($password, $hash) || password_verify($password, $hash)) {
            $passwordValid = true;
        }

        if (!$passwordValid) {
            return null;
        }

        // Synchronize and return local User model
        if (($record['source_table'] ?? '') === 'admin_user') {
            return self::syncAdminUser($record, $password);
        }

        return self::syncCompanyUser($record, $password);
    }

    /**
     * Look up user record in Company System database.
     */
    public static function findInCompanySystem(string $identifier): ?array
    {
        $pdo = self::getConnection();
        if (!$pdo) {
            return null;
        }

        $clean = trim($identifier);

        // 1. Check admin_users table (exact username or id)
        try {
            $stmt = $pdo->prepare("
                SELECT id, username, password, name, role, status, 'admin_user' as source_table 
                FROM admin_users 
                WHERE (username = :ident OR id = :ident) AND status = 1 
                LIMIT 1
            ");
            $stmt->execute(['ident' => $clean]);
            $admin = $stmt->fetch();
            if ($admin) {
                return $admin;
            }
        } catch (Throwable $e) {
            Log::debug("CompanySystemService find in admin_users error: " . $e->getMessage());
        }

        // 2. Check users table by email, name, employee code, or email username
        try {
            $query = "
                SELECT u.id, u.name, u.email, u.password, u.is_active, u.telegram_chat_id, u.telegram_username,
                       e.id as employee_id, e.employee_code, e.department_id as company_department_id,
                       d.name as company_department_name, p.title as position_title, p.level as position_level,
                       'user' as source_table
                FROM users u
                LEFT JOIN employees e ON u.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                WHERE (u.email = :ident 
                   OR u.name = :ident 
                   OR e.employee_code = :ident 
                   OR u.email LIKE :likeEmail 
                   OR u.name LIKE :likeName)
                  AND u.is_active = 1
                LIMIT 1
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'ident' => $clean,
                'likeEmail' => $clean . '@%',
                'likeName' => $clean . '%',
            ]);
            $user = $stmt->fetch();

            if ($user) {
                // Fetch assigned roles from model_has_roles
                $rStmt = $pdo->prepare("
                    SELECT r.name 
                    FROM model_has_roles mhr 
                    JOIN roles r ON mhr.role_id = r.id 
                    WHERE mhr.model_id = ? AND mhr.model_type LIKE '%User%'
                ");
                $rStmt->execute([$user['id']]);
                $user['roles'] = $rStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                return $user;
            }
        } catch (Throwable $e) {
            Log::error("CompanySystemService find in users error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Synchronize a regular company system user into Planner's local users table.
     */
    public static function syncCompanyUser(array $compUser, ?string $rawPassword = null): User
    {
        $compUserId = (int)$compUser['id'];
        $email = trim($compUser['email'] ?? '');
        $fullName = trim($compUser['name'] ?? '');
        $passwordHash = $compUser['password'] ?? '';

        // Generate username
        $username = $email ? explode('@', $email)[0] : strtolower(str_replace(' ', '.', $fullName));

        // Resolve Department in Planner
        $plannerDept = self::resolvePlannerDepartment(
            $compUser['company_department_id'] ? (int)$compUser['company_department_id'] : null,
            $compUser['company_department_name'] ?? null
        );

        // Resolve Role in Planner
        $roleId = self::resolvePlannerRoleId(
            $compUser['roles'] ?? [],
            $compUser['position_title'] ?? '',
            $compUser['company_department_name'] ?? ''
        );

        // Find or create local user
        $localUser = User::where('company_user_id', $compUserId)
            ->orWhere('email', $email)
            ->orWhere('username', $username)
            ->first();

        if (!$localUser) {
            $localUser = new User();
            $localUser->username = $username;
        }

        $localUser->company_user_id = $compUserId;
        $localUser->full_name = $fullName ?: $localUser->full_name;
        $localUser->email = $email ?: $localUser->email;
        if (!empty($passwordHash)) {
            $localUser->password_hash = $passwordHash;
        } elseif (!empty($rawPassword)) {
            $localUser->password_hash = Hash::make($rawPassword);
        }

        $localUser->role_id = $roleId;
        if ($plannerDept) {
            $localUser->department_id = $plannerDept->id;
        }

        // Sync Telegram info if present
        if (!empty($compUser['telegram_chat_id']) && $compUser['telegram_chat_id'] !== '123456789') {
            $localUser->telegram_chat_id = $compUser['telegram_chat_id'];
        }
        if (!empty($compUser['telegram_username'])) {
            $localUser->telegram_username = $compUser['telegram_username'];
        }

        $localUser->is_active = 1;
        $localUser->save();

        // If user is designated department head, update the department's head_user_id
        if ($plannerDept && self::isDepartmentHeadRole($compUser['roles'] ?? [], $compUser['position_title'] ?? '')) {
            $plannerDept->update(['head_user_id' => $localUser->id]);
        }

        return $localUser;
    }

    /**
     * Synchronize an admin_user from company system into Planner.
     */
    public static function syncAdminUser(array $admin, ?string $rawPassword = null): User
    {
        $adminUsername = trim($admin['username'] ?? 'admin');
        $adminName = trim($admin['name'] ?? 'Admin');
        $hash = $admin['password'] ?? '';

        $localUser = User::where('username', $adminUsername)->first();
        if (!$localUser) {
            $localUser = new User();
            $localUser->username = $adminUsername;
            $localUser->email = strtolower($adminUsername) . '@kaldiscoffee.com';
        }

        $localUser->full_name = $adminName ?: $localUser->full_name;
        if (!empty($hash)) {
            $localUser->password_hash = $hash;
        } elseif (!empty($rawPassword)) {
            $localUser->password_hash = Hash::make($rawPassword);
        }

        // Admin role: super_admin (1) or it_admin (5)
        $localUser->role_id = 1;
        $localUser->is_active = 1;
        $localUser->save();

        return $localUser;
    }

    /**
     * Synchronize all active departments and designated heads from Company System.
     */
    public static function syncDepartments(): array
    {
        $pdo = self::getConnection();
        if (!$pdo) {
            throw new \RuntimeException("Unable to establish connection to Company System database.");
        }

        $stmt = $pdo->query("SELECT id, name, is_active FROM departments WHERE is_active = 1 ORDER BY name");
        $companyDepts = $stmt->fetchAll();

        $syncedCount = 0;
        $createdCount = 0;
        $headsSynced = 0;
        $results = [];

        foreach ($companyDepts as $cd) {
            $cId = (int)$cd['id'];
            $cName = trim($cd['name']);

            $dept = self::resolvePlannerDepartment($cId, $cName);
            if ($dept) {
                $syncedCount++;
                $results[] = [
                    'company_id' => $cId,
                    'name' => $cName,
                    'planner_id' => $dept->id,
                    'action' => 'updated',
                ];
            } else {
                $createdCount++;
                $results[] = [
                    'company_id' => $cId,
                    'name' => $cName,
                    'planner_id' => null,
                    'action' => 'created',
                ];
            }
        }

        // Next, synchronize department heads
        $headsQuery = "
            SELECT u.id, u.name, u.email, u.password, u.telegram_chat_id, u.telegram_username,
                   e.department_id as company_department_id, d.name as company_department_name,
                   p.title as position_title, r.name as role_name
            FROM model_has_roles mhr
            JOIN roles r ON mhr.role_id = r.id
            JOIN users u ON mhr.model_id = u.id
            JOIN employees e ON u.employee_id = e.id
            JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE r.name IN ('department head', 'ceo role', 'IT Admin')
               OR p.title LIKE '%Head%'
               OR p.title LIKE '%Director%'
               OR p.title LIKE '%General Manager%'
            ORDER BY u.id
        ";

        try {
            $headsStmt = $pdo->query($headsQuery);
            $heads = $headsStmt->fetchAll();

            foreach ($heads as $h) {
                $h['roles'] = [$h['role_name']];
                $user = self::syncCompanyUser($h);
                if ($user->department_id) {
                    Department::where('id', $user->department_id)->update(['head_user_id' => $user->id]);
                    $headsSynced++;
                }
            }
        } catch (Throwable $e) {
            Log::warning("CompanySystemService sync department heads error: " . $e->getMessage());
        }

        return [
            'total_company_departments' => count($companyDepts),
            'synced_departments' => $syncedCount + $createdCount,
            'heads_synced' => $headsSynced,
            'details' => $results,
        ];
    }

    /**
     * Resolve or create the matching Planner Department.
     */
    public static function resolvePlannerDepartment(?int $companyDeptId, ?string $companyDeptName): ?Department
    {
        if (!$companyDeptId && !$companyDeptName) {
            return null;
        }

        // 1. Try finding by company_department_id
        if ($companyDeptId) {
            $dept = Department::where('company_department_id', $companyDeptId)->first();
            if ($dept) {
                return $dept;
            }
        }

        $cName = trim((string)$companyDeptName);
        $lowName = strtolower($cName);

        // 2. Try finding by exact name or code
        $dept = Department::where('department_name', $cName)
            ->orWhere('department_code', $cName)
            ->first();

        // 3. Try finding by alias map
        if (!$dept && isset(self::$aliasMap[$lowName])) {
            $targetCode = self::$aliasMap[$lowName];
            $dept = Department::where('department_code', $targetCode)->first();
        }

        // If found, link company_department_id
        if ($dept) {
            if ($companyDeptId && empty($dept->company_department_id)) {
                $dept->update(['company_department_id' => $companyDeptId]);
            }
            return $dept;
        }

        // 4. Create new Department in Planner
        if ($cName) {
            $baseCode = isset(self::$aliasMap[$lowName])
                ? self::$aliasMap[$lowName]
                : strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $cName), 0, 4));

            $code = $baseCode;
            $counter = 1;
            while (Department::where('department_code', $code)->exists()) {
                $code = substr($baseCode, 0, 3) . $counter++;
            }

            return Department::create([
                'company_department_id' => $companyDeptId,
                'department_name' => $cName,
                'department_code' => $code,
                'active' => 1,
            ]);
        }

        return null;
    }

    /**
     * Resolve Planner role_id from company roles, position, and department.
     */
    public static function resolvePlannerRoleId(array $roles, string $positionTitle = '', string $departmentName = ''): int
    {
        $roleNames = array_map('strtolower', $roles);
        $pos = strtolower($positionTitle);
        $dept = strtolower($departmentName);

        // 1. Super Admin / IT Admin
        foreach ($roleNames as $r) {
            if (str_contains($r, 'super admin') || str_contains($r, 'it admin') || $r === 'superadmin') {
                return 1; // super_admin
            }
        }

        // 2. CEO / GM
        foreach ($roleNames as $r) {
            if (str_contains($r, 'ceo') || str_contains($r, 'general manager') || str_contains($r, 'gm')) {
                return 2; // gm_management
            }
        }
        if (str_contains($pos, 'ceo') || str_contains($pos, 'general manager') || $dept === 'general manager' || $dept === 'executive team') {
            return 2;
        }

        // 3. HR
        if (str_contains($dept, 'hr') || str_contains($dept, 'human resource') || str_contains($pos, 'hr officer') || str_contains($pos, 'human resource')) {
            if (self::isDepartmentHeadRole($roles, $positionTitle)) {
                return 4; // dept_head
            }
            return 3; // hr
        }

        // 4. Department Head
        if (self::isDepartmentHeadRole($roles, $positionTitle)) {
            return 4; // dept_head
        }

        // Default role for authenticated employee
        return 4; // dept_head
    }

    /**
     * Check if the user is considered a Department Head.
     */
    protected static function isDepartmentHeadRole(array $roles, string $positionTitle): bool
    {
        $roleNames = array_map('strtolower', $roles);
        $pos = strtolower($positionTitle);

        foreach ($roleNames as $r) {
            if (str_contains($r, 'department head') || str_contains($r, 'dept_head') || str_contains($r, 'head')) {
                return true;
            }
        }

        if (str_contains($pos, 'head') || str_contains($pos, 'director') || str_contains($pos, 'manager')) {
            return true;
        }

        return false;
    }
}
