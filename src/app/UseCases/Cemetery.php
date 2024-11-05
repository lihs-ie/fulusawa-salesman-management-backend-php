<?php

namespace App\UseCases;

use App\Domains\Cemetery\CemeteryRepository;
use App\Domains\Cemetery\Entities\Cemetery as Entity;
use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Cemetery\ValueObjects\CemeteryType;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\UseCases\Factories\CommonDomainFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Enumerable;

/**
 * 墓地情報ユースケース
 */
class Cemetery
{
    use CommonDomainFactory;

    public function __construct(
        private readonly CemeteryRepository $repository,
    ) {
    }

    /**
     * 墓地情報を永続化する
     *
     * @param string|null $identifier
     * @param string $customer
     * @param string $name
     * @param string $type
     * @param string $construction
     * @param boolean $inHouse
     * @return void
     */
    public function persist(
        string $identifier,
        string $customer,
        string $name,
        string $type,
        string $construction,
        bool $inHouse,
    ): void {
        $entity = new Entity(
            identifier: new CemeteryIdentifier($identifier),
            customer: new CustomerIdentifier($customer),
            name: $name,
            construction: CarbonImmutable::parse($construction),
            type: $this->convertCemeteryType($type),
            inHouse: $inHouse,
        );

        $this->repository->persist($entity);
    }

    /**
     * 墓地情報を取得する
     *
     * @param string $identifier
     * @return Entity
     */
    public function find(string $identifier): Entity
    {
        return $this->repository->find(new CemeteryIdentifier($identifier));
    }

    /**
     * 墓地情報一覧を取得する
     *
     * @return Enumerable<Entity>
     */
    public function list(): Enumerable
    {
        return $this->repository->list();
    }

    /**
     * 顧客の墓地情報一覧を取得する
     *
     * @param string $customer
     * @return Enumerable<Entity>
     */
    public function ofCustomer(string $customer): Enumerable
    {
        return $this->repository->ofCustomer(new CustomerIdentifier($customer));
    }

    /**
     * 墓地情報を削除する
     *
     * @param string $identifier
     * @return void
     */
    public function delete(string $identifier): void
    {
        $this->repository->delete(new CemeteryIdentifier($identifier));
    }

    /**
     * 文字列から墓地種別を生成する
     *
     * @param string $type
     * @return CemeteryType
     */
    private function convertCemeteryType(string $type): CemeteryType
    {
        return match ($type) {
            '1' => CemeteryType::INDIVIDUAL,
            '2' => CemeteryType::FAMILY,
            '3' => CemeteryType::COMMUNITY,
            '4' => CemeteryType::OTHER,
        };
    }
}
