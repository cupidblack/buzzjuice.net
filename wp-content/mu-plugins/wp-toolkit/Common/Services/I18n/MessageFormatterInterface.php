<?php
// Copyright 1999-2026. WebPros International GmbH. All rights reserved.

namespace Webpros\WptkWpPlugin\WpToolkit\Common\Services\I18n;

interface MessageFormatterInterface
{
    /**
     * @param string $messageTemplate
     * @param array $parameters
     * @param string $localeCode
     * @return string
     */
    public function format($messageTemplate, array $parameters, $localeCode);
}
