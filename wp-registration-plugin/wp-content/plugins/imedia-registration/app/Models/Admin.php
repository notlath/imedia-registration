<?php

/**
 * IMedia Registration — Admin model.
 *
 * Owns all SQL for the admins table. Phase 4: full CRUD surface.
 *
 * Per php-pro: strict types, named placeholders, typed return shapes.
 * Per wordpress-pro: password_hash / password_verify for bcrypt.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Admin
{
    public const ROLES = ['admin', 'super'];

    /**
     * @return array{id: int, name: string, email: string, password: string, role: string}|null
     */
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, email, password, role, locked_until
             FROM admins
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        return [
            'id'           => (int) $row['id'],
            'name'         => (string) $row['name'],
            'email'        => (string) $row['email'],
            'password'     => (string) $row['password'],
            'role'         => (string) $row['role'],
            'locked_until' => $row['locked_until'],
        ];
    }

    /**
     * @return array{id:int, name:string, email:string, password:string, role:string}|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, email, password, role
             FROM admins
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        return [
            'id'       => (int) $row['id'],
            'name'     => (string) $row['name'],
            'email'    => (string) $row['email'],
            'password' => (string) $row['password'],
            'role'     => (string) $row['role'],
        ];
    }

    /**
     * @return array<int, array{id:int, name:string, email:string, role:string, created_at:string}>
     */
    public static function all(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT id, name, email, role, created_at FROM admins ORDER BY id ASC'
        );
        $rows = $stmt ? $stmt->fetchAll() : [];
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id'         => (int) $row['id'],
                'name'       => (string) $row['name'],
                'email'      => (string) $row['email'],
                'role'       => (string) $row['role'],
                'created_at' => (string) $row['created_at'],
            ];
        }
        return $out;
    }

    public static function count(): int
    {
        $stmt = Database::pdo()->query('SELECT COUNT(*) FROM admins');
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }

    public static function create(string $name, string $email, string $password, string $role): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO admins (name, email, password, role) VALUES (:name, :email, :pw, :role)'
        );
        $stmt->execute([
            ':name'  => $name,
            ':email' => strtolower(trim($email)),
            ':pw'    => $hash,
            ':role'  => $role,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Update a subset of fields. Only non-null arguments are written.
     * If $newPassword is null the password is not changed.
     */
    public static function update(
        int $id,
        ?string $name = null,
        ?string $email = null,
        ?string $role = null,
        ?string $newPassword = null
    ): void {
        $sets  = [];
        $bind  = [':id' => $id];
        if ($name !== null) {
            $sets[]        = 'name = :name';
            $bind[':name'] = $name;
        }
        if ($email !== null) {
            $sets[]         = 'email = :email';
            $bind[':email'] = strtolower(trim($email));
        }
        if ($role !== null) {
            $sets[]        = 'role = :role';
            $bind[':role'] = $role;
        }
        if ($newPassword !== null) {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $sets[]        = 'password = :pw';
            $bind[':pw']   = $hash;
        }
        if ($sets === []) {
            return;
        }
        $sql  = 'UPDATE admins SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($bind);
    }

    /**
     * Used by the self-edit profile page. The role is intentionally
     * blocked from being changed by an admin editing themselves — the
     * caller (controller) must not pass a $role value here.
     */
    public static function updateOwn(
        int $id,
        ?string $name = null,
        ?string $email = null,
        ?string $newPassword = null
    ): void {
        self::update($id, $name, $email, null, $newPassword);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM admins WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * Re-hash the admin's password (used by Auth::attempt when the
     * stored hash uses a weaker cost than the current PASSWORD_BCRYPT default).
     */
    public static function updatePassword(int $id, string $newHash): void
    {
        $stmt = Database::pdo()->prepare('UPDATE admins SET password = :p WHERE id = :id');
        $stmt->execute([':p' => $newHash, ':id' => $id]);
    }

    /**
     * Set the per-account lockout window. Pass null in $untilTo to clear
     * the lock (e.g. on successful login). Stored as UTC datetime.
     */
    public static function lock(int $id, ?\DateTimeInterface $until = null): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE admins SET locked_until = :u WHERE id = :id'
        );
        $stmt->bindValue(':u', $until?->format('Y-m-d H:i:s'));
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
    }
}
