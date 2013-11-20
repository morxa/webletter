<?php
require 'settings.php';
$latex_chars = array('/\\\\/', '/&/', '/%/', '/\\$/', '/#/', '/_/', '/{/', '/}/', '/~/', '/\\^/');

$latex_replacements = array('\\\\textbackslash', '\\\\&', '\\\\%', '\\\\$', '\\\\#', '\\\\_', '\\\\\{', '\\\\\}', '\\\\textasciitilde', '\\\\textasciicircum');
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
foreach($_POST as $placeholder=>$replacement) {
  // remove all white spaces and new lines from the right side of the placeholder
  // to prevent multiple-line placeholders (potential security issue)
  $placeholder = rtrim($placeholder);
  // only allow placeholders starting with $tokenstring or $optstring or $noptstring
  $tokenstring = "token-";
  $optstring = "%opt-token-";
  $noptstring = "%nopt-token-";
  if (substr_compare($placeholder, $tokenstring, 0, strlen($tokenstring)) != 0
      && substr_compare($placeholder, $optstring, 0, strlen($optstring)) != 0
      && substr_compare($placeholder, $noptstring, 0, strlen($optstring)) != 0 ) {
    continue;
  }
  // escape all latex characters
  $replacement = preg_replace($latex_chars, $latex_replacements, $replacement);
  echo "replace '$placeholder' with '$replacement'<br />";
  $template = preg_replace("/". preg_quote($placeholder) . "/", $replacement, $template);
}
$filebase = "letter";
$srcfile = $filebase . ".tex";
$handle = fopen($srcfile, "w") or die("failed to open srcfile");
fwrite($handle, $template);
fclose($handle);
$outfile = $filebase . ".pdf";
exec("$compiler $srcfile", $output, $ret);
if ($ret != 0) {
  foreach ($output as $i => $line) {
    echo "$line<br />";
  }
}
if (file_exists($outfile)) {
  //echo "writing file";
  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename='.basename($outfile));
  ob_clean();
  flush();
  readfile($outfile);
}

// remove all files from tmpdir
// then remove tmpdir
if ($handle = opendir($dir)) {
  while (false !== ($entry = readdir($handle))) {
    if ($entry != "." && $entry != "..") {
      unlink("$dir/$entry") or exit("couldn't unlink $dir/$entry");
    }
  }
  closedir($handle);
  rmdir($dir) or exit("couldn't unlink $dir");
} else {
  exit("couldn't unlink $dir; $dir isn't readable");
}
?>
