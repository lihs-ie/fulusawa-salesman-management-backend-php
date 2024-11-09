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
 * ユーザーユースケース
 */
class User
{
    use CommonDomainFactory;

    public function __construct(
        private readonly UserRepository $repository,
    ) {
    }

    /**
     * ユーザーを永続化する
     *
     * @param string $identifier
     * @param array $name
     * @param array $address
     * @param array $phone
     * @param string $email
     * @param string $password
     * @param string $role
     * @return void
     */
    public function persist(
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

        $this->repository->persist($entity);
    }

    /**
     * ユーザーを取得する
     *
     * @param string $identifier
     * @return Entity
     */
    public function find(string $identifier): Entity
    {
        return $this->repository->find(new UserIdentifier($identifier));
    }

    /**
     * ユーザー一覧を取得する
     *
     * @return Enumerable
     */
    public function list(): Enumerable
    {
        return $this->repository->list();
    }

    /**
     * ユーザーを削除する
     *
     * @param string $identifier
     * @return void
     */
    public function delete(string $identifier): void
    {
        $this->repository->delete(new UserIdentifier($identifier));
    }

    /**
     * 文字列をロールに変換する
     *
     * @param string $role
     * @return Role
     */
    private function convertRole(string $role): Role
    {
        return match ($role) {
            '1' => Role::ADMIN,
            '2' => Role::USER,
        };
    }
}
