<?php
require 'settings.php';

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

$request = json_decode($_POST["q"]);
if ($debugging) {
  echo "request:<br />";
  var_dump($request);
  echo "<br /><br />letters[0]:<br />";
  var_dump($request->letters[0]);
  echo "<br /><br />letters[0]->tokens:<br />";
  var_dump($request->letters[0]->tokens);
  echo "<br /><br />";
  //exit("done");
}

function escape_and_replace($placeholder, $replacement, $template) {
  $latex_chars = array('/\\\\/', '/&/', '/%/', '/\\$/', '/#/', '/_/', '/{/', '/}/', '/~/', '/\\^/');
  $latex_replacements = array('\\\\textbackslash ', '\\\\& ', '\\\\% ', '\\\\$ ', '\\\\# ', '\\\\_ ', '\\\\\{ ', '\\\\\} ', '\\\\textasciitilde ', '\\\\textasciicircum ');
  $escaped_replacement = preg_replace($latex_chars, $latex_replacements, $replacement);
  $count = 0;
  $res = preg_replace("/" . preg_quote($placeholder) . "\b/", $escaped_replacement, $template, -1, $count);
  //echo "replaced '$placeholder' $count times<br />";
  return $res;
}

function process_token($token, $template) {
  if ($token->isEnabled) {
    if ($token->isOptional) {
      $placeholder = "%opt-" . $token->key;
      $template = escape_and_replace($placeholder, "", $template);
    }
    $template = escape_and_replace("token-" . $token->key, $token->value, $template);
  }
  else { // !isEnabled
    if ($token->isOptional) {
      $placeholder = "%nopt-" . $token->key;
      $template = escape_and_replace($placeholder, "", $template);
    }
  }
  return $template;
}

$static_tokens = array();
$dynamic_tokens = array();

foreach($request->tokens as $token) {
  if ($token->isStatic) {
    $static_tokens[] = $token;
  }
  else {
    $dynamic_tokens[$token->groupID][] = $token;
  }
}

foreach($static_tokens as $token) {
  $template = process_token($token, $template);
}

$recipient_placeholder = "%add-recipient-here";
$recipient_macro = "\serialletter{token-toname}{token-tostreet}{token-tozip}{token-tocity}";

foreach($dynamic_tokens as $recipient) {
  // don't escape here!
  $template = preg_replace("/" . preg_quote($recipient_placeholder) . "\b/", $recipient_macro . "\n" . $recipient_placeholder, $template);
  foreach($recipient as $token) {
    $template = process_token($token, $template);
  }
}

$filebase = "letter";
$srcfile = $filebase . ".tex";
$handle = fopen($srcfile, "w") or die("failed to open srcfile");
fwrite($handle, $template);
fclose($handle);
$outfile = $filebase . ".pdf";
exec("$compiler $srcfile", $output, $ret);
if ($ret != 0 || $debugging) {
  foreach ($output as $i => $line) {
    echo "$line<br />";
  }
}

if ($ret) {
  exit("An error occurred ($ret). No file was created.");
}

if ($debugging) {
  exit("debugging. exit. Please remember to delete temporary files manually");
}

if (file_exists($outfile)) {
  //echo "writing file";
  header('Content-Type: application/pdf');
  header('Content-Disposition: inline; filename='.basename($outfile));
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
