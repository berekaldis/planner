<?php
/**
 * Role-Based Access Control & Department Scoping
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/auth.php';

class Permissions {
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_GM = 'gm_management';
    public const ROLE_HR = 'hr';
    public const ROLE_DEPT_HEAD = 'dept_head';
    public const ROLE_IT_ADMIN = 'it_admin';

    public static function getRole(): ?string {
        $user = Auth::user();
        return $user['role_name'] ?? null;
    }

    public static function isSuperAdmin(): bool {
        return self::getRole() === self::ROLE_SUPER_ADMIN;
    }

    public static function isGM(): bool {
        return in_array(self::getRole(), [self::ROLE_SUPER_ADMIN, self::ROLE_GM], true);
    }

    public static function isHR(): bool {
        return in_array(self::getRole(), [self::ROLE_SUPER_ADMIN, self::ROLE_HR], true);
    }

    public static function isDeptHead(): bool {
        return self::getRole() === self::ROLE_DEPT_HEAD;
    }

    public static function isITAdmin(): bool {
        return in_array(self::getRole(), [self::ROLE_SUPER_ADMIN, self::ROLE_IT_ADMIN], true);
    }

    /**
     * Check if current user has any of the given roles
     */
    public static function hasAnyRole(array $roles): bool {
        $currentRole = self::getRole();
        if ($currentRole === self::ROLE_SUPER_ADMIN) {
            return true; // Super admin has universal access
        }
        return in_array($currentRole, $roles, true);
    }

    /**
     * Require one of the specified roles or abort with 403
     */
    public static function requireRole(array|string $roles): void {
        Auth::requireLogin();
        $rolesArray = is_array($roles) ? $roles : [$roles];

        if (!self::hasAnyRole($rolesArray)) {
            http_response_code(403);
            die('Access Denied: You do not have permission to view this resource.');
        }
    }

    /**
     * Can user manage strategy activation? (GM / Super Admin only)
     */
    public static function canActivateStrategy(): bool {
        return self::isGM();
    }

    /**
     * Can user view company-wide data across all departments?
     */
    public static function canViewAllDepartments(): bool {
        return in_array(self::getRole(), [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_GM,
            self::ROLE_HR,
            self::ROLE_IT_ADMIN
        ], true);
    }

    /**
     * Check if user can access a specific department's data
     */
    public static function canAccessDepartment(int $departmentId): bool {
        if (self::canViewAllDepartments()) {
            return true;
        }

        $user = Auth::user();
        return (int)($user['department_id'] ?? 0) === (int)$departmentId;
    }

    /**
     * Enforce department scoping or abort with 403
     */
    public static function requireDepartmentAccess(int $departmentId): void {
        Auth::requireLogin();
        if (!self::canAccessDepartment($departmentId)) {
            http_response_code(403);
            die('Access Denied: You are not authorized to access this department.');
        }
    }

    /**
     * Get user's active department ID, or fallback for super admin/management
     */
    public static function getActiveDepartmentId(?int $requestedDeptId = null): ?int {
        $user = Auth::user();
        if (self::canViewAllDepartments()) {
            return $requestedDeptId ?: ($user['department_id'] ?? null);
        }
        return $user['department_id'] ?? null;
    }
}
