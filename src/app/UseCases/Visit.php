<?php

namespace App\UseCases;

use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\Entities\Visit as Entity;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Domains\Visit\ValueObjects\VisitResult;
use App\Domains\Visit\VisitRepository;
use App\UseCases\Factories\CommonDomainFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Enumerable;

/**
 * 訪問ユースケース
 */
class Visit
{
    public function __construct(
        private readonly VisitRepository $repository,
        private readonly CommonDomainFactory $factory
    ) {
    }

    /**
     * 訪問を永続化する
     *
     * @param string $identifier
     * @param string $user
     * @param string $visitedAt
     * @param array $address
     * @param array|null $phone
     * @param boolean $hasGraveyard
     * @param string|null $note
     * @param string $result
     * @return void
     */
    public function persist(
        string $identifier,
        string $user,
        string $visitedAt,
        array $address,
        array|null $phone,
        bool $hasGraveyard,
        string|null $note,
        string $result
    ): void {
        $entity = new Entity(
            identifier: new VisitIdentifier($identifier),
            user: new UserIdentifier($user),
            visitedAt: CarbonImmutable::parse($visitedAt),
            address: $this->factory->extractAddress($address),
            phone: \is_null($phone) ? null : $this->factory->extractPhone($phone),
            hasGraveyard: $hasGraveyard,
            note: $note,
            result: $this->convertVisitResult($result),
        );

        $this->repository->persist($entity);
    }

    /**
     * 訪問を取得する
     *
     * @param string $identifier
     * @return Entity
     */
    public function find(string $identifier): Entity
    {
        return $this->repository->find(new VisitIdentifier($identifier));
    }

    /**
     * 訪問一覧を取得する
     *
     * @return Enumerable<Entity>
     */
    public function list(): Enumerable
    {
        return $this->repository->list();
    }

    /**
     * 訪問を削除する
     *
     * @param string $identifier
     * @return void
     */
    public function delete(string $identifier): void
    {
        $this->repository->delete(new VisitIdentifier($identifier));
    }

    /**
     * 文字列から訪問結果を生成する
     *
     * @param string $result
     * @return VisitResult
     */
    private function convertVisitResult(string $result): VisitResult
    {
        return match ($result) {
            '0' => VisitResult::NO_CONTRACT,
            '1' => VisitResult::CONTRACT,
        };
    }
}
