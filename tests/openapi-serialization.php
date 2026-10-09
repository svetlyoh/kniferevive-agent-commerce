<?php
// Read-only contract regression; no WordPress database or gateway connection.
define('ABSPATH', __DIR__ . '/');
require dirname(__DIR__) . '/wordpress/kniferevive-agent-commerce/includes/Api.php';
use KnifeRevive\AgentCommerce\Api;
$source = file_get_contents(dirname(__DIR__) . '/wordpress/kniferevive-agent-commerce/assets/openapi.json');
$expected = json_decode($source, false, 64, JSON_THROW_ON_ERROR);
$served = json_decode(json_encode(Api::openapi(null), JSON_THROW_ON_ERROR), false, 64, JSON_THROW_ON_ERROR);
if ($served != $expected) {
    fwrite(STDERR, "FAIL: REST serialization changes the published OpenAPI contract.\n");
    exit(1);
}
foreach (['/sessions', '/sessions/attach'] as $path) {
    $properties = $served->paths->{$path}->post->requestBody->content->{'application/json'}->schema->properties;
    if (!is_object($properties)) {
        fwrite(STDERR, "FAIL: empty schema properties must serialize as an object.\n");
        exit(1);
    }
}
echo "PASS: REST OpenAPI serialization preserves the entire document and empty objects.\n";
