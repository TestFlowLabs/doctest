<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Parser;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Parser\AutoImportResolver;

final class AutoImportResolverTest extends TestCase
{
    #[Test]
    public function resolves_explicit_imports(): void
    {
        $resolver = new AutoImportResolver(['App\\Models\\User', 'App\\Services\\AuthService']);
        $code = $resolver->generateUseStatements();

        $this->assertStringContainsString('use App\\Models\\User;', $code);
        $this->assertStringContainsString('use App\\Services\\AuthService;', $code);
    }

    #[Test]
    public function empty_imports_returns_empty_string(): void
    {
        $resolver = new AutoImportResolver([]);
        $code = $resolver->generateUseStatements();

        $this->assertSame('', $code);
    }

    #[Test]
    public function deduplicates_imports(): void
    {
        $resolver = new AutoImportResolver([
            'App\\Models\\User',
            'App\\Models\\User',
            'App\\Services\\Auth',
        ]);
        $code = $resolver->generateUseStatements();

        $this->assertSame(1, substr_count($code, 'use App\\Models\\User;'));
        $this->assertSame(1, substr_count($code, 'use App\\Services\\Auth;'));
    }

    #[Test]
    public function resolves_wildcard_imports_from_classmap(): void
    {
        $classMap = [
            'App\\Models\\User' => '/path/User.php',
            'App\\Models\\Post' => '/path/Post.php',
            'App\\Services\\Auth' => '/path/Auth.php',
        ];

        $resolver = new AutoImportResolver(['App\\Models\\*'], $classMap);
        $code = $resolver->generateUseStatements();

        $this->assertStringContainsString('use App\\Models\\User;', $code);
        $this->assertStringContainsString('use App\\Models\\Post;', $code);
        $this->assertStringNotContainsString('App\\Services\\Auth', $code);
    }

    #[Test]
    public function excludes_existing_imports_from_block_code(): void
    {
        $resolver = new AutoImportResolver(['App\\Models\\User', 'App\\Services\\Auth']);
        $blockCode = "use App\\Models\\User;\n\$user = new User();";

        $code = $resolver->generateUseStatements($blockCode);

        $this->assertStringNotContainsString('use App\\Models\\User;', $code);
        $this->assertStringContainsString('use App\\Services\\Auth;', $code);
    }

    #[Test]
    public function mixed_explicit_and_wildcard(): void
    {
        $classMap = [
            'App\\Models\\User' => '/path/User.php',
            'App\\Models\\Post' => '/path/Post.php',
        ];

        $resolver = new AutoImportResolver(['App\\Helpers\\Str', 'App\\Models\\*'], $classMap);
        $code = $resolver->generateUseStatements();

        $this->assertStringContainsString('use App\\Helpers\\Str;', $code);
        $this->assertStringContainsString('use App\\Models\\User;', $code);
        $this->assertStringContainsString('use App\\Models\\Post;', $code);
    }
}
