<?php
ini_set('display_errors', 'stderr');
// TODO: Add help prompt
// TODO: Correct the exit codes based on assignment
// Check arguments
if (isset($argv[1])) {
  if ($argv[1] == '--help') {
    echo "Display help";
    exit(0);
  } else {
    echo "Incorrect argument format";
    exit(-1);
  }
}

// Get input from stdin
$file = file_get_contents("php://stdin");
if (!$file) {
  echo "\nThe file doesn't exist or you don't have correct permissions!";
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
if (trim($header[0]) != '.IPPcode23') {
  echo "Header missing or incorrect";
  exit(21);
}

// Pop it from array for easier work with it
array_shift($file);

// Regex to match variable syntax
function checkVar($input) {
  if (!preg_match('/^(LF|TF|GF)@[a-zA-Z_\-\$\&\%\*\!\?][a-zA-Z0-9_\-\$\&\%\*\!\?]*$/', $input)) {
    // TODO: Check the correct exit number here.
    exit(23);
  }
  return true;
}

function checkLabel($input) {
  if (!preg_match('/^[a-zA-Z_\-\$\&\%\*\!\?][a-zA-Z0-9_\-\$\&\%\*\!\?]*$/', $input)) {
    // TODO: Check the correct exit number here.
    exit(23);
  }
  return true;
}

function checkSymbol($input) {
  $const = explode('@', $input);

  // When either type or the value is not set
  if (!isset($const[0]) || !isset($const[1])) exit(23);

  switch($const[0]) {
    case 'GF':
    case 'LF':
    case 'TF':
      if (!preg_match('/^[a-zA-Z_\-\$\&\%\*\!\?][a-zA-Z0-9_\-\$\&\%\*\!\?]*$/', $const[1])) exit(23);
      return ['var', $input];
      break;
    case 'int':
      if (!is_numeric($const[1])) exit(23); 
      return [$const[0], $const[1]];
      break;
    case 'bool':
      if (!preg_match('/(true|false)/', $const[1])) exit(23);
      return [$const[0], $const[1]];
      break;
    case 'string':
      // TODO: What to check here?
      if (!preg_match('/^(?:[^\\\\]|\\\\(?=\d{3}))*$/', $const[1])) exit(23);
      return [$const[0], $const[1]];
      break;
    case 'nil':
      if (strcmp($const[1], 'nil') != 0) exit(23);
      return [$const[0], $const[1]];
      break;
    default:
      echo $const[0];
      echo 'unknown const';
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
    case 'move':
      if (count($input) != 3) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);
      addInstruction('MOVE', $order, $input[1], 'var', $arg2[1], $arg2[0]);
      break;
    case 'createframe':
    case 'pushframe':
    case 'popframe':
    case 'return':
    case 'break':
      if (count($input) != 1) exit(23);
      addInstruction(strtoupper($input[0]), $order);
      break;
    case 'defvar':
      if (count($input) != 2) exit(23);
      checkVar($input[1]);
      addInstruction('DEFVAR', $order, $input[1], 'var');
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
    case 'pops':
      if (count($input) != 2) exit(23);
      checkVar($input[1]);
      addInstruction('POPS', $order, $input[1], 'var');
      break;
    case 'add':
    case 'sub':
    case 'mul':
    case 'idiv':
      if (count($input) != 4) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);
      $arg3 = checkSymbol($input[3]);

      // If the numbers arent int but syntax is correct
      // if ($arg2[0] != 'int' || $arg3[0] != 'int') exit(23);
      addInstruction(strtoupper($input[0]), $order, $input[1], 'var', $arg2[1], $arg2[0], $arg3[1], $arg3[0]);
      break;
    case 'lt':
    case 'gt':
    case 'eq':
      if (count($input) != 4) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);
      $arg3 = checkSymbol($input[3]);

      addInstruction(strtoupper($input[0]), $order, $input[1], 'var', $arg2[1], $arg2[0], $arg3[1], $arg3[0]);
      break;
    case 'and':
    case 'or':
      if (count($input) != 4) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);
      $arg3 = checkSymbol($input[3]);

      addInstruction(strtoupper($input[0]), $order, $input[1], 'var', $arg2[1], $arg2[0], $arg3[1], $arg3[0]);
      break;
    case 'not':
      if (count($input) != 3) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);

      addInstruction(strtoupper($input[0]), $order, $input[1], 'var', $arg2[1], $arg2[0]);
      break;
    case 'int2char':
      if (count($input) != 3) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);

      addInstruction('INT2CHAR', $order, $input[1], 'var', $arg2[1], $arg2[0]);
      break;
    case 'stri2int':
      if (count($input) != 4) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);
      $arg3 = checkSymbol($input[3]);

      addInstruction('STRI2INT', $order, $input[1], 'var', $arg2[1], $arg2[0], $arg3[1], $arg3[0]);
      break;
    case 'read':
      if (count($input) != 3) exit(23);
      checkVar($input[1]);
      if (!preg_match('/^(bool|string|int)$/', $input[2])) exit(23);
      
      addInstruction('READ', $order, $input[1], 'var', $input[2], 'type');
      break;
    case 'type':
    case 'strlen':
      if (count($input) != 3) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);

      addInstruction(strtoupper($input[0]), $order, $input[1], 'var', $arg2[1], $arg2[0]);
      break;
    case 'concat':
    case 'getchar':
    case 'setchar':
      if (count($input) != 4) exit(23);
      checkVar($input[1]);
      $arg2 = checkSymbol($input[2]);
      $arg3 = checkSymbol($input[3]);

      addInstruction(strtoupper($input[0]), $order, $input[1], 'var', $arg2[1], $arg2[0], $arg3[1], $arg3[0]);
      break;
    case 'label':
    case 'jump':
      if (count($input) != 2) exit(23);
      checkLabel($input[1]);

      addInstruction(strtoupper($input[0]), $order, $input[1], 'label');
      break;
    case 'jumpifeq':
    case 'jumpifneq':
      if (count($input) != 4) exit(23);
      checkLabel($input[1]);
      $arg2 = checkSymbol($input[2]);
      $arg3 = checkSymbol($input[3]);

      // if($arg2[0] != $arg3[0]) exit(23);
      addInstruction(strtoupper($input[0]), $order, $input[1], 'label', $arg2[1], $arg2[0], $arg3[1], $arg3[0]);
      break;
    case 'exit':
    case 'dprint':
    case 'write':
      if (count($input) != 2) exit(23);
      $arg1 = checkSymbol($input[1]);

      addInstruction(strtoupper($input[0]), $order, $arg1[1], $arg1[0]);
      break;
    default:
      echo 'Unknown instruction';
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