<?php

use PHPTracerWeaver\Scanner\ClassScanner;
use PHPTracerWeaver\Scanner\FunctionBodyScanner;
use PHPTracerWeaver\Scanner\FunctionParametersScanner;
use PHPTracerWeaver\Scanner\ModifiersScanner;
use PHPTracerWeaver\Scanner\ScannerInterface;
use PHPTracerWeaver\Scanner\Token;
use PHPTracerWeaver\Scanner\TokenBuffer;

interface TransformerInterface extends ScannerInterface
{
    public function getOutput();
}

/** Just a dummy really */
class PassthruTransformer implements TransformerInterface
{
    protected $output = '';

    public function accept(Token $token)
    {
        $this->output .= $token->getText();
    }

    public function getOutput()
    {
        return $this->output;
    }
}

interface BufferEditor
{
    public function editBuffer(TokenBuffer $buffer);
}

class PassthruBufferEditor implements BufferEditor
{
    public function editBuffer(TokenBuffer $buffer)
    {
    }
}

class DocCommentEditorTransformer implements TransformerInterface
{
    protected $function_body_scanner;
    protected $modifiers_scanner;
    protected $parameters_scanner;
    protected $editor;
    protected $state = 0;
    protected $buffer;

    public function __construct(FunctionBodyScanner $function_body_scanner, ModifiersScanner $modifiers_scanner, FunctionParametersScanner $parameters_scanner, BufferEditor $editor)
    {
        $this->function_body_scanner = $function_body_scanner;
        $this->modifiers_scanner = $modifiers_scanner;
        $this->parameters_scanner = $parameters_scanner;
        $this->editor = $editor;
        $this->buffer = new TokenBuffer();
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_DOC_COMMENT)) {
            $this->state = 1;
            $this->raiseBuffer();
        } elseif (0 === $this->state && ($this->modifiers_scanner->isActive() || $token->isA(T_FUNCTION))) {
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

    public function raiseBuffer()
    {
        $this->flushBuffers();
        $this->buffer = $this->buffer->raise();
    }

    public function flushBuffers()
    {
        while ($this->buffer->hasSuper()) {
            $this->buffer = $this->buffer->flush();
        }
    }

    public function getOutput()
    {
        $this->flushBuffers();

        return $this->buffer->toText();
    }
}

/** Uses result from a trace to construct docblocks */
class TracerDocBlockEditor implements BufferEditor
{
    protected $signatures;
    protected $class_scanner;
    protected $function_body_scanner;
    protected $parameters_scanner;

    public function __construct(Signatures $signatures, ClassScanner $class_scanner, FunctionBodyScanner $function_body_scanner, FunctionParametersScanner $parameters_scanner)
    {
        $this->signatures = $signatures;
        $this->class_scanner = $class_scanner;
        $this->function_body_scanner = $function_body_scanner;
        $this->parameters_scanner = $parameters_scanner;
    }

    public function generateDoc($func, $class = '', $params = [])
    {
        if ($this->signatures->has($func, $class)) {
            $signature = $this->signatures->get($func, $class);

            $key = 0;
            $longestType = 0;
            $seenArguments = $signature->getArguments();
            foreach ($params as $name => $type) {
                $seenArgument = $seenArguments[$key];
                $seenArgument->collateWith($type);
                $longestType = max(mb_strlen($seenArgument->getType()), $longestType);
                $params[$name] = $seenArgument->getType();
                ++$key;
            }

            $doc = "\n";
            $doc .= "    /**\n";
            foreach ($params as $name => $type) {
                $doc .= '     * @param ' . $type . str_repeat(' ', $longestType - mb_strlen($type) + 1) . $name . "\n";
            }
            if ($params) {
                $doc .= "     *\n";
            }
            $doc .= '     * @return ' . $signature->getReturnType() . "\n";
            $doc .= '     */';

            return $doc;
        }
    }

    public function editBuffer(TokenBuffer $buffer)
    {
        $text = $this->generateDoc(
            $this->function_body_scanner->getName(),
            $this->class_scanner->getCurrentClass(),
            $this->parameters_scanner->getCurrentSignatureAsTypeMap()
        );
        if (!$text) {
            return;
        }

        if (!$buffer->getFirstToken()->isA(T_DOC_COMMENT)) {
            $buffer->prepend(new Token("\n    ", -1, $buffer->getFirstToken()->getDepth()));
            $buffer->prepend(new Token("\n    /**\n     */", T_DOC_COMMENT, $buffer->getFirstToken()->getDepth()));
        }

        $current = $buffer->getFirstToken();
        $new_token = new Token($text, $current->getToken(), $current->getDepth());
        $buffer->replaceToken($current, $new_token);
    }
}
