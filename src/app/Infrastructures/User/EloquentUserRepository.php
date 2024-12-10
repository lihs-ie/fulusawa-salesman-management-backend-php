<?php

namespace App\Infrastructures\User;

use App\Domains\Common\ValueObjects\MailAddress;
use App\Domains\User\Entities\User as Entity;
use App\Domains\User\UserRepository;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Infrastructures\Common\AbstractEloquentRepository;
use App\Infrastructures\Support\Common\EloquentCommonDomainDeflator;
use App\Infrastructures\Support\Common\EloquentCommonDomainRestorer;
use App\Infrastructures\User\Models\User as Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Enumerable;

/**
 * ユーザーリポジトリ.
 */
class EloquentUserRepository extends AbstractEloquentRepository implements UserRepository
{
    use EloquentCommonDomainRestorer;
    use EloquentCommonDomainDeflator;

    /**
     * コンストラクタ.
     */
    public function __construct(
        private readonly Record $builder
    ) {}

    /**
     * {@inheritDoc}
     */
    public function add(Entity $user): void
    {
        try {
            $this->createQuery()
                ->create(
                    [
                        'identifier' => $user->identifier()->value(),
                        'first_name' => $user->firstName(),
                        'last_name' => $user->lastName(),
                        'address' => $this->deflateAddress($user->address()),
                        'phone_number' => $this->deflatePhoneNumber($user->phone()),
                        'email' => $user->email()->value(),
                        'password' => $user->password(),
                        'role' => $user->role()->name,
                    ]
                )
            ;
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception, $user->identifier()->value());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(Entity $user): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($user->identifier())
            ->first()
        ;

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('User not found: %s', $user->identifier()->value()));
        }

        try {
            $target->update(
                [
                    'first_name' => $user->firstName(),
                    'last_name' => $user->lastName(),
                    'address' => $this->deflateAddress($user->address()),
                    'phone_number' => $this->deflatePhoneNumber($user->phone()),
                    'email' => $user->email()->value(),
                    'password' => $user->password(),
                    'role' => $user->role()->name,
                ]
            );
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception, $user->identifier()->value());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find(UserIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($record)) {
            throw new \OutOfBoundsException(\sprintf('User not found: %s', $identifier->value()));
        }

        return $this->restoreEntity($record);
    }

    /**
     * {@inheritDoc}
     */
    public function ofCredentials(MailAddress $email, string $password): Entity
    {
        $record = $this->createQuery()
            ->ofCredentials($email, $password)
            ->first()
        ;

        if (\is_null($record)) {
            throw new \OutOfBoundsException('User not found.');
        }

        return $this->restoreEntity($record);
    }

    /**
     * {@inheritDoc}
     */
    public function list(): Enumerable
    {
        return $this->createQuery()
            ->get()
            ->map(fn (Record $record): Entity => $this->restoreEntity($record))
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(UserIdentifier $identifier): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('User not found: %s', $identifier->value()));
        }

        $target->delete();
    }

    /**
     * クエリビルダーを生成する.
     */
    private function createQuery(): Builder
    {
        return $this->builder->newQuery();
    }

    /**
     * レコードからユーザーエンティティを復元する.
     */
    private function restoreEntity(Record $record): Entity
    {
        return new Entity(
            identifier: new UserIdentifier($record->identifier),
            firstName: $record->first_name,
            lastName: $record->last_name,
            address: $this->restoreAddress($record),
            phone: $this->restorePhone($record),
            email: new MailAddress($record->email),
            password: $record->password,
            role: $this->restoreRole($record->role),
        );
    }

    /**
     * レコードからユーザー権限を復元する.
     */
    private function restoreRole(string $role): Role
    {
        return match ($role) {
            Role::ADMIN->name => Role::ADMIN,
            Role::USER->name => Role::USER,
            default => throw new \UnexpectedValueException(\sprintf('Invalid role: %s', $role)),
        };
    }
}
