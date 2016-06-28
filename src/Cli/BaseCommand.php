<?php

declare (strict_types = 1);

namespace Mihaeu\PhpDependencies\Cli;

use Mihaeu\PhpDependencies\Analyser;
use Mihaeu\PhpDependencies\DependencyCollection;
use Mihaeu\PhpDependencies\Parser;
use Mihaeu\PhpDependencies\PhpFileCollection;
use Mihaeu\PhpDependencies\PhpFileFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

abstract class BaseCommand extends Command
{
    /** @var PhpFileFinder */
    protected $phpFileFinder;

    /** @var Parser */
    protected $parser;

    /** @var Analyser */
    protected $analyser;

    /** @var string */
    protected $defaultFormat;

    /** @var string[] */
    protected $allowedFormats;

    /**
     * @param string        $name
     * @param PhpFileFinder $phpFileFinder
     * @param Parser        $parser
     * @param Analyser      $analyser
     */
    public function __construct(
        string $name,
        PhpFileFinder $phpFileFinder,
        Parser $parser,
        Analyser $analyser
    ) {
        parent::__construct($name);

        $this->phpFileFinder = $phpFileFinder;
        $this->parser = $parser;
        $this->analyser = $analyser;
    }

    protected function configure()
    {
        $this
            ->addArgument(
                'source',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Location of your PHP source files.'
            )
            ->addOption(
                'internals',
                null,
                InputOption::VALUE_NONE,
                'Check for dependencies from internal PHP Classes like SplFileInfo.'
            )
            ->addOption(
                'only-namespaces',
                null,
                InputOption::VALUE_NONE,
                'Output dependencies as packages instead of single classes.'
            )
        ;
    }

    /**
     * @param string $destination
     *
     * @throws \Exception
     */
    protected function ensureOutputFormatIsValid(string $destination)
    {
        if (!in_array(preg_replace('/.+\.(\w+)$/', '$1', $destination), $this->allowedFormats, true)) {
            throw new \InvalidArgumentException('Output format is not allowed ('.implode(', ', $this->allowedFormats).')');
        }
    }

    /**
     * @param string[] $sources
     *
     * @throws \Exception
     */
    protected function ensureSourcesAreReadable(array $sources)
    {
        foreach ($sources as $source) {
            if (!is_readable($source)) {
                throw new \InvalidArgumentException('File/Directory does not exist or is not readable.');
            }
        }
    }

    /**
     * @param string $destination
     *
     * @throws \Exception
     */
    protected function ensureDestinationIsWritable(string $destination)
    {
        if (!is_writable(dirname($destination))) {
            throw new \InvalidArgumentException('Destination is not writable.');
        }
    }

    /**
     * @param $sources
     * @param bool $withInternals
     * @param bool $onlyNamespaces
     *
     * @return DependencyCollection
     */
    protected function detectDependencies(array $sources, bool $withInternals = false, bool $onlyNamespaces = false) : DependencyCollection
    {
        $files = new PhpFileCollection();
        foreach ($sources as $source) {
            $files = $files->addAll($this->phpFileFinder->find(new \SplFileInfo($source)));
        }
        $ast = $this->parser->parse($files);

        $dependencies = $withInternals
            ? $this->analyser->analyse($ast)
            : $this->analyser->analyse($ast)->removeInternals();

        return $onlyNamespaces
            ? $dependencies->onlyNamespaces()
            : $dependencies;
    }
}