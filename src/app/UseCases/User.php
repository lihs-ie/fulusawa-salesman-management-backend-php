<?php

namespace App\UseCases;

use App\Domains\Common\ValueObjects\MailAddress;
use App\Domains\User\Entities\User as Entity;
use App\Domains\User\UserRepository;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\UseCases\Factories\CommonDomainFactory;
use Illuminate\Support\Enumerable;

/**
 * ユーザーユースケース
 */
class User
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly CommonDomainFactory $factory
    ) {
    }

    /**
     * ユーザーを永続化する
     *
     * @param string $identifier
     * @param array $name
     * @param array $address
     * @param array $phone
     * @param string $mail
     * @param string $role
     * @return void
     */
    public function persist(
        string $identifier,
        array $name,
        array $address,
        array $phone,
        string $mail,
        string $role,
    ): void {
        $entity = new Entity(
            identifier: new UserIdentifier($identifier),
            firstName: $this->factory->extractString($name, 'first'),
            lastName: $this->factory->extractString($name, 'last'),
            address: $this->factory->extractAddress($address),
            phone: $this->factory->extractPhone($phone),
            mail: new MailAddress($mail),
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
