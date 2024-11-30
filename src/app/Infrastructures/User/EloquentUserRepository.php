<?php

namespace App\Infrastructures\User;

use App\Domains\Common\ValueObjects\MailAddress;
use App\Domains\User\Entities\User as Entity;
use App\Domains\User\UserRepository;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Infrastructures\Support\Common\EloquentCommonDomainRestorer;
use App\Infrastructures\User\Models\User as Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Enumerable;

/**
 * ユーザーリポジトリ.
 */
class EloquentUserRepository implements UserRepository
{
    use EloquentCommonDomainRestorer;

    public function __construct(
        private readonly Record $builder
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function persist(Entity $user): void
    {
        $phone = $user->phone();
        $address = $user->address();

        $this->createQuery()
            ->updateOrCreate(
                ['identifier' => $user->identifier()->value()],
                [
                    'identifier' => $user->identifier()->value(),
                    'first_name' => $user->firstName(),
                    'last_name' => $user->lastName(),
                    'phone_area_code' => $phone->areaCode(),
                    'phone_local_code' => $phone->localCode(),
                    'phone_subscriber_number' => $phone->subscriberNumber(),
                    'postal_code_first' => $address->postalCode()->first(),
                    'postal_code_second' => $address->postalCode()->second(),
                    'prefecture' => $address->prefecture->value,
                    'city' => $address->city(),
                    'street' => $address->street(),
                    'building' => $address->building(),
                    'email' => $user->email()->value(),
                    'password' => $user->password(),
                    'role' => $user->role->name,
                ]
            );
    }

    /**
     * {@inheritDoc}
     */
    public function find(UserIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->where('identifier', $identifier->value())
            ->first();

        if (\is_null($record)) {
            throw new \OutOfBoundsException(\sprintf('User not found: %s', $identifier->value()));
        }

        return $this->restoreEntity($record);
    }

    /**
     * {@inheritDoc}
     */
    public function list(): Enumerable
    {
        return $this->createQuery()
            ->where('deleted_at', null)
            ->get()
            ->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(UserIdentifier $identifier): void
    {
        $target = Record::where('identifier', $identifier->value())->first();

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('User not found: %s', $identifier->value()));
        }

        $target->delete();
    }

    /**
     * クエリビルダーを生成する.
     *
     * @return Builder
     */
    private function createQuery(): Builder
    {
        return $this->builder->newQuery();
    }

    /**
     * レコードからユーザーエンティティを復元する.
     *
     * @param Record $record
     * @return Entity
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
     *
     * @param string $role
     * @return Role
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
