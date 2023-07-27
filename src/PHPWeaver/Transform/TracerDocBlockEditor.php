<?php namespace PHPWeaver\Transform;

use PHPWeaver\Exceptions\Exception;
use PHPWeaver\Scanner\ClassScanner;
use PHPWeaver\Scanner\FunctionBodyScanner;
use PHPWeaver\Scanner\FunctionParametersScanner;
use PHPWeaver\Scanner\NamespaceScanner;
use PHPWeaver\Scanner\Token;
use PHPWeaver\Scanner\TokenBuffer;
use PHPWeaver\Signature\Signatures;

/** Uses result from a trace to construct docblocks */
class TracerDocBlockEditor implements BufferEditorInterface
{
    protected Signatures $signatures;
    protected ClassScanner $classScanner;
    protected FunctionBodyScanner $functionBodyScanner;
    protected FunctionParametersScanner $parametersScanner;
    protected NamespaceScanner $namespaceScanner;

    public function __construct(
        Signatures $signatures,
        ClassScanner $classScanner,
        FunctionBodyScanner $functionBodyScanner,
        FunctionParametersScanner $parametersScanner,
        NamespaceScanner $namespaceScanner
    ) {
        $this->signatures = $signatures;
        $this->classScanner = $classScanner;
        $this->functionBodyScanner = $functionBodyScanner;
        $this->parametersScanner = $parametersScanner;
        $this->namespaceScanner = $namespaceScanner;
    }

    /**
     * @param string[] $params
     */
    public function generateDoc(string $func, string $class = '', array $params = [], string $namespace = ''): ?string
    {
        if ((!$params && ('__construct' === $func || '__destruct' === $func))
            || !$this->signatures->has($func, $class, $namespace)
        ) {
            return null;
        }

        $signature = $this->signatures->get($func, $class, $namespace);

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

        $doc = "/**\n";
        foreach ($params as $name => $type) {
            $doc .= '     * @param ' . $type . str_repeat(' ', $longestType - mb_strlen($type) + 1) . $name . "\n";
        }
        if ('__construct' !== $func && '__destruct' !== $func) {
            if ($params) {
                $doc .= "     *\n";
            }
            $doc .= '     * @return ' . $signature->getReturnType() . "\n";
        }
        $doc .= '     */';

        return $doc;
    }

    public function editBuffer(TokenBuffer $buffer): void
    {
        $text = $this->generateDoc(
            $this->functionBodyScanner->getName(),
            $this->classScanner->getCurrentClass(),
            $this->parametersScanner->getCurrentSignatureAsTypeMap(),
            $this->namespaceScanner->getCurrentNamespace()
        );
        if (null === $text) {
            return;
        }

        $firstToken = $buffer->getFirstToken();
        if (null === $firstToken) {
            throw new Exception('Failed to find insert point for phpDoc');
        }

        if (!$firstToken->isA(T_DOC_COMMENT)) {
            $buffer->prepend(new Token("\n    ", -1, $firstToken->getDepth()));
            $buffer->prepend(new Token("\n    /**\n     */", T_DOC_COMMENT, $firstToken->getDepth()));
        }

        $current = $buffer->getFirstToken();
        assert($current instanceof Token);
        $newToken = new Token($text, $current->getToken(), $current->getDepth());
        $buffer->replaceToken($current, $newToken);
    }
}
