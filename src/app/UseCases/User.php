<?php

namespace App\UseCases;

use App\Domains\Common\ValueObjects\MailAddress;
use App\Domains\User\Entities\User as Entity;
use App\Domains\User\UserRepository;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\UseCases\Factories\CommonDomainFactory;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Hash;

/**
 * ユーザーユースケース.
 */
class User
{
    use CommonDomainFactory;

    public function __construct(
        private readonly UserRepository $repository,
    ) {}

    /**
     * ユーザーを追加する.
     */
    public function add(
        string $identifier,
        array $name,
        array $address,
        array $phone,
        string $email,
        string $password,
        string $role,
    ): void {
        $entity = new Entity(
            identifier: new UserIdentifier($identifier),
            firstName: $this->extractString($name, 'first'),
            lastName: $this->extractString($name, 'last'),
            address: $this->extractAddress($address),
            phone: $this->extractPhone($phone),
            email: new MailAddress($email),
            password: Hash::make($password),
            role: $this->convertRole($role)
        );

        $this->repository->add($entity);
    }

    /**
     * ユーザーを更新する.
     */
    public function update(
        string $identifier,
        array $name,
        array $address,
        array $phone,
        string $email,
        string $password,
        string $role,
    ): void {
        $entity = new Entity(
            identifier: new UserIdentifier($identifier),
            firstName: $this->extractString($name, 'first'),
            lastName: $this->extractString($name, 'last'),
            address: $this->extractAddress($address),
            phone: $this->extractPhone($phone),
            email: new MailAddress($email),
            password: Hash::make($password),
            role: $this->convertRole($role)
        );

        $this->repository->update($entity);
    }

    /**
     * ユーザーを取得する.
     */
    public function find(string $identifier): Entity
    {
        return $this->repository->find(new UserIdentifier($identifier));
    }

    /**
     * ユーザー一覧を取得する.
     */
    public function list(): Enumerable
    {
        return $this->repository->list();
    }

    /**
     * ユーザーを削除する.
     */
    public function delete(string $identifier): void
    {
        $this->repository->delete(new UserIdentifier($identifier));
    }

    /**
     * 文字列をロールに変換する.
     */
    private function convertRole(string $role): Role
    {
        return match ($role) {
            Role::ADMIN->name => Role::ADMIN,
            Role::USER->name => Role::USER,
        };
    }
}
