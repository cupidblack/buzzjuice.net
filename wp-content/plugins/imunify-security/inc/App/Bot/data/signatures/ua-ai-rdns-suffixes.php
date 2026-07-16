<?php
return array (
  'source_url' => 'MANUAL (provider docs — developer.amazon.com/amazonbot, commoncrawl.org/faq, bytedance.com, you.com/bot)',
  'fetched_at' => '2026-05-19T00:00:00+00:00',
  'note' => 'AI crawler rDNS providers. Same FCrDNS mechanism as ua-rdns-suffixes.php. Consulted only after the bundled IpRangeLookup misses for AI crawler UAs.',
  'providers' =>
  array (
    'amazon' =>
    array (
      'tokens' => array ( 0 => 'Amazonbot', 1 => 'Amzn-SearchBot' ),
      'suffixes' => array ( 0 => '.amazonbot.amazon' ),
    ),
    'commoncrawl' =>
    array (
      'tokens' => array ( 0 => 'CCBot' ),
      'suffixes' => array ( 0 => '.commoncrawl.org' ),
    ),
    'bytedance' =>
    array (
      'tokens' => array ( 0 => 'Bytespider' ),
      'suffixes' => array ( 0 => '.bytedance.com' ),
    ),
    'you' =>
    array (
      'tokens' => array ( 0 => 'YouBot' ),
      'suffixes' => array ( 0 => '.you.com' ),
    ),
  ),
);
