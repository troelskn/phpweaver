<?php namespace PHPTracerWeaver\Command;

use PHPTracerWeaver\Reflector\StaticReflector;
use PHPTracerWeaver\Scanner\ClassScanner;
use PHPTracerWeaver\Scanner\FunctionBodyScanner;
use PHPTracerWeaver\Scanner\FunctionParametersScanner;
use PHPTracerWeaver\Scanner\ModifiersScanner;
use PHPTracerWeaver\Scanner\NamespaceScanner;
use PHPTracerWeaver\Scanner\ScannerMultiplexer;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPTracerWeaver\Signature\Signatures;
use PHPTracerWeaver\Transform\DocCommentEditorTransformer;
use PHPTracerWeaver\Transform\TracerDocBlockEditor;
use PHPTracerWeaver\Xtrace\FunctionTracer;
use PHPTracerWeaver\Xtrace\TraceReader;
use PHPTracerWeaver\Xtrace\TraceSignatureLogger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use SplFileObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WeaveCommand extends Command
{
    const RETURN_CODE_OK    = 0;
    const RETURN_CODE_ERROR = 1;

    /**
     * Set up command parameteres and help message.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('weave')
            ->setDescription('Analyze trace and generate phpDoc in target files')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to folder or files to be process')
            ->addArgument('tracefile', InputArgument::OPTIONAL, 'Trace file to analyze', 'dumpfile.xt')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Update files inplace instead of printing the result')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command will analyze function signatures and update there phpDoc:

    <info>%command.full_name% src/</info>

By default the resulting code will be printed to the terminal, to update the orginal file you can specify <comment>--overwrite</comment>:

    <info>%command.full_name% src/ --overwrite</info>

By default it will look for the tracefile in the current directory, but you can also specify a path:

    <info>%command.full_name% src/ tests/tracefile.xt</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $traceFilename = $input->getArgument('tracefile');
        $pathToWeave = $input->getArgument('path');
        $overwrite = $input->getOption('overwrite');

        $this->output = new SymfonyStyle($input, $output);


        $filesToWave = $this->getFilesToProcess($pathToWeave);

        $sigs = $this->parseTrace($traceFilename);
        $this->transformFiles($filesToWave, $sigs, $overwrite);

        return self::RETURN_CODE_OK;
    }

    private function getFilesToProcess(string $pathToWeave): array
    {
        $filesToWave = [];

        if (is_dir($pathToWeave)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pathToWeave));
            $fileIterator = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
            foreach ($fileIterator as $file) {
                $filesToWave[] = $file[0];
            }

            return $filesToWave;
        } elseif (!is_file($pathToWeave)) {
            throw new Exception('Path (' . $pathToWeave . ') isn\'t readable');
        }

        $filesToWave[] = $pathToWeave;

        return $filesToWave;
    }

    private function parseTrace(string $traceFilename): Signatures
    {
        $reflector = new StaticReflector();
        $sigs = new Signatures($reflector);
        if (is_file($traceFilename)) {
            $traceFile = new SplFileObject($traceFilename);
            $collector = new TraceSignatureLogger($sigs, $reflector);
            $handler = new FunctionTracer($collector, $reflector);
            $trace = new TraceReader($handler);

            $traceFile->setFlags(SplFileObject::READ_AHEAD);
            $this->progressBarStart(iterator_count($traceFile));
            foreach ($traceFile as $lineNo => $line) {
                $trace->processLine($lineNo, $line);
                $this->progressBarAdvance();
            }

            $handler->closeVoidReturns(0);
            $this->progressBarEnd();
        }
        return $sigs;
    }

    private function transformFiles(array $filesToWeave, Signatures $sigs, bool $overwrite): void
    {
        $this->progressBarStart(count($filesToWeave));
        foreach ($filesToWeave as $fileToWeave) {
            $this->setupFileProcesser($sigs);
            $tokenStream = $this->tokenizer->scan(file_get_contents($fileToWeave));
            $tokenStream->iterate($this->scanner);
            $this->progressBarAdvance();

            if ($overwrite) {
                file_put_contents($fileToWeave, $this->transformer->getOutput());
                continue;
            }

            echo $this->transformer->getOutput();
        }
        $this->progressBarEnd();
    }

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

        $this->tokenizer = new TokenStreamParser();
    }

    private function progressBarStart(int $steps)
    {
        if (!$steps) {
            return;
        }

        $this->progressBar = $this->output->createProgressBar();
        $this->progressBar->setBarWidth(50);

        $this->progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $this->progressBar->start($steps);
    }

    private function progressBarAdvance()
    {
        if (!$this->progressBar) {
            return;
        }

        $this->progressBar->advance();
    }

    private function progressBarEnd()
    {
        if (!$this->progressBar) {
            return;
        }

        $this->progressBar->finish();
        $this->progressBar = null;
        $this->output->text('');
    }
}
