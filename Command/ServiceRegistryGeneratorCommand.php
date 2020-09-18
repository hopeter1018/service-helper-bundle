<?php

/*
 * <hokwaichi@gmail.com>
 */

declare(strict_types=1);

namespace HoPeter1018\ServiceHelperBundle\Command;

use RuntimeException;
use Stringy\Stringy;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
// use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Templating\EngineInterface;

class ServiceRegistryGeneratorCommand extends ContainerAwareCommand
{
    const NAMESPACE = [
      '-- Let me type in --',
      // '-- EMPTY --',
      'Command',
      'Console',
      'Frontend',
      'Services',
    ];

    /** @var string */
    protected $bundle;
    /** @var string */
    protected $bundleDirectory;

    /** @var string */
    protected $providerNamespace;
    /** @var string */
    protected $providerNamespaceSnaked;

    /** @var string */
    protected $providerName;
    /** @var string */
    protected $providerNameSnaked;

    /** @var string */
    protected $fqcnPrefix;
    /** @var string */
    protected $classShortNamePrefix;

    /** @var EngineInterface */
    protected $engine;

    /** @var FileLocator */
    protected $fileLocator;

    public function __construct(EngineInterface $engine, FileLocator $fileLocator)
    {
        $this->engine = $engine;
        $this->fileLocator = $fileLocator;

        parent::__construct();
    }

    protected function configure()
    {
        $this
          ->setName('hopeter1018:service-helper:generate-registry')
          ->setDescription('Generate Service Registry')
          ->setHelp('This command is to Generate Service Registry')
          ->addArgument('name', InputArgument::REQUIRED, 'What name?')
          ->addOption('bundle', 'b', InputOption::VALUE_OPTIONAL, 'Which bundle?', null)
          ->addOption('namespace', null, InputOption::VALUE_OPTIONAL, 'Which namespace?', null)
          ->addOption('dry-run', 'd', InputOption::VALUE_OPTIONAL, 'Write file?', false)
      ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('[hopeter1018] Generate Service Registry');

        $this->dryRun = false !== $input->getOption('dry-run');

        $this->initQuestions($io, $input, $output);
        $this->generate($io, $input, $output);
        $this->registerFiles($io, $input, $output);

        // TODO register services.xml
        // TODO register pass
        $io->text('Done');
    }

    protected function registerFiles(SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        $linebreak = "\n";
        if (file_exists($targetFile = $this->bundleDirectory.$this->bundle.'.php')) {
            $content = file_get_contents($targetFile);
            $replaces = [];
            if (!strstr($content, 'public function build(ContainerBuilder $container)')) {
                $replaces[$linebreak.'}'] = $linebreak.'public function build(ContainerBuilder $container){}'.$linebreak.$linebreak.'}';
                $replaces[';'.$linebreak.$linebreak.'use '] = ';'.$linebreak.$linebreak.'use Symfony\Component\DependencyInjection\ContainerBuilder;'.$linebreak.'use ';
            }
            if (!strstr($content, 'use '.$this->bundleNamespace.'\\DependencyInjection\\'.$this->providerNamespace.'\\'.$this->classShortNamePrefix.'Pass;')) {
                $replaces[$linebreak.$linebreak.'use '] = $linebreak.$linebreak
                .'use '.$this->bundleNamespace.'\\DependencyInjection\\'.$this->providerNamespace.'\\'.$this->classShortNamePrefix.'Pass;'.$linebreak
                .'use '.$this->bundleNamespace.'\\Services\\'.$this->providerNamespace.'\\'.$this->classShortNamePrefix.'\\'.$this->classShortNamePrefix.'Interface;'.$linebreak
                .'use ';
                $replaces['public function build(ContainerBuilder $container)'.$linebreak.'    {'] = 'public function build(ContainerBuilder $container){$container->addCompilerPass(new '.$this->classShortNamePrefix.'Pass());'
                  ."\$container->registerForAutoconfiguration({$this->classShortNamePrefix}Interface::class)->addTag('{$this->providerNameBundleSuffix}.{$this->providerNamespaceSnaked}.{$this->providerNameSnaked}pool');";
            }
            !$this->dryRun and file_put_contents($targetFile, str_replace(array_keys($replaces), array_values($replaces), $content));
            $io->text('- Modified '.$targetFile);

            $cmd = 'php-cs-fixer --allow-risky=no --config='.escapeshellarg($this->getContainer()->getParameter('kernel.project_dir').'/.php_cs').' fix '.escapeshellarg($targetFile);
            exec($cmd);
        }

        if (file_exists($targetFile = $this->bundleDirectory.'DependencyInjection\\'.str_replace('Bundle', 'Extension', $this->bundle).'.php')) {
            $content = file_get_contents($targetFile);
            $replaces = [];
            if (!strstr($content, '$loader->load(\'services/'.$this->providerNamespaceSnaked.'.'.substr($this->providerNameSnaked, 0, -1).'.yaml\');')) {
                $replaces['$loader->load(\'services.yaml\');'] = '$loader->load(\'services.yaml\');$loader->load(\'services/'.$this->providerNamespaceSnaked.'.'.substr($this->providerNameSnaked, 0, -1).'.yaml\');';
            } elseif (!strstr($content, '$loader->load(\'services/'.$this->providerNamespaceSnaked.'.'.substr($this->providerNameSnaked, 0, -1).'.yml\');')) {
                $replaces['$loader->load(\'services.yml\');'] = '$loader->load(\'services.yml\');$loader->load(\'services/'.$this->providerNamespaceSnaked.'.'.substr($this->providerNameSnaked, 0, -1).'.yml\');';
            } elseif (!strstr($content, '$loader->load(\'services/'.$this->providerNamespaceSnaked.'.'.substr($this->providerNameSnaked, 0, -1).'.xml\');')) {
                $replaces['$loader->load(\'services.xml\');'] = '$loader->load(\'services.xml\');$loader->load(\'services/'.$this->providerNamespaceSnaked.'.'.substr($this->providerNameSnaked, 0, -1).'.xml\');';
            }

            !$this->dryRun and file_put_contents($targetFile, str_replace(array_keys($replaces), array_values($replaces), $content));
            $io->text('- Modified '.$targetFile);

            $cmd = 'php-cs-fixer --allow-risky=no --config='.escapeshellarg($this->getContainer()->getParameter('kernel.project_dir').'/.php_cs').' fix '.escapeshellarg($targetFile);
            exec($cmd);
        } else {
            $io->warning('- Not Found '.$targetFile);
        }
    }

    protected function generate(SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        $twig = $this->engine;
        $context = [
            'bundle_namespace' => $this->bundleNamespace,
            'name' => $this->providerName,
            'namespace' => $this->providerNamespace,

            'service_id_bundle_suffix' => $this->providerNameBundleSuffix,
            'service_id_prefix' => $this->providerNameSnaked,
            'service_id_namespace_prefix' => $this->providerNamespaceSnaked,
        ];

        // Generate the service provider
        $dir = $this->bundleDirectory."Services/{$this->providerNamespace}/{$this->providerName}/";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $registryContent = $twig->render('HoPeter1018ServiceHelperBundle:Command:ServiceRegistry/ServiceRegistry.php.twig', $context);
        !$this->dryRun and file_put_contents($dir.$this->providerName.'Registry.php', $registryContent);
        $io->text('- Generated to '.$dir.$this->providerName.'Registry.php');
        $interfaceContent = $twig->render('HoPeter1018ServiceHelperBundle:Command:ServiceRegistry/ServiceInterface.php.twig', $context);
        !$this->dryRun and file_put_contents($dir.$this->providerName.'Interface.php', $interfaceContent);
        $io->text('- Generated to '.$dir.$this->providerName.'Interface.php');

        // Generate the dependency injection pass
        $dir = $this->bundleDirectory.'DependencyInjection/'.$this->providerNamespace.'/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $passContent = $twig->render('HoPeter1018ServiceHelperBundle:Command:ServiceRegistry/ServicePass.php.twig', $context);
        !$this->dryRun and file_put_contents($dir.$this->providerName.'Pass.php', $passContent);
        $io->text('- Generated to '.$dir.$this->providerName.'Pass.php');

        // Generate new service xml
        $dir = $this->bundleDirectory.'Resources/config/services/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $xmlContent = $twig->render('HoPeter1018ServiceHelperBundle:Command:ServiceRegistry/services.xml.twig', $context);
        !$this->dryRun and file_put_contents($dir.$this->providerNamespaceSnaked.'.'.substr($this->providerNameSnaked, 0, -1).'.xml', $xmlContent);
        $io->text('- Generated to '.$dir.$this->providerNamespaceSnaked.'.'.substr($this->providerNameSnaked, 0, -1).'.xml');
    }

    protected function initQuestions(SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        // Ask for Bundle
        $bundles = array_keys($this->getContainer()->getParameter('kernel.bundles'));
        $bundle = $input->getOption('bundle');
        if (null !== $bundle and !in_array($bundle, $bundles)) {
            $bundle = null;
        }
        if (null === $bundle) {
            if (1 !== count($bundles)) {
                $question = new ChoiceQuestion('Which bundle? ', $bundles);
                $question->setErrorMessage('Bundle %s is invalid.');
                $bundle = $helper->ask($input, $output, $question);
            } else {
                $bundle = $bundles[0];
            }
        }
        $this->bundle = $bundle;
        $this->bundleDirectory = $this->getContainer()->get('kernel')->locateResource('@'.$this->bundle);
        $bundleClass = $this->getContainer()->get('kernel')->getBundles()[$bundle];
        $this->bundleNamespace = str_replace('\\'.$this->bundle, '', get_class($bundleClass));
        $io->text('Bundle:    '.$this->bundle);
        $io->text('Directory: '.$this->bundleDirectory);

        $providerNamespace = $input->getOption('namespace');
        if (null !== $providerNamespace and !in_array($providerNamespace, static::NAMESPACE)) {
            $providerNamespace = null;
        }
        if (null === $providerNamespace) {
            $question = new ChoiceQuestion('Which namespace? ', static::NAMESPACE, 1);
            $question->setErrorMessage('namespace %s is invalid.');
            $providerNamespace = $helper->ask($input, $output, $question);
            if ('-- Let me type in --' === $providerNamespace) {
                $question = new Question('namespace (Sample: PascalCasedName, * no ""): ');
                $question->setValidator(function ($answer) {
                    if (null === $answer) {
                        throw new RuntimeException('Please provide the Name.');
                    } elseif (0 === preg_match('@^([A-Z][a-z]+){1,}$@', $answer)) {
                        throw new RuntimeException('Name must be atleast one word in PascalCase.');
                    }

                    return $answer;
                });
                $providerNamespace = $helper->ask($input, $output, $question);
                // } elseif ('-- EMPTY --' === $providerNamespace) {
            //     $providerNamespace;
            }
        }
        $this->providerNamespace = $providerNamespace;
        $this->providerNamespaceSnaked = (new Stringy($this->providerNamespace))->underscored();

        $providerName = $input->getArgument('name');
        if (null === $providerName) {
            $question = new Question('Name (Sample: PascalCasedName, * no ""): ');
            $command = $this;
            $question->setValidator(function ($answer) use ($command) {
                if (null === $answer) {
                    throw new RuntimeException('Please provide the Name.');
                } elseif (0 === preg_match('@^([A-Z][a-z]+){1,}$@', $answer)) {
                    throw new RuntimeException('Name must be atleast one word in PascalCase.');
                } else {
                    $dir = $command->bundleDirectory."Services/{$answer}/".$command->providerNamespace.'/';
                    if (file_exists($dir.$answer.'Registry.php')) {
                        throw new RuntimeException('already exists! (Name: '.$answer.')');
                    }
                }

                return $answer;
            });
            $providerName = $helper->ask($input, $output, $question);
        }
        $this->providerName = $providerName;
        $this->providerNameSnaked = (new Stringy($this->providerName))->underscored().'_';
        $this->providerNameBundleSuffix = (new Stringy($this->bundle))->underscored();

        $this->fqcnPrefix = $this->bundle.'\Services\\'.$this->providerNamespace.'\\'.$this->providerName.'';
        $this->classShortNamePrefix = $this->providerName.'';
    }
}
