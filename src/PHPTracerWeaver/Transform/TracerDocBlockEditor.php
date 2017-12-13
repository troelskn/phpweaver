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
    protected $classScanner;
    /** @var FunctionBodyScanner */
    protected $functionBodyScanner;
    /** @var FunctionParametersScanner */
    protected $parametersScanner;

    /**
     * @param Signatures                $signatures
     * @param ClassScanner              $classScanner
     * @param FunctionBodyScanner       $functionBodyScanner
     * @param FunctionParametersScanner $parametersScanner
     */
    public function __construct(
        Signatures $signatures,
        ClassScanner $classScanner,
        FunctionBodyScanner $functionBodyScanner,
        FunctionParametersScanner $parametersScanner
    ) {
        $this->signatures = $signatures;
        $this->classScanner = $classScanner;
        $this->functionBodyScanner = $functionBodyScanner;
        $this->parametersScanner = $parametersScanner;
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

            $doc = "\n"; // TODO do not add an empty line if at the top of the class
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
            $this->functionBodyScanner->getName(),
            $this->classScanner->getCurrentClass(),
            $this->parametersScanner->getCurrentSignatureAsTypeMap()
        );
        if (!$text) {
            return;
        }

        if (!$buffer->getFirstToken()->isA(T_DOC_COMMENT)) {
            $buffer->prepend(new Token("\n    ", -1, $buffer->getFirstToken()->getDepth()));
            $buffer->prepend(new Token("\n    /**\n     */", T_DOC_COMMENT, $buffer->getFirstToken()->getDepth()));
        }

        $current = $buffer->getFirstToken();
        $newToken = new Token($text, $current->getToken(), $current->getDepth());
        $buffer->replaceToken($current, $newToken);
    }
}
