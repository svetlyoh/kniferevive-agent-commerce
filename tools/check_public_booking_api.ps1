# Read-only merchant API check. No session, booking, order or payment is created.
# Run with PowerShell 7. HTTP/2 avoids the client-specific HTTP/1.1 challenge
# observed with Invoke-WebRequest; this does not solve a browser challenge.
[CmdletBinding()]
param([ValidatePattern('^[0-9]{5}$')][string]$PostalCode = '94565')

$ErrorActionPreference = 'Stop'
if ($PSVersionTable.PSVersion.Major -lt 7) {
    throw 'Use PowerShell 7 (pwsh) for HTTP/2, or your existing Node/Python HTTP client.'
}

$apiBase = 'https://kniferevive.com/wp-json/kniferevive-agent/v1'
$headers = @{Accept = 'application/json'; 'User-Agent' = 'KnifeRevive-Concierge-Diagnostics/0.5.14'}
$checks = [System.Collections.Generic.List[object]]::new()

function Read-PublicJson([string]$Path) {
    $response = Invoke-WebRequest -Uri ($apiBase + $Path) -HttpVersion 2.0 `
        -Headers $headers -MaximumRedirection 0 -TimeoutSec 20
    $contentType = [string]($response.Headers['Content-Type'] -join ',')
    if ($response.StatusCode -ne 200 -or $contentType -notmatch '^application/json(?:;|$)') {
        throw "Public API did not return HTTP 200 JSON for $Path. Do not treat HTML or a challenge as API data."
    }
    $checks.Add([ordered]@{path = $Path; status = [int]$response.StatusCode; http_version = $response.BaseResponse.Version.ToString(); content_type = $contentType})
    return ($response.Content | ConvertFrom-Json)
}

$capabilities = Read-PublicJson '/capabilities'
$options = Read-PublicJson '/booking-options'
$availability = Read-PublicJson '/booking-availability'
$coverage = Read-PublicJson ('/booking-coverage?postal_code=' + $PostalCode)
$expectedModes = @('prepaid_dropoff','prepaid_dropoff_delivery','prepaid_pickup','prepaid_pickup_delivery','pay_later_dropoff')

if (-not $capabilities.discovery.anonymous -or -not $capabilities.booking.enabled -or -not $options.enabled) {
    throw 'Live anonymous discovery or booking is disabled. Do not advertise a working booking flow.'
}
if ($options.handoff_options.Count -ne 5 -or $options.services.Count -lt 1) {
    throw 'The live service menu does not contain five options and a service price.'
}
for ($i = 0; $i -lt $expectedModes.Count; $i++) {
    $choice = $options.handoff_options[$i]
    if ($choice.mode -ne $expectedModes[$i]) { throw 'Unexpected option order or mode.' }
    $expectedFee = switch ($i) {
        1 { [int]$options.merchant_trip_fee_minor }
        2 { [int]$options.merchant_trip_fee_minor }
        3 { [int]$options.merchant_round_trip_fee_minor }
        default { 0 }
    }
    $feeLabel = [regex]::Match($choice.label, '\$(?<amount>[0-9]+(?:\.[0-9]{1,2})?) (?<kind>round-trip fee|trip fee)')
    if (-not $feeLabel.Success) { throw 'A live option omits its numeric trip fee.' }
    $labelFeeMinor = [decimal]::Parse($feeLabel.Groups['amount'].Value, [Globalization.CultureInfo]::InvariantCulture) * 100
    if ([int]$choice.transport_fee_minor -ne $expectedFee -or $labelFeeMinor -ne $expectedFee -or ($i -eq 3 -and $feeLabel.Groups['kind'].Value -ne 'round-trip fee')) {
        throw 'A live option has a mismatched fee or an amount-free label.'
    }
}
if ($options.handoff_options[4].payment_required -or $options.handoff_options[4].zip_required -or $options.handoff_options[4].label -notmatch 'nothing due now') {
    throw 'The last option is not unpaid self drop-off/collection.'
}

[ordered]@{
    checked_at_utc = [DateTime]::UtcNow.ToString('o')
    result = 'public_discovery_pass'
    adapter_version = $options.adapter_version
    checks = @($checks.ToArray())
    service_prices = @($options.services | Select-Object product_id,title,unit_price_minor,currency)
    handoff_options = @($options.handoff_options | Select-Object mode,label,transport_fee_minor,zip_required,payment_required)
    single_trip_fee_minor = $options.merchant_trip_fee_minor
    combined_trip_fee_minor = $options.merchant_round_trip_fee_minor
    postal_code_checked = $PostalCode
    coverage = $coverage
    booking_availability_response_received = ($null -ne $availability)
    booking_or_order_created = $false
    payment_or_refund_sent = $false
    buyer_bot_runtime_verified = $false
} | ConvertTo-Json -Depth 12
