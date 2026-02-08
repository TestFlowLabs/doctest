<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Config\BootstrapResolver;

beforeEach(function (): void {
    $this->tmpDir = sys_get_temp_dir().'/doctest-bootstrap-test-'.bin2hex(random_bytes(8));
    mkdir($this->tmpDir, 0755, true);
});

afterEach(function (): void {
    // Clean up temp directory
    $files = glob($this->tmpDir.'/*.php') ?: [];
    foreach ($files as $file) {
        unlink($file);
    }
    if (is_dir($this->tmpDir)) {
        rmdir($this->tmpDir);
    }
});

test('discovers php files from directory', function (): void {
    file_put_contents($this->tmpDir.'/laravel.php', "<?php\n// laravel bootstrap");
    file_put_contents($this->tmpDir.'/database.php', "<?php\n// db bootstrap");

    $resolver = new BootstrapResolver($this->tmpDir);

    expect($resolver->availableProfiles())->toBe(['database', 'laravel']);
});
test('maps filename to profile name strips extension', function (): void {
    file_put_contents($this->tmpDir.'/my-framework.php', "<?php\n// fw");

    $resolver = new BootstrapResolver($this->tmpDir);

    expect($resolver->availableProfiles())->toBe(['my-framework']);
});
test('resolve single profile returns require once', function (): void {
    file_put_contents($this->tmpDir.'/laravel.php', "<?php\n// laravel");

    $resolver = new BootstrapResolver($this->tmpDir);
    $code     = $resolver->resolve(['laravel']);

    expect($code)->toContain("require_once '");
    expect($code)->toContain('laravel.php');
});
test('resolve multiple profiles composes in order', function (): void {
    file_put_contents($this->tmpDir.'/laravel.php', "<?php\n// laravel");
    file_put_contents($this->tmpDir.'/database.php', "<?php\n// db");

    $resolver = new BootstrapResolver($this->tmpDir);
    $code     = $resolver->resolve(['laravel', 'database']);

    $laravelPos  = strpos($code, 'laravel.php');
    $databasePos = strpos($code, 'database.php');

    expect($laravelPos)->toBeLessThan($databasePos);
});
test('global bootstrap is prepended before profiles', function (): void {
    file_put_contents($this->tmpDir.'/laravel.php', "<?php\n// laravel");

    $resolver = new BootstrapResolver($this->tmpDir, "require_once 'vendor/autoload.php';");
    $code     = $resolver->resolve(['laravel']);

    $globalPos  = strpos($code, 'vendor/autoload.php');
    $profilePos = strpos($code, 'laravel.php');

    expect($globalPos)->toBeLessThan($profilePos);
});
test('resolve empty profiles returns only global bootstrap', function (): void {
    $resolver = new BootstrapResolver($this->tmpDir, "require_once 'vendor/autoload.php';");
    $code     = $resolver->resolve([]);

    expect($code)->toBe("require_once 'vendor/autoload.php';");
});
test('resolve empty profiles without global returns empty string', function (): void {
    $resolver = new BootstrapResolver($this->tmpDir);
    $code     = $resolver->resolve([]);

    expect($code)->toBe('');
});
test('throws runtime exception for unknown profile', function (): void {
    $resolver = new BootstrapResolver($this->tmpDir);

    $resolver->resolve(['nonexistent']);
})->throws(RuntimeException::class, 'Unknown bootstrap profile "nonexistent"');
test('available profiles returns sorted list', function (): void {
    file_put_contents($this->tmpDir.'/zebra.php', "<?php\n");
    file_put_contents($this->tmpDir.'/alpha.php', "<?php\n");
    file_put_contents($this->tmpDir.'/middle.php', "<?php\n");

    $resolver = new BootstrapResolver($this->tmpDir);

    expect($resolver->availableProfiles())->toBe(['alpha', 'middle', 'zebra']);
});
test('handles non existent directory gracefully', function (): void {
    $resolver = new BootstrapResolver('/nonexistent/path');

    expect($resolver->availableProfiles())->toBe([]);
});
test('non existent directory throws for any profile', function (): void {
    $resolver = new BootstrapResolver('/nonexistent/path');

    $resolver->resolve(['anything']);
})->throws(RuntimeException::class);
