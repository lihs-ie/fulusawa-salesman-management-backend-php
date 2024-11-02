<?php

namespace Application\Rules;

use PHPMD\AbstractNode;
use PHPMD\AbstractRule;
use PHPMD\Rule\ClassAware;
use PHPMD\Rule\InterfaceAware;
use SplFileInfo;

/**
 * PSR4準拠していないFQCN・ファイルパスを検出するカスタムルールセットクラス.
 */
class PSR4 extends AbstractRule implements ClassAware, InterfaceAware
{
    /**
     * 対象となるディレクトリ.
     */
    private const TARGET_DIRECTORIES = [
        '/app/',
        '/tests/',
    ];

    /**
     * FQCNがファイルパスと一致しているかを確認する.
     */
    public function apply(AbstractNode $node): void
    {
        $filename = $node->getFileName();

        if (!$this->matchesTarget($filename)) {
            return;
        }

        $fqcn = $node->getFullQualifiedName();

        $expected = $this->getExpectedPath($fqcn, [
            $this->getStringProperty('appNamespacePrefix') => $this->getStringProperty('appPathPrefix'),
            $this->getStringProperty('testsNamespacePrefix') => $this->getStringProperty('testsPathPrefix'),
            $this->getStringProperty('rulesNamespacePrefix') => $this->getStringProperty('rulesPathPrefix'),
        ]);

        if (!$this->compare($expected, $filename)) {
            $this->addViolation($node, ['fqcn' => $fqcn]);
        }
    }

    /**
     * ファイルが対象かどうかを判定する.
     */
    protected function matchesTarget(string $filename): bool
    {
        $root = new SplFileInfo(__DIR__ . '/../../../');
        $rootPath = $root->getRealPath();

        if (!$this->compareLeft($filename, $rootPath)) {
            return false;
        }

        $candidate = \str_replace($rootPath, '', $filename);

        foreach (static::TARGET_DIRECTORIES as $target) {
            if ($this->compareLeft($candidate, $target)) {
                return true;
            }
        }

        return false;
    }

    /**
     * FQCNから期待するファイルパスを取得する.
     */
    protected function getExpectedPath(string $fqcn, array $prefixes): string
    {
        foreach ($prefixes as $namespacePrefix => $pathPrefix) {
            if ($this->compareLeft($fqcn, $namespacePrefix)) {
                return $this->translateNamespaceToPath($fqcn, $namespacePrefix, $pathPrefix);
            }
        }

        return $this->getStringProperty('globalNamespacePrefix');
    }

    /**
     * 期待するファイルパスと実際のファイルパスを比較する.
     */
    protected function compare(string $expected, string $filename): bool
    {
        return $this->compareRight($filename, $expected);
    }

    /**
     * FQCNをファイルパス相当に変換する.
     */
    protected function translateNamespaceToPath(string $fqcn, string $namespacePrefix, string $pathPrefix): string
    {
        $pattern = \sprintf(
            '!^(?<prefix>%s)(?<target>.+)$!',
            \str_replace('\\', '\\\\', $namespacePrefix),
        );

        \preg_match($pattern, $fqcn, $matches);
        $target = \ltrim($matches['target'], '\\');

        return $pathPrefix . \DIRECTORY_SEPARATOR . \str_replace('\\', \DIRECTORY_SEPARATOR, $target) . '.php';
    }

    /**
     * 文字列が指定された文字列から始まっているかを判定する.
     */
    protected function compareLeft(string $haystack, string $needle): bool
    {
        return \strncmp($haystack, $needle, \strlen($needle)) === 0;
    }

    /**
     * 文字列が指定された文字列で終わっているかを判定する.
     */
    protected function compareRight(string $haystack, string $needle): bool
    {
        return \substr_compare($haystack, $needle, -\strlen($needle)) === 0;
    }
}
