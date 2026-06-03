<?php

declare(strict_types=1);

namespace App\Data\Core\User;

use App\Data\Core\Log\EntityLogRepository;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

use function date;

final readonly class UserRepository
{
    public function __construct(
        private ConnectionInterface $db,
        private EntityLogRepository $entityLogRepository,
    ) {
    }

    public function findById(int|string $id): ?UserEntity
    {
        $row = (new Query($this->db))
            ->from('{{%core_user}}')
            ->where(['id' => $id])
            ->one();

        return $row === null ? null : $this->map($row);
    }

    public function findByEmail(string $email): ?UserEntity
    {
        $row = (new Query($this->db))
            ->from('{{%core_user}}')
            ->where(['email' => mb_strtolower(trim($email))])
            ->one();

        return $row === null ? null : $this->map($row);
    }

    public function exists(int $id): bool
    {
        return (new Query($this->db))
            ->from('{{%core_user}}')
            ->where(['id' => $id])
            ->exists();
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = (new Query($this->db))
            ->from('{{%core_user}}')
            ->where(['email' => mb_strtolower(trim($email))]);

        if ($excludeId !== null) {
            $query = $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }

    public function create(UserEntity $user, ?array $roleIds = null): int
    {
        $user->stampCreated($user->createdBy ?? $user->updatedBy);
        $data = [
            'email' => $user->email,
            'password_hash' => $user->passwordHash,
            'name' => $user->name,
            'status' => $user->status,
            'created_at' => $user->createdAt,
            'updated_at' => $user->updatedAt,
            'last_login_at' => $user->lastLoginAt,
            'remember_token_hash' => $user->rememberTokenHash,
            'password_changed_at' => $user->passwordChangedAt,
            'password_expires_at' => $user->passwordExpiresAt,
            'password_reset_selector' => $user->passwordResetSelector,
            'password_reset_token_hash' => $user->passwordResetTokenHash,
            'password_reset_token_expires_at' => $user->passwordResetTokenExpiresAt,
            'created_by' => $user->createdBy,
            'updated_by' => $user->updatedBy,
        ];

        $this->db->createCommand()->insert('{{%core_user}}', $data)->execute();

        $id = (int) $this->db->getLastInsertID();
        $user->id = $id;

        $this->entityLogRepository->record(
            'user',
            $id,
            EntityLogRepository::ACTION_CREATE,
            $user,
            $roleIds === null
                ? 'INSERT INTO core_user (email, password_hash, name, status, created_at, updated_at, last_login_at, remember_token_hash, password_changed_at, password_expires_at, password_reset_selector, password_reset_token_hash, password_reset_token_expires_at, created_by, updated_by) VALUES (:email, :password_hash, :name, :status, :created_at, :updated_at, :last_login_at, :remember_token_hash, :password_changed_at, :password_expires_at, :password_reset_selector, :password_reset_token_hash, :password_reset_token_expires_at, :created_by, :updated_by)'
                : 'INSERT INTO core_user (email, password_hash, name, status, created_at, updated_at, last_login_at, remember_token_hash, password_changed_at, password_expires_at, password_reset_selector, password_reset_token_hash, password_reset_token_expires_at, created_by, updated_by) VALUES (:email, :password_hash, :name, :status, :created_at, :updated_at, :last_login_at, :remember_token_hash, :password_changed_at, :password_expires_at, :password_reset_selector, :password_reset_token_hash, :password_reset_token_expires_at, :created_by, :updated_by); INSERT core_user_role :role_ids',
            $roleIds === null ? $data : [...$data, 'role_ids' => $roleIds],
        );

        return $id;
    }

    /**
     * @return int[]
     */
    public function getRoleIds(int $userId): array
    {
        $ids = (new Query($this->db))
            ->select(['role_id'])
            ->from('{{%core_user_role}}')
            ->where(['user_id' => $userId])
            ->orderBy(['role_id' => SORT_ASC])
            ->column();

        return array_map(static fn (mixed $id): int => (int) $id, $ids);
    }

    public function createWithRoles(UserEntity $user, array $roleIds): int
    {
        $transaction = $this->db->beginTransaction();

        try {
            $userId = $this->create($user, $roleIds);
            $this->replaceRoles($userId, $roleIds);

            $transaction->commit();

            return $userId;
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public function updateWithRoles(UserEntity $user, array $roleIds, bool $updatePassword = true): void
    {
        $transaction = $this->db->beginTransaction();

        try {
            $user->stampUpdated($user->updatedBy);
            $data = [
                'email' => $user->email,
                'name' => $user->name,
                'status' => $user->status,
                'updated_at' => $user->updatedAt,
                'updated_by' => $user->updatedBy,
            ];

            if ($updatePassword) {
                $data['password_hash'] = $user->passwordHash;
                $data['remember_token_hash'] = $user->rememberTokenHash;
                $data['password_changed_at'] = $user->passwordChangedAt;
                $data['password_expires_at'] = $user->passwordExpiresAt;
                $data['password_reset_selector'] = $user->passwordResetSelector;
                $data['password_reset_token_hash'] = $user->passwordResetTokenHash;
                $data['password_reset_token_expires_at'] = $user->passwordResetTokenExpiresAt;
            }

            $this->db->createCommand()->update('{{%core_user}}', $data, [
                'id' => $user->id,
            ])->execute();

            $this->replaceRoles((int) $user->id, $roleIds);

            $this->entityLogRepository->record(
                'user',
                $user->id,
                EntityLogRepository::ACTION_UPDATE,
                $user,
                'UPDATE core_user SET email = :email, name = :name, status = :status, updated_at = :updated_at, updated_by = :updated_by WHERE id = :id; REPLACE core_user_role WITH :role_ids',
                [...$data, 'id' => $user->id, 'role_ids' => $roleIds],
            );

            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public function updateProfile(UserEntity $user): void
    {
        $transaction = $this->db->beginTransaction();

        try {
            $user->stampUpdated($user->updatedBy);

            $data = [
                'email' => $user->email,
                'name' => $user->name,
                'updated_at' => $user->updatedAt,
                'updated_by' => $user->updatedBy,
            ];

            $this->db->createCommand()->update('{{%core_user}}', $data, [
                'id' => $user->id,
            ])->execute();

            $this->entityLogRepository->record(
                'user',
                $user->id,
                EntityLogRepository::ACTION_UPDATE,
                $user,
                'UPDATE core_user SET email = :email, name = :name, updated_at = :updated_at, updated_by = :updated_by WHERE id = :id',
                [...$data, 'id' => $user->id],
            );

            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public function delete(int $id, ?int $actorId = null): void
    {
        $transaction = $this->db->beginTransaction();

        try {
            $user = $this->findById($id);

            $this->db->createCommand()
                ->delete('{{%core_user_role}}', ['user_id' => $id])
                ->execute();

            $this->db->createCommand()
                ->delete('{{%core_user}}', ['id' => $id])
                ->execute();

            if ($user !== null) {
                $this->entityLogRepository->record(
                    'user',
                    $id,
                    EntityLogRepository::ACTION_DELETE,
                    $user,
                    'DELETE FROM core_user_role WHERE user_id = :id; DELETE FROM core_user WHERE id = :id',
                    ['id' => $id],
                    actorId: $actorId,
                );
            }

            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public function updateLastLogin(int $id, string $dateTime): void
    {
        $user = $this->findById($id);
        $logEntity = $user === null ? null : new UserEntity(
            id: $user->id,
            email: $user->email,
            passwordHash: $user->passwordHash,
            name: $user->name,
            status: $user->status,
            createdAt: $user->createdAt,
            updatedAt: $dateTime,
            lastLoginAt: $dateTime,
            rememberTokenHash: $user->rememberTokenHash,
            passwordChangedAt: $user->passwordChangedAt,
            passwordExpiresAt: $user->passwordExpiresAt,
            passwordResetSelector: $user->passwordResetSelector,
            passwordResetTokenHash: $user->passwordResetTokenHash,
            passwordResetTokenExpiresAt: $user->passwordResetTokenExpiresAt,
            createdBy: $user->createdBy,
            updatedBy: $id,
        );

        $this->db->createCommand()->update('{{%core_user}}', [
            'last_login_at' => $dateTime,
            'updated_at' => $dateTime,
            'updated_by' => $id,
        ], [
            'id' => $id,
        ])->execute();

        if ($logEntity !== null) {
            $this->entityLogRepository->record(
                'user',
                $id,
                EntityLogRepository::ACTION_UPDATE,
                $logEntity,
                'UPDATE core_user SET last_login_at = :last_login_at, updated_at = :updated_at, updated_by = :updated_by WHERE id = :id',
                [
                    'last_login_at' => $dateTime,
                    'updated_at' => $dateTime,
                    'updated_by' => $id,
                    'id' => $id,
                ],
            );
        }
    }

    public function updateRememberToken(int $id, ?string $tokenHash, ?int $actorId = null): void
    {
        $dateTime = date('Y-m-d H:i:s');

        $this->db->createCommand()->update('{{%core_user}}', [
            'remember_token_hash' => $tokenHash,
            'updated_at' => $dateTime,
            'updated_by' => $actorId ?? $id,
        ], [
            'id' => $id,
        ])->execute();
    }

    public function storePasswordResetToken(
        int $id,
        string $selector,
        string $tokenHash,
        string $expiresAt,
    ): void {
        $dateTime = date('Y-m-d H:i:s');

        $this->db->createCommand()->update('{{%core_user}}', [
            'password_reset_selector' => $selector,
            'password_reset_token_hash' => $tokenHash,
            'password_reset_token_expires_at' => $expiresAt,
            'updated_at' => $dateTime,
            'updated_by' => $id,
        ], [
            'id' => $id,
        ])->execute();
    }

    public function clearPasswordResetToken(int $id, ?int $actorId = null): void
    {
        $dateTime = date('Y-m-d H:i:s');

        $this->db->createCommand()->update('{{%core_user}}', [
            'password_reset_selector' => null,
            'password_reset_token_hash' => null,
            'password_reset_token_expires_at' => null,
            'updated_at' => $dateTime,
            'updated_by' => $actorId ?? $id,
        ], [
            'id' => $id,
        ])->execute();
    }

    public function findByPasswordResetSelector(string $selector): ?UserEntity
    {
        $row = (new Query($this->db))
            ->from('{{%core_user}}')
            ->where(['password_reset_selector' => $selector])
            ->one();

        return $row === null ? null : $this->map($row);
    }

    public function changePassword(
        int $id,
        string $passwordHash,
        string $changedAt,
        ?string $expiresAt,
        ?int $actorId = null,
    ): void {
        $user = $this->findById($id);
        $actorId ??= $id;

        $this->db->createCommand()->update('{{%core_user}}', [
            'password_hash' => $passwordHash,
            'remember_token_hash' => null,
            'password_changed_at' => $changedAt,
            'password_expires_at' => $expiresAt,
            'password_reset_selector' => null,
            'password_reset_token_hash' => null,
            'password_reset_token_expires_at' => null,
            'updated_at' => $changedAt,
            'updated_by' => $actorId,
        ], [
            'id' => $id,
        ])->execute();

        if ($user !== null) {
            $logEntity = new UserEntity(
                id: $user->id,
                email: $user->email,
                passwordHash: $passwordHash,
                name: $user->name,
                status: $user->status,
                createdAt: $user->createdAt,
                updatedAt: $changedAt,
                lastLoginAt: $user->lastLoginAt,
                rememberTokenHash: null,
                passwordChangedAt: $changedAt,
                passwordExpiresAt: $expiresAt,
                passwordResetSelector: null,
                passwordResetTokenHash: null,
                passwordResetTokenExpiresAt: null,
                createdBy: $user->createdBy,
                updatedBy: $actorId,
            );

            $this->entityLogRepository->record(
                'user',
                $id,
                EntityLogRepository::ACTION_UPDATE,
                $logEntity,
                'UPDATE core_user SET password_hash = :password_hash, remember_token_hash = NULL, password_changed_at = :password_changed_at, password_expires_at = :password_expires_at, password_reset_selector = NULL, password_reset_token_hash = NULL, password_reset_token_expires_at = NULL, updated_at = :updated_at, updated_by = :updated_by WHERE id = :id',
                [
                    'password_hash' => '[redacted]',
                    'password_changed_at' => $changedAt,
                    'password_expires_at' => $expiresAt,
                    'updated_at' => $changedAt,
                    'updated_by' => $actorId,
                    'id' => $id,
                ],
            );
        }
    }

    private function replaceRoles(int $userId, array $roleIds): void
    {
        $this->db->createCommand()
            ->delete('{{%core_user_role}}', ['user_id' => $userId])
            ->execute();

        foreach ($roleIds as $roleId) {
            $this->db->createCommand()->insert('{{%core_user_role}}', [
                'user_id' => $userId,
                'role_id' => (int) $roleId,
            ])->execute();
        }
    }

    private function map(array $row): UserEntity
    {
        return new UserEntity(
            id: (int) $row['id'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            name: (string) $row['name'],
            status: (int) $row['status'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            lastLoginAt: $row['last_login_at'] !== null ? (string) $row['last_login_at'] : null,
            rememberTokenHash: ($row['remember_token_hash'] ?? null) !== null ? (string) $row['remember_token_hash'] : null,
            passwordChangedAt: ($row['password_changed_at'] ?? null) !== null ? (string) $row['password_changed_at'] : null,
            passwordExpiresAt: ($row['password_expires_at'] ?? null) !== null ? (string) $row['password_expires_at'] : null,
            passwordResetSelector: ($row['password_reset_selector'] ?? null) !== null ? (string) $row['password_reset_selector'] : null,
            passwordResetTokenHash: ($row['password_reset_token_hash'] ?? null) !== null ? (string) $row['password_reset_token_hash'] : null,
            passwordResetTokenExpiresAt: ($row['password_reset_token_expires_at'] ?? null) !== null ? (string) $row['password_reset_token_expires_at'] : null,
            createdBy: ($row['created_by'] ?? null) !== null ? (int) $row['created_by'] : null,
            updatedBy: ($row['updated_by'] ?? null) !== null ? (int) $row['updated_by'] : null,
        );
    }
}
