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

// Check header
$header = $file[0];
$header = explode('#', $header);
if (trim($header[0]) != '.IPPcode23') {
  echo "Header missing or incorrect";
  exit(21);
}

// Pop it from array for easier work with it
array_shift($file);

// Remove comments from rest of the lines
foreach ($file as $key => $line) {
  $trimmedLine = explode('#', $line);
  $file[$key] = trim($trimmedLine[0]);
}

// Regex to match variable syntax
function checkVar($input) {
  if (!preg_match('/(LF|TF|GF)@[a-zA-Z0-9_\-\$\&\%\*\!\?]+/', $input)) {
    // TODO: Check the correct exit number here.
    echo($input);
    exit(23);
  }
}

function checkLabel($input) {
  if (!preg_match('/[a-zA-Z0-9_\-\$\&\%\*\!\?]+/', $input)) {
    // TODO: Check the correct exit number here.
    echo($input);
    exit(23);
  }
}

$xml = new SimpleXMLElement("<program></program>");
$xml->addAttribute('language', 'IPPCode23');
 
function addInstruction($opcode, $position, $arg1 = '', $arg2 = '', $arg3 = '') {
  global $xml;
  $instruction = $xml->addChild('instruction');
  $instruction->addAttribute('order', $position);
  $instruction->addAttribute('opcode', $opcode);

  if ($arg1) $instruction->addAttribute('arg1', $arg1);
  if ($arg2) $instruction->addAttribute('arg2', $arg2);
  if ($arg3) $instruction->addAttribute('arg3', $arg3);
}

// Parse the lines
foreach ($file as $key=>$line) {
  $input = explode(' ', $line);

  switch(strtolower($input[0])) {
    case 'move':
      checkVar($input[1]);
      checkVar($input[2]);
      addInstruction('MOVE', $key++, $input[1], $input[2]);
      break;
    case 'createframe':
      addInstruction('CREATEFRAME', $key++);
      break;
    case 'pushframe':
      addInstruction('PUSHFRAME', $key++);
      break;
    case 'popframe':
      addInstruction('POPFRAME', $key++);
      break;
    case 'defvar':
      checkVar($input[1]);
      addInstruction('DEFVAR', $key++, $input[1]);
      break;
    case 'call':
      checkLabel($input[1]);
      addInstruction('CALL', $key++, $input[1]);
      break;
    case 'return':
      addInstruction('RETURN', $key++);
      break;
    case 'pushs':
      break;
    case 'pops':
      break;
    case 'add':
      break;
    case 'sub':
      break;
    case 'mul':
      break;
    case 'idiv':
      break;
    case 'lt':
    case 'gt':
    case 'eq':
      break;
    case 'and':
    case 'or':
    case 'not':
      break;
    case 'int2char':
      break;
    case 'stri2int':
      break;
    case 'read':
      break;
    case 'write':
      break;
    case 'concat':
      break;
    case 'strlen':
      break;
    case 'getchar':
      break;
    case 'setchar':
      break;
    case 'type':
      break;
    case 'label':
      break;
    case 'jump':
      break;
    case 'jumpifeq':
      break;
    case 'jumpifneq':
      break;
    case 'exit':
      break;
    case 'dprint':
      break;
    case 'break':
      break;
    default:
      echo 'Unknown instruction';
      exit(-1);
  }
}

$output = new DOMDocument("1.0");
$output->preserveWhiteSpace = false;
$output->formatOutput = true;
$output->loadXML($xml->asXML());
echo $output->saveXML();
?>