<?php
// Copyright 1999-2026. WebPros International GmbH. All rights reserved.

namespace Webpros\WptkWpPlugin\WpToolkit\Waf\RulesConverter;

final class PatchstackRuleConverter implements RuleConverterInterface
{
    public static function convert(array $rule)
    {
        return [
            'id' => \crc32($rule['vulnerabilityId']),
            'rules' => $rule['rules'],
            'type' => 'BLOCK',
        ];
    }
}
