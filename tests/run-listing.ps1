param([Parameter(Mandatory=$true)][string]$PhpPath,[Parameter(Mandatory=$true)][string]$WordPressRoot)
$ErrorActionPreference='Stop'
$taskExtensions=(Join-Path (Split-Path $PhpPath) 'ext').Replace('\','/')
$taskArgs=@('-n','-d',"extension_dir=$taskExtensions",'-d','extension=mysqli','-d','extension=mbstring','-d','extension=openssl','-d','extension=curl','-d','memory_limit=512M')
$taskResults=@()
function Invoke-SandboxCheck([string]$Script,[string[]]$Extra,[string]$Marker,[string]$Label) {
    $taskOutput=& $PhpPath @taskArgs $Script $WordPressRoot @Extra 2>&1
    $taskExit=$LASTEXITCODE
    $taskOutput | Set-Content ".runtime/$Label.txt" -Encoding utf8
    if($taskExit -ne 0 -or -not (($taskOutput -join "`n").Contains($Marker))){$taskOutput | Select-Object -Last 12 | Write-Output;throw "$Label failed"}
    $script:taskResults+=@{label=$Label;state='PASS';result=($taskOutput | Select-Object -Last 1);processor_evidence='synthetic only'}
    Write-Output "PASS: $Label"
}
foreach($taskMode in @('legacy','legacy-no-sync','hpos','hpos-no-sync')) {
    $env:KREV_LISTING_TEST_STACK='1'
    $taskStorage=if($taskMode.StartsWith('legacy')){'legacy'}else{'hpos'}
    $taskSync=if($taskMode.EndsWith('no-sync')){'off'}else{'on'}
    Invoke-SandboxCheck 'tests/storage-mode.php' @($taskStorage,$taskSync) 'Synthetic database set' "storage-$taskMode"
    Invoke-SandboxCheck 'tests/listing-checkout.php' @() 'listing checkout assertions passed.' "listing-$taskMode"
    Invoke-SandboxCheck 'tests/booking.php' @() 'booking assertions passed.' "booking-$taskMode"
    Invoke-SandboxCheck 'tests/booking-bridge.php' @() 'booking bridge assertions passed.' "booking-bridge-$taskMode"
    Invoke-SandboxCheck 'tests/booking-lifecycle.php' @() 'lifecycle assertions passed.' "booking-lifecycle-$taskMode"
    if($taskMode -in @('legacy','hpos')) {
        Remove-Item Env:KREV_LISTING_TEST_STACK
        Invoke-SandboxCheck 'tests/integration.php' @() 'behavioral assertions passed.' "operator-regression-$taskMode"
    }
}
Remove-Item Env:KREV_LISTING_TEST_STACK
Invoke-SandboxCheck 'tests/stripe-setup.php' @('setup') 'assertions passed' 'stripe-setup-regression'
$taskResults | ConvertTo-Json -Depth 4 | Set-Content '.runtime/listing-acceptance-results.json' -Encoding utf8
Write-Output 'PASS: isolated acceptance matrix complete; real processor tests remain separate.'
