<?php
$template_dir = 'template';
if (!is_dir($template_dir)) {
  exit("templatedir " . $template_dir . " not found");
}

// create tmp dir
$dir = sys_get_temp_dir() . "/" . "webletter-" . mt_rand();
mkdir($dir) or exit("failed to make tmpdir");

// copy all files to tmpdir
if ($handle = opendir($template_dir)) {
  while (false !== ($entry = readdir($handle))) {
    if ($entry != "." && $entry != "..") {
      copy($template_dir . "/" . $entry, $dir . "/" . $entry );
    }
  }
  closedir($handle);
} else {
  exit("failed to copy template dir");
}

// switch to tmpdir
chdir($dir) or exit("failed to chdir");
//
$template_file = 'template.tex';
if (!file_exists($template_file)) {
  exit($template_file . " not found");
}
$template = file_get_contents($template_file);
if ($template == FALSE)
  exit("couldn't read " . $template_file);
foreach($_GET as $placeholder=>$replacement) {
//  if ($placeholder == "" || $replacement == "")
//    continue;
  $replacement = preg_replace("/\\\\/", "", $replacement);
  echo "replace '$placeholder' with '$replacement'<br />";
  $template = preg_replace("/". preg_quote("token-" . $placeholder) . "/", preg_quote($replacement), $template);
}
echo "$template";
$filebase = "letter";
$srcfile = $filebase . ".tex";
$handle = fopen($srcfile, "w") or die("failed to open srcfile");
fwrite($handle, $template);
fclose($handle);
$outfile = $filebase . ".pdf";
exec("pdflatex $srcfile", $output, $ret);
if ($ret != 0) {
  foreach ($output as $i => $line) {
    echo "$line<br />";
  }
}
unlink($srcfile);
if (file_exists($outfile)) {
  //echo "writing file";
  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename='.basename($outfile));
  ob_clean();
  flush();
  readfile($outfile);
  unlink($outfile);
  exit;
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
?>
