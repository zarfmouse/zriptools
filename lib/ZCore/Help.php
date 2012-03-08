<?php

namespace ZCore;
use Exception;

class Help {

  // TODO: Support multi-valued arguments. run-task doesn't support
  // them yet either.

  private $requiredArguments;
  private $optionalArguments;
  private $argumentDescriptions;
  private $argumentValidators;

  final public function validate(array $arguments) {
    foreach($this->requiredArguments as $req) {
      if(!array_key_exists($req, $arguments)) {
	throw new MissingRequiredArgumentException($req);
      }
    }
    foreach($arguments as $key => $val) {
      if(! (in_array($key, $this->requiredArguments) ||
	      in_array($key, $this->optionalArguments))) {
	throw new UnknownArgumentException($key);
      }
      $validator = $this->argumentValidators[$key];
      if(isset($validator) && !call_user_func($validator, $val)) {
	throw new InvalidArgumentException("$key => $val");
      }
    }
    return $arguments;
  }

  final public function addArgument($argument, $required = true, $description = null, $validator = null) {
    if($required) {
      $this->requiredArguments[] = $argument;
    } else {
      $this->optionalArguments[] = $argument;
    }

    if(isset($description)) {
      $this->argumentDescriptions[$argument] = $description;
    }

    if(isset($validator)) {
      $this->argumentValidators[$argument] = $validator;
    }
  }

}

class HelpException extends Exception {};
class MissingRequiredArgumentException extends HelpException {};
class UnknownArgumentException extends HelpException {};
class InvalidArgumentException extends HelpException {};


