<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Audit;

use TestFlowLabs\DocTest\CodeBlock\CodeBlock;

final class AuditScanner
{
    /** @var array<string, array<string>> */
    private const array PATTERNS = [
        'filesystem' => [
            'file_put_contents',
            'file_get_contents',
            'fopen',
            'fwrite',
            'unlink',
            'rmdir',
            'mkdir',
            'rename',
            'copy',
            'chmod',
            'chown',
            'glob',
            'scandir',
            'tempnam',
        ],
        'network' => [
            'curl_init',
            'curl_exec',
            'file_get_contents',
            'fopen',
            'fsockopen',
            'stream_socket_client',
            'Http::get',
            'Http::post',
            'Http::put',
            'Http::delete',
        ],
        'process' => [
            'exec',
            'shell_exec',
            'system',
            'passthru',
            'proc_open',
            'popen',
            'pcntl_exec',
        ],
    ];

    /**
     * @param array<CodeBlock> $blocks
     *
     * @return array<AuditFinding>
     */
    public function scan(array $blocks): array
    {
        $findings = [];

        foreach ($blocks as $block) {
            $blockFindings = $this->scanBlock($block);
            $findings = array_merge($findings, $blockFindings);
        }

        return $findings;
    }

    /**
     * @return array<AuditFinding>
     */
    private function scanBlock(CodeBlock $block): array
    {
        $findings = [];
        $seen = [];

        foreach (self::PATTERNS as $category => $functions) {
            foreach ($functions as $function) {
                $pattern = $this->buildPattern($function);

                if (preg_match($pattern, $block->rawCode) === 1) {
                    $key = $block->file . ':' . $block->startLine . ':' . $function;

                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $findings[] = new AuditFinding(
                        file: $block->file,
                        line: $block->startLine,
                        category: $category,
                        match: $function,
                    );
                }
            }
        }

        return $findings;
    }

    private function buildPattern(string $function): string
    {
        if (str_contains($function, '::')) {
            return '/\b' . preg_quote($function, '/') . '\b/';
        }

        return '/\b' . preg_quote($function, '/') . '\s*\(/';
    }
}
