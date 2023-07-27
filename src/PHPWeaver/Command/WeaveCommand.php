<?php namespace PHPWeaver\Command;

use Composer\XdebugHandler\XdebugHandler;
use PHPWeaver\Exceptions\Exception;
use PHPWeaver\Reflector\StaticReflector;
use PHPWeaver\Scanner\ClassScanner;
use PHPWeaver\Scanner\FunctionBodyScanner;
use PHPWeaver\Scanner\FunctionParametersScanner;
use PHPWeaver\Scanner\ModifiersScanner;
use PHPWeaver\Scanner\NamespaceScanner;
use PHPWeaver\Scanner\ScannerMultiplexer;
use PHPWeaver\Scanner\TokenStreamParser;
use PHPWeaver\Signature\Signatures;
use PHPWeaver\Transform\DocCommentEditorTransformer;
use PHPWeaver\Transform\TracerDocBlockEditor;
use PHPWeaver\Xtrace\FunctionTracer;
use PHPWeaver\Xtrace\TraceReader;
use PHPWeaver\Xtrace\TraceSignatureLogger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use SplFileObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WeaveCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_ERROR = 1;
    const REFRESH_RATE_INTERVAL = 0.033333334; // 30hz

    /** @var int */
    private $nextSteps = 0;
    /** @var float */
    private $nextUpdate = 0.0;
    /** @var ?SymfonyStyle */
    private $output;
    /** @var ?ProgressBar */
    private $progressBar;
    /** @var ?TokenStreamParser */
    private $tokenizer;
    /** @var ?ScannerMultiplexer */
    private $scanner;
    /** @var ?DocCommentEditorTransformer */
    private $transformer;

    /**
     * Set up command parameteres and help message.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('weave')
            ->setDescription('Analyze trace and generate phpDoc in target files')
            ->addArgument(
                'path',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Path to folder or files to be process'
            )
            ->addOption('tracefile', null, InputOption::VALUE_OPTIONAL, 'Trace file to analyze', 'dumpfile')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command will analyze function signatures and update there phpDoc:

    <info>%command.full_name% src/</info>

By default it will look for the tracefile in the current directory, but you can also specify a path (.xt is automattically appended):

    <info>%command.full_name% src/ --tracefile tests/tracefile</info>

You can specify multiple paths to process, this way the trace file will only have to be processed once:

    <info>%command.full_name% app/ public/index.php tests/</info>
EOT
        );
    }

    /**
     * Run the weave process.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Restart if xdebug is loaded, unless the environment variable PHPWEAVER_ALLOW_XDEBUG is set.
        $xdebug = new XdebugHandler('phpweaver');
        $xdebug->check();
        unset($xdebug);

        $pathsToWeave = $input->getArgument('path');
        if (!is_array($pathsToWeave))
            return self::RETURN_CODE_ERROR;
        $tracefile = $input->getOption('tracefile');
        if (!is_string($tracefile))
            return self::RETURN_CODE_ERROR;
        $tracefile .= '.xt';

        $this->output = new SymfonyStyle($input, $output);

        $filesToWeave = $this->getFilesToProcess($pathsToWeave);

        $sigs = $this->parseTrace($tracefile);
        $this->transformFiles($filesToWeave, $sigs);

        return self::RETURN_CODE_OK;
    }

    /**
     * Fetch array of file names to process.
     *
     * @param string[] $pathsToWeave
     *
     * @return array<int, string>
     */
    private function getFilesToProcess(array $pathsToWeave): array
    {
        $filesToWeave = [];

        foreach ($pathsToWeave as $pathToWeave) {
            if (is_dir($pathToWeave)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pathToWeave));
                $fileIterator = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
                foreach ($fileIterator as $file) {
                    $filesToWeave[] = $file[0];
                }

                continue;
            }

            if (!is_file($pathToWeave)) {
                throw new Exception('Path (' . $pathToWeave . ') isn\'t readable');
            }

            $filesToWeave[] = $pathToWeave;
        }

        return $filesToWeave;
    }

    /**
     * Parse the trace file.
     *
     * @param string $tracefile
     *
     * @return Signatures
     */
    private function parseTrace(string $tracefile): Signatures
    {
        $sigs = new Signatures();
        if (is_file($tracefile)) {
            $traceFile = new SplFileObject($tracefile);
            $trace = new TraceReader(new FunctionTracer(new TraceSignatureLogger($sigs)));

            $traceFile->setFlags(SplFileObject::READ_AHEAD);
            $this->progressBarStart(iterator_count($traceFile), '<info>Parsing tracefile …</info>');
            foreach ($traceFile as $line) {
                if (!is_string($line)) {
                    throw new Exception('Unable to read trace file');
                }
                $trace->processLine($line);
                $this->progressBarAdvance();
            }

            $this->progressBarEnd();
        }

        return $sigs;
    }

    /**
     * Process files and insert phpDoc.
     *
     * @refactor Avoid need to check if scanner and trasformer where created
     *
     * @param string[]   $filesToWeave
     * @param Signatures $sigs
     *
     * @return void
     */
    private function transformFiles(array $filesToWeave, Signatures $sigs): void
    {
        $this->progressBarStart(count($filesToWeave), '<info>Updating source files …</info>');

        foreach ($filesToWeave as $fileToWeave) {
            $this->setupFileProcesser($sigs);
            if (null === $this->scanner || null === $this->transformer) {
                throw new Exception('Failed to initialize scanner');
            }
            $this->tokenizer = new TokenStreamParser();
            $fileContent = file_get_contents($fileToWeave);
            if (false === $fileContent) {
                throw new Exception('Unable to read source file: ' . $fileToWeave);
            }
            $tokenStream = $this->tokenizer->scan($fileContent);
            $tokenStream->iterate($this->scanner);

            file_put_contents($fileToWeave, $this->transformer->getOutput());

            $this->progressBarAdvance();
        }

        $this->progressBarEnd();
    }

    /**
     * Initialize the php parser.
     *
     * @param Signatures $sigs
     *
     * @return void
     */
    private function setupFileProcesser(Signatures $sigs): void
    {
        $this->scanner = new ScannerMultiplexer();
        $parametersScanner = new FunctionParametersScanner();
        $functionBodyScanner = new FunctionBodyScanner();
        $modifiersScanner = new ModifiersScanner();
        $classScanner = new ClassScanner();
        $namespaceScanner = new NamespaceScanner();
        $editor = new TracerDocBlockEditor(
            $sigs,
            $classScanner,
            $functionBodyScanner,
            $parametersScanner,
            $namespaceScanner
        );

        $this->transformer = new DocCommentEditorTransformer(
            $functionBodyScanner,
            $modifiersScanner,
            $parametersScanner,
            $editor
        );

        $this->scanner->appendScanners([
            $parametersScanner,
            $functionBodyScanner,
            $modifiersScanner,
            $classScanner,
            $namespaceScanner,
            $this->transformer,
        ]);
    }

    /**
     * Start a progressbar on the ouput.
     *
     * @refactor Avoid need to check if output has been created
     *
     * @param int    $steps
     * @param string $message
     *
     * @return void
     */
    private function progressBarStart(int $steps, string $message): void
    {
        if (!$steps) {
            return;
        }

        if (null === $this->output) {
            throw new Exception('Output not set');
        }

        $this->progressBar = $this->output->createProgressBar();
        $this->progressBar->setBarWidth(50);

        $this->progressBar->setMessage($message);
        $this->progressBar->setFormat("%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%\n%message%");
        $this->progressBar->start($steps);

        $this->nextSteps = 0;
        $this->nextUpdate = microtime(true) + self::REFRESH_RATE_INTERVAL;
    }

    /**
     * Advance the progress bare by steps.
     *
     * Rate limited to avoid performance issues.
     *
     * @param int $steps
     *
     * @return void
     */
    private function progressBarAdvance(int $steps = 1): void
    {
        if (!$this->progressBar) {
            return;
        }

        $this->nextSteps += $steps;

        if (microtime(true) <= $this->nextUpdate) {
            return;
        }

        $this->progressBar->advance($this->nextSteps);
        $this->nextSteps = 0;
        $this->nextUpdate = microtime(true) + self::REFRESH_RATE_INTERVAL;
    }

    /**
     * Set the progress to 100% and clear it from the output.
     *
     * @return void
     */
    private function progressBarEnd(): void
    {
        if (!$this->progressBar) {
            return;
        }

        $this->progressBar->finish();
        $this->progressBar->clear();
        $this->progressBar = null;
    }
}
