<?php
/** a single token in the source code of a file */
class Token
{
    protected $text;
    protected $token;
    protected $depth;

    public function __construct($text, $token, $depth)
    {
        $this->text = $text;
        $this->token = $token;
        $this->depth = $depth;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getDepth()
    {
        return $this->depth;
    }

    public function isA($type)
    {
        return $this->getToken() === $type;
    }

    public function isCurlyOpen()
    {
        $token = $this->getToken();

        return T_CURLY_OPEN === $token || T_DOLLAR_OPEN_CURLY_BRACES === $token || '{' === $this->getText();
    }
}

/** a collection of tokens */
class TokenStream
{
    protected $tokens = [];

    public function getHash()
    {
        return md5(serialize($this->tokens));
    }

    public function append(Token $token)
    {
        $this->tokens[] = $token;
    }

    public function iterate(Scanner $scanner)
    {
        foreach ($this->tokens as $token) {
            $scanner->accept($token);
        }
    }
}

/** parses a string -> tokenstream */
class TokenStreamParser
{
    public function scan($source)
    {
        //todo: track indentation
        $stream = new TokenStream();
        $depth = 0;
        foreach (token_get_all($source, true) as $token) {
            if (is_array($token)) {
                [$token, $text] = $token;
            } elseif (is_string($token)) {
                $text = $token;
                $token = -1;
            }
            if (T_CURLY_OPEN === $token || T_DOLLAR_OPEN_CURLY_BRACES === $token || '{' === $text) {
                ++$depth;
            } elseif ('}' == $text) {
                --$depth;
            }
            $stream->append(new Token($text, $token, $depth));
        }

        return $stream;
    }
}

/** Used by transformers */
class TokenBuffer
{
    protected $super;
    protected $tokens = [];

    public function __construct(self $super = null)
    {
        $this->super = $super;
    }

    public function prepend(Token $token)
    {
        array_unshift($this->tokens, $token);
    }

    public function append(Token $token)
    {
        $this->tokens[] = $token;
    }

    public function getFirstToken()
    {
        return isset($this->tokens[0]) ? $this->tokens[0] : null;
    }

    public function replaceToken(Token $token, Token $new_token)
    {
        $tmp = [];
        foreach ($this->tokens as $t) {
            if ($t === $token) {
                $tmp[] = $new_token;
            } else {
                $tmp[] = $t;
            }
        }
        $this->tokens = $tmp;
    }

    public function hasSuper()
    {
        return (bool) $this->super;
    }

    public function raise()
    {
        return new self($this);
    }

    public function flush()
    {
        if (!$this->super) {
            return $this;
        }
        $tokens = $this->tokens;
        $this->tokens = [];
        foreach ($tokens as $token) {
            $this->super->append($token);
        }

        return $this->super;
    }

    public function toText()
    {
        $out = '';
        foreach ($this->tokens as $token) {
            $out .= $token->getText();
        }

        return $out;
    }
}

/** provides access to a file */
interface FileAccess
{
    public function getContents();

    public function getPathname();
}

/** default implementation for FileAccess */
class FilePath implements FileAccess
{
    protected $pathname;

    public function __construct($pathname)
    {
        $this->pathname = $pathname;
    }

    public function getContents()
    {
        if (!is_file($this->getPathname())) {
            throw new Exception('Not a file or not readable');
        }

        return get_file_contents($this->getPathname());
    }

    public function getPathname()
    {
        return realpath($this->pathname);
    }
}

/** entity representing a file with sourcecode */
class SourceFile
{
    protected $token_stream;
    protected $hash;
    protected $path;

    public function __construct(FileAccess $path, TokenStream $token_stream)
    {
        $this->path = $path;
        $this->token_stream = $token_stream;
        $this->hash = $token_stream->getHash();
    }

    public function getPath()
    {
        return $this->path;
    }

    public function hasChanges()
    {
        return $this->hash != $this->token_stream->getHash();
    }

    public function getTokenStream()
    {
        return $this->token_stream;
    }

    public function setTokenStream(TokenStream $token_stream)
    {
        $this->token_stream = $token_stream;
    }
}

/** a repository + gateway for SourceFile's */
class SourceFileRepository
{
    protected $streams = [];
    protected $parser;

    public function __construct(TokenStreamParser $parser)
    {
        $this->parser = $parser;
    }

    public function get(FileAccess $path)
    {
        if (!isset($this->streams[$path->getPathname()])) {
            $this->streams[$path->getPathname()] = $this->load($path);
        }
        // todo: assert not changed on disk
        return $this->streams[$path->getPathname()];
    }

    protected function load(FileAccess $path)
    {
        return new SourceFile($path, $this->parser->scan($path->getContents(), $path->getPathname()));
    }
}

/** a statemachine for scanning a tokenstream  */
interface Scanner
{
    public function accept(Token $token);
}

/** used for sending output to multiple scanners at once */
class ScannerMultiplexer implements Scanner
{
    protected $scanners = [];

    public function appendScanner(Scanner $scanner)
    {
        $this->scanners[] = $scanner;

        return $scanner;
    }

    public function accept(Token $token)
    {
        foreach ($this->scanners as $scanner) {
            $scanner->accept($token);
        }
    }
}

/** Tracks the current class scope */
class ClassScanner implements Scanner
{
    protected $current_class_scope = 0;
    protected $current_class;
    protected $state = 0;
    protected $on_class_begin;
    protected $on_class_end;
    protected $on_classname;

    public function notifyOnClassBegin($callback)
    {
        $this->on_class_begin = $callback;
    }

    public function notifyOnClassEnd($callback)
    {
        $this->on_class_end = $callback;
    }

    public function notifyOnClassName($callback)
    {
        $this->on_classname = $callback;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_INTERFACE) || $token->isA(T_CLASS)) {
            $this->state = 1;
            if (is_callable($this->on_class_begin)) {
                call_user_func($this->on_class_begin);
            }
        } elseif ($token->isA(T_STRING) && 1 === $this->state) {
            $this->current_class = $token->getText();
            $this->current_class_scope = $token->getDepth();
            $this->state = 2;
            if (is_callable($this->on_classname)) {
                call_user_func($this->on_classname);
            }
        } elseif (2 === $this->state && $token->getDepth() > $this->current_class_scope) {
            $this->state = 3;
        } elseif (3 === $this->state && $token->getDepth() === $this->current_class_scope) {
            $this->current_class = null;
            $this->state = 0;
            if (is_callable($this->on_class_end)) {
                call_user_func($this->on_class_end);
            }
        }
    }

    public function getCurrentClass()
    {
        return $this->current_class;
    }
}

/** Scans for class inheritance */
class ClassExtendsScanner implements Scanner
{
    protected $on_extends;
    protected $on_implements;
    protected $state = 0;
    protected $class_scanner;

    public function __construct(ClassScanner $class_scanner)
    {
        $this->class_scanner = $class_scanner;
    }

    public function notifyOnExtends($callback)
    {
        $this->on_extends = $callback;
    }

    public function notifyOnImplements($callback)
    {
        $this->on_implements = $callback;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_EXTENDS)) {
            $this->state = 1;
        } elseif ($token->isA(T_IMPLEMENTS)) {
            $this->state = 2;
        } elseif (1 === $this->state && $token->isA(T_STRING)) {
            if (is_callable($this->on_extends)) {
                call_user_func($this->on_extends, $this->class_scanner->getCurrentClass(), $token->getText());
            }
        } elseif (2 === $this->state && $token->isA(T_STRING)) {
            if (is_callable($this->on_implements)) {
                call_user_func($this->on_implements, $this->class_scanner->getCurrentClass(), $token->getText());
            }
        } elseif ($token->isCurlyOpen()) {
            $this->state = 0;
        }
    }
}

/** Tracks possible preludes for functions */
class ModifiersScanner implements Scanner
{
    protected $on_modifiers_begin;
    protected $on_modifiers_end;
    protected $was_function = false;
    protected $state = 0;

    public function notifyOnModifiersBegin($callback)
    {
        $this->on_modifiers_begin = $callback;
    }

    public function notifyOnModifiersEnd($callback)
    {
        $this->on_modifiers_end = $callback;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_PRIVATE) || $token->isA(T_PROTECTED) || $token->isA(T_PUBLIC) || $token->isA(T_FINAL) || $token->isA(T_STATIC) || $token->isA(T_ABSTRACT)) {
            $this->state = 1;
            if (is_callable($this->on_modifiers_begin)) {
                call_user_func($this->on_modifiers_begin);
            }
        } elseif ($token->isA(T_INTERFACE) || $token->isA(T_CLASS) || $token->isA(T_FUNCTION) || $token->isA(T_VARIABLE)) {
            $this->was_function = $token->isA(T_FUNCTION);
            $this->state = 0;
            if (is_callable($this->on_modifiers_end)) {
                call_user_func($this->on_modifiers_end);
            }
        }
    }

    public function isActive()
    {
        return 1 === $this->state;
    }

    public function wasFunction()
    {
        return $this->was_function;
    }
}

/** Scans for function name + body */
class FunctionBodyScanner implements Scanner
{
    protected $name;
    protected $state = 0;

    public function accept(Token $token)
    {
        if ($token->isA(T_FUNCTION)) {
            $this->current_class_scope = $token->getDepth();
            $this->state = 1;
        } elseif (1 === $this->state && $token->isA(T_STRING)) {
            $this->name = $token->getText();
            $this->state = 2;
        } elseif (2 === $this->state && $token->getDepth() > $this->current_class_scope) {
            $this->state = 3;
        } elseif (3 === $this->state && $token->getDepth() === $this->current_class_scope) {
            $this->state = 0;
        }
    }

    public function isActive()
    {
        return $this->state > 2;
    }

    public function getName()
    {
        return $this->name;
    }
}

/** Scans for, collects and parses function signatures */
class FunctionParametersScanner implements Scanner
{
    protected $signature = [];
    protected $paren_count = 0;
    protected $state = 0;
    protected $on_signature_begin;
    protected $on_signature_end;

    public function notifyOnSignatureBegin($callback)
    {
        $this->on_signature_begin = $callback;
    }

    public function notifyOnSignatureEnd($callback)
    {
        $this->on_signature_end = $callback;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_FUNCTION)) {
            $this->state = 1;
        } elseif (1 === $this->state && '(' === $token->getText()) {
            $this->signature = [];
            $this->signature[] = [$token->getText(), $token->getToken()];
            $this->paren_count = 1;
            $this->state = 2;
            if (is_callable($this->on_signature_begin)) {
                call_user_func($this->on_signature_begin);
            }
        } elseif (2 === $this->state) {
            $this->signature[] = [$token->getText(), $token->getToken()];
            if ('(' === $token->getText()) {
                ++$this->paren_count;
            } elseif (')' === $token->getText()) {
                --$this->paren_count;
            }
            if (0 === $this->paren_count) {
                $this->state = 0;
                if (is_callable($this->on_signature_end)) {
                    call_user_func($this->on_signature_end);
                }
            }
        }
    }

    public function isActive()
    {
        return 0 !== $this->state;
    }

    public function getCurrentSignature()
    {
        return $this->signature;
    }

    public function getCurrentSignatureAsString()
    {
        $txt = '';
        foreach ($this->signature as $struct) {
            $txt .= $struct[0];
        }

        return $txt;
    }

    public function getCurrentSignatureAsTypeMap()
    {
        $current = null;
        $map = [];
        foreach ($this->signature as $tuple) {
            [$text, $token] = $tuple;
            if (T_VARIABLE === $token) {
                $map[$text] = $current ? $current : '???';
            } elseif (',' === $text) {
                $current = null;
            } elseif (T_STRING === $token) {
                $current = $text;
            }
        }

        return $map;
    }
}

/** Scans for doc-comments */
class DocBlockScanner implements Scanner
{
    protected $last_doc_block;
    protected $state = 0;
    protected $parameters_scanner;

    public function __construct(FunctionParametersScanner $parameters_scanner)
    {
        $this->parameters_scanner = $parameters_scanner;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_DOC_COMMENT)) {
            $this->last_doc_block = $token->getText();
            $this->state = 1;
        } elseif ($token->isA(T_INTERFACE) || $token->isA(T_CLASS) || $token->isA(T_FUNCTION) || ($token->isA(T_VARIABLE) && !$this->parameters_scanner->isActive())) {
            if (1 === $this->state) {
                $this->state = 2;
            } else {
                $this->last_doc_block = null;
                $this->state = 0;
            }
        }
    }

    public function isActive()
    {
        return 1 === $this->state;
    }

    public function getCurrentDocBlock()
    {
        return $this->last_doc_block;
    }
}
