<?php

declare(strict_types=1);

namespace Pest\Browser\Support;

/**
 * @phpstan-type Violations array<int, array{
 *       impact?: string|null,
 *       help?: string|null,
 *       helpUrl?: string|null,
 *       nodes?: array<int, array{
 *           target?: array<string>|string|null,
 *           html?: string|null,
 *           any?: array<int, array{
 *               message?: string|null,
 *               relatedNodes?: array<int, array{
 *                   target?: array<string>|string|null,
 *                   html?: string|null,
 *               }>|null,
 *           }>|null,
 *           none?: array<int, array{
 *               message?: string|null,
 *               relatedNodes?: array<int, array{
 *                   target?: array<string>|string|null,
 *                   html?: string|null,
 *               }>|null,
 *           }>|null,
 *       }>|null,
 *   }>
 */
final class AccessibilityFormatter
{
    /**
     * @param  Violations  $violations
     */
    public static function format(array $violations): string
    {
        $issuesCount = count($violations);
        $lines = ["{$issuesCount} Accessibility issues found"];

        foreach ($violations as $v) {
            $impactStr = $v['impact'] ?? 'unknown';
            $help = $v['help'] ?? '';
            $helpUrl = $v['helpUrl'] ?? '';
            $header = sprintf('- [%s] %s %s', $impactStr, $help, $helpUrl);
            $lines[] = $header;

            $nodes = $v['nodes'] ?? [];
            foreach ($nodes as $node) {
                $selector = '';
                if (isset($node['target'])) {
                    $targets = $node['target'];
                    if (is_array($targets)) {
                        $selector = implode(' ', array_values(array_filter($targets, static fn (string $s): bool => $s !== '')));
                    } elseif (is_string($targets)) {
                        $selector = $targets;
                    }
                }
                if ($selector !== '') {
                    $lines[] = "  Selector: {$selector}";
                }

                $html = $node['html'] ?? null;
                if (is_string($html) && $html !== '') {
                    $lines[] = "  HTML: {$html}";
                }

                // any/none messages
                foreach (['any' => '  any:', 'none' => '  none:'] as $key => $label) {
                    $checks = $node[$key] ?? [];
                    $messages = [];
                    foreach ($checks as $check) {
                        if (isset($check['message']) && $check['message'] !== '') {
                            $messages[] = $check['message'];
                        }
                    }
                    if ($messages !== []) {
                        $lines[] = $label;
                        foreach ($messages as $m) {
                            $lines[] = "    - {$m}";
                        }
                    }
                }

                // related nodes (from both any and none)
                $relatedNodes = [];
                foreach (['any', 'none'] as $k) {
                    $checks = $node[$k] ?? [];
                    foreach ($checks as $check) {
                        $rels = $check['relatedNodes'] ?? [];
                        foreach ($rels as $rel) {
                            $relatedNodes[] = $rel;
                        }
                    }
                }

                if ($relatedNodes !== []) {
                    $lines[] = '  Related nodes:';
                    foreach ($relatedNodes as $relatedNode) {
                        $relSelector = '';
                        if (isset($relatedNode['target'])) {
                            $targets = $relatedNode['target'];
                            if (is_array($targets)) {
                                $relSelector = implode(' ', array_values(array_filter($targets, static fn (string $s): bool => $s !== '')));
                            } elseif (is_string($targets)) {
                                $relSelector = $targets;
                            }
                        }
                        if ($relSelector !== '') {
                            $lines[] = "    Selector: {$relSelector}";
                        }
                        $relHtml = $relatedNode['html'] ?? null;
                        if (is_string($relHtml) && $relHtml !== '') {
                            $lines[] = "    HTML: {$relHtml}";
                        }
                    }
                }
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
