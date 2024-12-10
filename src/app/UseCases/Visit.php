<?php

namespace App\UseCases;

use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\Entities\Visit as Entity;
use App\Domains\Visit\ValueObjects\Criteria;
use App\Domains\Visit\ValueObjects\Criteria\Sort;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Domains\Visit\ValueObjects\VisitResult;
use App\Domains\Visit\VisitRepository;
use App\UseCases\Factories\CommonDomainFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Enumerable;

/**
 * 訪問ユースケース.
 */
class Visit
{
    use CommonDomainFactory;

    /**
     * コンストラクタ.
     */
    public function __construct(
        private readonly VisitRepository $repository,
    ) {}

    /**
     * 訪問を永続化する.
     */
    public function add(
        string $identifier,
        string $user,
        string $visitedAt,
        array $address,
        ?array $phone,
        bool $hasGraveyard,
        ?string $note,
        string $result
    ): void {
        $entity = new Entity(
            identifier: new VisitIdentifier($identifier),
            user: new UserIdentifier($user),
            visitedAt: CarbonImmutable::parse($visitedAt),
            address: $this->extractAddress($address),
            phone: \is_null($phone) ? null : $this->extractPhone($phone),
            hasGraveyard: $hasGraveyard,
            note: $note,
            result: $this->convertVisitResult($result),
        );

        $this->repository->add($entity);
    }

    /**
     * 訪問を更新する.
     */
    public function update(
        string $identifier,
        string $user,
        string $visitedAt,
        array $address,
        ?array $phone,
        bool $hasGraveyard,
        ?string $note,
        string $result
    ): void {
        $entity = new Entity(
            identifier: new VisitIdentifier($identifier),
            user: new UserIdentifier($user),
            visitedAt: CarbonImmutable::parse($visitedAt),
            address: $this->extractAddress($address),
            phone: \is_null($phone) ? null : $this->extractPhone($phone),
            hasGraveyard: $hasGraveyard,
            note: $note,
            result: $this->convertVisitResult($result),
        );

        $this->repository->update($entity);
    }

    /**
     * 訪問を取得する.
     */
    public function find(string $identifier): Entity
    {
        return $this->repository->find(new VisitIdentifier($identifier));
    }

    /**
     * 訪問一覧を取得する.
     *
     * @return Enumerable<Entity>
     */
    public function list(array $conditions): Enumerable
    {
        return $this->repository->list($this->createCriteria($conditions));
    }

    /**
     * 訪問を削除する.
     */
    public function delete(string $identifier): void
    {
        $this->repository->delete(new VisitIdentifier($identifier));
    }

    /**
     * 文字列から訪問結果を生成する.
     */
    private function convertVisitResult(string $result): VisitResult
    {
        return match ($result) {
            VisitResult::NO_CONTRACT->name => VisitResult::NO_CONTRACT,
            VisitResult::CONTRACT->name => VisitResult::CONTRACT,
        };
    }

    /**
     * 配列から検索条件を生成する.
     */
    private function createCriteria(array $conditions): Criteria
    {
        $user = isset($conditions['user']) ?
            new UserIdentifier($this->extractString($conditions, 'user')) : null;

        return new Criteria(
            user: $user,
            sort: $this->convertSort($this->extractString($conditions, 'sort')),
        );
    }

    /**
     * 文字列からソート条件を生成する.
     */
    private function convertSort(?string $sort): ?Sort
    {
        if (\is_null($sort)) {
            return null;
        }

        return match ($sort) {
            Sort::VISITED_AT_ASC->name => Sort::VISITED_AT_ASC,
            Sort::VISITED_AT_DESC->name => Sort::VISITED_AT_DESC,
        };
    }
}
