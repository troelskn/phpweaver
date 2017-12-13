<?php namespace PHPTracerWeaver\Transform;

use PHPTracerWeaver\Scanner\ClassScanner;
use PHPTracerWeaver\Scanner\FunctionBodyScanner;
use PHPTracerWeaver\Scanner\FunctionParametersScanner;
use PHPTracerWeaver\Scanner\Token;
use PHPTracerWeaver\Scanner\TokenBuffer;
use PHPTracerWeaver\Signature\Signatures;

/** Uses result from a trace to construct docblocks */
class TracerDocBlockEditor implements BufferEditorInterface
{
    /** @var Signatures */
    protected $signatures;
    /** @var ClassScanner */
    protected $class_scanner;
    /** @var FunctionBodyScanner */
    protected $function_body_scanner;
    /** @var FunctionParametersScanner */
    protected $parameters_scanner;

    /**
     * @param Signatures                $signatures
     * @param ClassScanner              $class_scanner
     * @param FunctionBodyScanner       $function_body_scanner
     * @param FunctionParametersScanner $parameters_scanner
     */
    public function __construct(
        Signatures $signatures,
        ClassScanner $class_scanner,
        FunctionBodyScanner $function_body_scanner,
        FunctionParametersScanner $parameters_scanner
    ) {
        $this->signatures = $signatures;
        $this->class_scanner = $class_scanner;
        $this->function_body_scanner = $function_body_scanner;
        $this->parameters_scanner = $parameters_scanner;
    }

    public function generateDoc($func, string $class = '', array $params = []): string
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

    /**
     * @param TokenBuffer $buffer
     *
     * @return void
     */
    public function editBuffer(TokenBuffer $buffer): void
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
