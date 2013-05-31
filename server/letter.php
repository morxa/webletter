<?php
$template_file = 'template.tex';
if (!file_exists($template_file)) {
  exit(1);
}
$template = file_get_contents($template_file);
if ($template == FALSE)
  exit("template not found");
foreach($_GET as $placeholder=>$replacement) {
//  if ($placeholder == "" || $replacement == "")
//    continue;
  echo "replace '$placeholder' with '$replacement'<br />";
  $template = preg_replace("/". preg_quote("token-" . $placeholder) . "/", preg_quote($replacement), $template);
}
echo "$template";
$dir = sys_get_temp_dir() . "/" . "webletter-" . mt_rand();
mkdir($dir) or exit("failed to make tmpdir");
chdir($dir) or exit("failed to chdir");
$filebase = "letter";
$srcfile = $filebase . ".tex";
$handle = fopen($srcfile, "w") or die("failed to open srcfile");
fwrite($handle, $template);
fclose($handle);
$outfile = $filebase . ".pdf";
exec("pdflatex $srcfile", $output);
foreach ($output as $i => $line) {
  echo "$line<br />";
}
unlink($srcfile);
if (file_exists($outfile)) {
  //echo "writing file";
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename='.basename($outfile));
  header('Content-Transfer-Encoding: binary');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize($outfile));
  ob_clean();
  flush();
  readfile($outfile);
  unlink($outfile);
  exit;
    $response=$outfile;
}
else {
  $response="FILE_NOT_EXISTENT";
}
$logfile = $filebase . ".log";
$auxfile = $filebase . ".aux";
if (file_exists($logfile)) {
  unlink($logfile) or exit("failed to remove log file");
}
if (file_exists($auxfile)) {
  unlink($auxfile) or exit("failed to remove aux file");
}
//rmdir($dir) or exit("failed to remove dir");
echo $dir . "/" . $response;
?>
