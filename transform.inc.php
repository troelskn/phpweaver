<?php
interface Transformer extends Scanner {
  function getOutput();
}

/** Just a dummy really */
class PassthruTransformer implements Transformer {
  protected $output = "";
  function accept(Token $token) {
    $this->output .= $token->getText();
  }
  function getOutput() {
    return $this->output;
  }
}

interface BufferEditor {
  function editBuffer(TokenBuffer $buffer);
}

class PassthruBufferEditor implements BufferEditor {
  function editBuffer(TokenBuffer $buffer) {}
}

class DocCommentEditorTransformer implements Transformer {
  protected $function_body_scanner;
  protected $modifiers_scanner;
  protected $parameters_scanner;
  protected $editor;
  protected $state = 0;
  protected $buffer;
  function __construct(FunctionBodyScanner $function_body_scanner, ModifiersScanner $modifiers_scanner, FunctionParametersScanner $parameters_scanner, BufferEditor $editor) {
    $this->function_body_scanner = $function_body_scanner;
    $this->modifiers_scanner = $modifiers_scanner;
    $this->parameters_scanner = $parameters_scanner;
    $this->editor = $editor;
    $this->buffer = new TokenBuffer();
  }
  function accept(Token $token) {
    if ($token->isA(T_DOC_COMMENT)) {
      $this->state = 1;
      $this->raiseBuffer();
    } elseif ($this->state === 0 && ($this->modifiers_scanner->isActive() || $token->isA(T_FUNCTION))) {
      $this->state = 1;
      $this->raiseBuffer();
    } elseif ($this->state > 0 && $this->function_body_scanner->isActive()) {
      $this->editor->editBuffer($this->buffer);
      $this->state = 0;
      $this->flushBuffers();
    } elseif ($token->isA(T_INTERFACE) || $token->isA(T_CLASS) || ($token->isA(T_VARIABLE) && !$this->parameters_scanner->isActive())) {
      $this->state = 0;
      $this->flushBuffers();
    }
    $this->buffer->append($token);
  }
  function raiseBuffer() {
    $this->flushBuffers();
    $this->buffer = $this->buffer->raise();
  }
  function flushBuffers() {
    while ($this->buffer->hasSuper()) {
      $this->buffer = $this->buffer->flush();
    }
  }
  function getOutput() {
    $this->flushBuffers();
    return $this->buffer->toText();
  }
}

/** Uses result from a trace to construct docblocks */
class TracerDocBlockEditor implements BufferEditor {
  protected $signatures;
  protected $class_scanner;
  protected $function_body_scanner;
  function __construct(Signatures $signatures, ClassScanner $class_scanner, FunctionBodyScanner $function_body_scanner) {
    $this->signatures = $signatures;
    $this->class_scanner = $class_scanner;
    $this->function_body_scanner = $function_body_scanner;
  }
  function getCommentFor($func, $class = "") {
    if ($this->signatures->has($func, $class)) {
      $signature = $this->signatures->get($func, $class);
      $doc = "/**\n";
      foreach ($signature->getArguments() as $argument) {
        $doc .= '    * @param ' . $argument->getType() . "\n";
      }
      $doc .= '    * @return ' . $signature->getReturnType() . "\n";
      $doc .= "    *" . "/";
      return $doc;
    }
  }
  function editBuffer(TokenBuffer $buffer) {
    $text = $this->getCommentFor($this->function_body_scanner->getName(), $this->class_scanner->getCurrentClass());
    if (!$text) {
      return;
    }
    if (!$buffer->getFirstToken()->isA(T_DOC_COMMENT)) {
      $buffer->prepend(new Token("\n  ", -1, $buffer->getFirstToken()->getDepth()));
      $buffer->prepend(new Token('/** */', T_DOC_COMMENT, $buffer->getFirstToken()->getDepth()));
    }
    $current = $buffer->getFirstToken();
    $new_token = new Token($text, $current->getToken(), $current->getDepth());
    $buffer->replaceToken($current, $new_token);
  }
}
