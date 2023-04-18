<?php
ini_set('display_errors', 'stderr');

// Check arguments
if (isset($argv[1])) {
  if ($argv[1] == '--help') {
    echo "Pouzitie: php parse.php <stdin >stdout <--help>";
    exit(0);
  } else {
    exit(10);
  }
}

// Get input from stdin
$file = file_get_contents("php://stdin");
if (!$file) {
  echo "\nSubor neexistuje alebo nemate dostatocne opravnenie!";
  exit(11);
}

$file = explode("\n", $file);

// Remove comments from rest of the lines
foreach ($file as $key => $line) {
  $trimmedLine = explode('#', $line);
  $file[$key] = trim($trimmedLine[0]);
}

// Remove empty lines
$file = array_filter($file);
$file = array_values($file);

// Check header
$header = $file[0];
$header = explode('#', $header);
if (strtolower(trim($header[0])) != '.ippcode23') {
  echo "Chybajuce alebo chybne zahlavie";
  exit(21);
}

// Pop it from array for easier work with it
array_shift($file);

// Regex to match variable syntax
function checkVar($input) {
  if (!preg_match('/^(LF|TF|GF)@[a-zA-Z_\-\$\&\%\*\!\?][a-zA-Z0-9_\-\$\&\%\*\!\?]*$/', $input)) {
    exit(23);
  }
  return ['var', $input];
}

function checkLabel($input) {
  if (!preg_match('/^[a-zA-Z_\-\$\&\%\*\!\?][a-zA-Z0-9_\-\$\&\%\*\!\?]*$/', $input)) {
    exit(23);
  }
  return ['label', $input];
}

function checkSymbol($input) {
  $const = explode('@', $input);

  // When either type or the value is not set
  if (!isset($const[0]) || !isset($const[1])) exit(23);

  switch($const[0]) {
    case 'GF':
    case 'LF':
    case 'TF':
      return checkVar($input);
      break;
    case 'int':
      if (!preg_match('/^[-+]?0x[\da-fA-F]+(?:_[\da-fA-F]+)*(?<!_)$/i', $const[1]) &&
          !preg_match('/^[-+]?(?:(?:0|0o)[0-7]+|(?:0|[1-9]\d*))(?:_\d+)*(?<!_)$/i', $const[1])) exit(23);
      return [$const[0], $const[1]];
      break;
    case 'bool':
      if (!preg_match('/(true|false)/', $const[1])) exit(23);
      return [$const[0], $const[1]];
      break;
    case 'string':
      if (!preg_match('/^(?:[^\\\\]|\\\\(?=\d{3}))*$/', $const[1])) exit(23);
      return [$const[0], $const[1]];
      break;
    case 'nil':
      if (strcmp($const[1], 'nil') != 0) exit(23);
      return [$const[0], $const[1]];
      break;
    default:
      exit(23);
  }
}

$xml = new SimpleXMLElement('<program></program>');
$xml->addAttribute('language', 'IPPcode23');
 
function addInstruction($opcode, $position, $arg1 = '', $arg1Type = '', $arg2 = '', $arg2Type = '', $arg3 = '', $arg3Type = '') {
  global $xml;
  $instruction = $xml->addChild('instruction');
  $instruction->addAttribute('order', $position);
  $instruction->addAttribute('opcode', $opcode);

  if (strlen($arg1) || strlen($arg1Type)) {
    $arg = $instruction->addChild('arg1', htmlspecialchars($arg1));
    $arg->addAttribute('type', $arg1Type);
  }
  if (strlen($arg2) || strlen($arg2Type)) {
    $arg = $instruction->addChild('arg2', htmlspecialchars($arg2));
    $arg->addAttribute('type', $arg2Type);
  } 
  if (strlen($arg3) || strlen($arg3Type)) {
    $arg = $instruction->addChild('arg3', htmlspecialchars($arg3));
    $arg->addAttribute('type', $arg3Type);
  } 
}

// Parse the lines
foreach ($file as $key=>$line) {
  $input = explode(' ', $line);
  $order = $key + 1;

  $input = array_filter($input);
  $input = array_values($input);

  // Ignore empty lines
  if (count($input) == 0) continue;

  switch(strtolower($input[0])) {
    case 'createframe':
    case 'pushframe':
    case 'popframe':
    case 'return':
    case 'break':
      if (count($input) != 1) exit(23);

      addInstruction(strtoupper($input[0]), $order);
      break;
    case 'defvar':
    case 'pops':
      if (count($input) != 2) exit(23);
      checkVar($input[1]);

      addInstruction(strtoupper($input[0]), $order, $input[1], 'var');
      break;
    case 'call':
      if (count($input) != 2) exit(23);
      checkLabel($input[1]);

      addInstruction('CALL', $order, $input[1], 'label');
      break;
    case 'pushs':
      if (count($input) != 2) exit(23);
      $symb = checkSymbol($input[1]);

      addInstruction('PUSHS', $order, $symb[1], $symb[0]);
      break;
    case 'move':
      if (count($input) != 3) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);

      addInstruction('MOVE', $order, $input[1], 'var', $arg2[1], $arg2[0]);
      break;
    case 'read':
      if (count($input) != 3) exit(23);
      checkVar($input[1]);
      if (!preg_match('/^(bool|string|int)$/', $input[2])) exit(23);

      addInstruction('READ', $order, $input[1], 'var', $input[2], 'type');
      break;
    case 'label':
    case 'jump':
      if (count($input) != 2) exit(23);
      checkLabel($input[1]);

      addInstruction(strtoupper($input[0]), $order, $input[1], 'label');
      break;
    case 'exit':
    case 'dprint':
    case 'write':
      if (count($input) != 2) exit(23);
      $arg1 = checkSymbol($input[1]);

      addInstruction(strtoupper($input[0]), $order, $arg1[1], $arg1[0]);
      break;
    case 'not':
    case 'int2char':
    case 'type':
    case 'strlen':
      if (count($input) != 3) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);

      addInstruction(strtoupper($input[0]), $order, $input[1], 'var', $arg2[1], $arg2[0]);
      break;
    case 'add':
    case 'sub':
    case 'mul':
    case 'idiv':
    case 'lt':
    case 'gt':
    case 'eq':
    case 'and':
    case 'or':
    case 'concat':
    case 'getchar':
    case 'setchar':
    case 'stri2int':
      if (count($input) != 4) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);
      $arg3 = checkSymbol($input[3]);

      addInstruction(strtoupper($input[0]), $order, $input[1], 'var', $arg2[1], $arg2[0], $arg3[1], $arg3[0]);
      break;
    case 'jumpifeq':
    case 'jumpifneq':
      if (count($input) != 4) exit(23);
      checkLabel($input[1]);
      $arg2 = checkSymbol($input[2]);
      $arg3 = checkSymbol($input[3]);

      addInstruction(strtoupper($input[0]), $order, $input[1], 'label', $arg2[1], $arg2[0], $arg3[1], $arg3[0]);
      break;
    default:
      echo 'Neznama alebo chybna instrukcia';
      exit(22);
  }
}

$output = new DOMDocument("1.0");
$output->preserveWhiteSpace = false;
$output->formatOutput = true;
$output->loadXML($xml->asXML());
$output->encoding = 'UTF-8';
echo $output->saveXML();
?>