<?php

$zip_file = 'hledger.zip';
$hledger = 'hledger';
$hledger_ui = 'hledger-ui';
$hledger_web = 'hledger-web';

if (strtoupper(substr(PHP_OS, 0, 5)) === 'LINUX') {
  $os = 'ubuntu';
} else if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
  $os = 'windows';
} else if (strtoupper(substr(PHP_OS, 0, 6)) === 'DARWIN') {
  $os = 'macos';
} else {
  echo("Can't detect OS: " . PHP_OS);
  exit(1);
}

$version = '1.21';

$url = "https://github.com/simonmichael/hledger/releases/download/${version}/hledger-${os}.zip";

$ch = curl_init($url);
$fp = fopen($zip_file, "w");
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_exec($ch);
if(curl_error($ch)) {
  echo("Error downloading hledger: " . curl_error($ch));
  exit(1);
}
curl_close($ch);
fclose($fp);

$zip = new ZipArchive;
$result = $zip->open($zip_file);
if ($result === true) {
  $zip->extractTo('bin/');
  $zip->close();
} else {
  echo("Error unzipping hledger");
  exit(1);
}

unlink("bin/$hledger_ui");
unlink("bin/$hledger_web");
unlink($zip_file);

chmod("bin/$hledger", 0755);
