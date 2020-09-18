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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Templating\EngineInterface;

class ServiceRegistryAddServiceCommand extends ContainerAwareCommand
{
    /** @var string */
    protected $bundle;
    /** @var string */
    protected $bundleDirectory;
    /** @var string */
    protected $providerName;

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
          ->setName('hopeter1018:service-helper:registry-add')
          ->setDescription('Add Service to Service Registry')
          ->setHelp('This command is to Add Service to Service Registry')
      ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('[360img.net] Add Service to Service Registry');

        $this->initQuestions($io, $input, $output);
        $this->generate($io, $input, $output);
        $this->registerFiles($io, $input, $output);

        $io->text('Done');
    }

    protected function registerFiles(SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        return;

        // May not required

        $linebreak = "\n";
        $target = $this->bundleDirectory.'Resources/config/services.xml';
        if ('EasternColor'.$this->registryBundleNamespace.'Bundle' === $this->bundle) {
            $target = $this->bundleDirectory.'Resources/config/services/'.$this->registryNamespaceSnaked.'.'.substr($this->registryNameSnaked, 0, -1).'.xml';
        }
        if (file_exists($target)) {
            $content = file_get_contents($target);
            $replaces = [];
            $twig = $this->engine;
            $fqcn = 'EasternColor\\'.str_replace(['EasternColor', 'Bundle'], ['', ''], $this->bundle).'Bundle\Services\\'.$this->registryNamespace.'\\'.$this->registryName.'\\'.$this->providerName.$this->registryName.'';
            if (!strstr($content, $fqcn)) {
                $context = [
                    'service_id_bundle_suffix' => (new Stringy(str_replace(['EasternColor'], [''], $this->bundle)))->underscored(),
                    'service_id_prefix' => $this->registryNameSnaked,
                    'service_id_namespace_prefix' => $this->registryNamespaceSnaked,
                    'new_service_name' => (new Stringy($this->providerName))->underscored(),
                    'new_service_fqcn' => $fqcn,
                ];
                $registryContent = $twig->render('HoPeter1018ServiceHelperBundle:Command:ServiceRegistry/service_block.xml.twig', $context);
                $replaces['@([^\r\n]+)</services>@'] = $registryContent.$linebreak.'$1</services>';
            }
            file_put_contents($target, preg_replace(array_keys($replaces), array_values($replaces), $content));
        }
    }

    protected function generate(SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        $twig = $this->engine;
        $dir = $this->bundleDirectory."Services/{$this->registryNamespace}/{$this->registryName}/";
        if (!file_exists($dir.$this->providerName.$this->registryName.'.php')) {
            $context = [
                'bundle_namespace' => $this->bundleNamespace,
                'name' => $this->providerName,
                'namespace' => $this->registryNamespace,

                'registry_bundle_namespace' => $this->registryBundleNamespace,
                'registry_name' => $this->registryName,
                'registry_namespace' => $this->registryNamespace,
            ];

            // Generate the service provider
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $providerContent = $twig->render('HoPeter1018ServiceHelperBundle:Command:ServiceRegistry/Service.php.twig', $context);
            file_put_contents($dir.$this->providerName.$this->registryName.'.php', $providerContent);
        }
    }

    protected function initQuestions(SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $providerNamespaces = ['Command', 'Console', 'Frontend', 'Services'];

        // $serviceIdPrefix = (new Stringy($this->bundle))->underscored().'.'.$this->providerNamespaceSnaked;
        $base = $this->getContainer()->getParameter('kernel.project_dir').'/src/EasternColor/';
        $finder = (new Finder())->in($base)->path('/('.implode('|', $providerNamespaces).')[\/\\\\].*/')->name('*Registry.php');
        $providers = [];
        /* @var $fileInfo SplFileInfo */
        foreach ($finder as $fileInfo) {
            $providers[] = dirname(str_replace($base.'\\', 'EasternColor\\', $fileInfo->getPathname()));
        }

        $question = new ChoiceQuestion('Which Registry? ', $providers);
        $question->setErrorMessage('Please select registry');
        $this->registry = $helper->ask($input, $output, $question);
        list($x, $this->registryBundleNamespace, $y, $this->registryNamespace, $this->registryName) = explode('\\', $this->registry);
        $this->registryName = str_replace('', '', $this->registryName);
        $this->registryBundleNamespace = str_replace('Bundle', '', $this->registryBundleNamespace);
        $this->registryNamespaceSnaked = (new Stringy($this->registryNamespace))->underscored();
        $this->registryNameSnaked = (new Stringy($this->registryName))->underscored().'_';
        $io->text('Registry: '.$this->registry);

        // Ask for Bundle
        $ecBundles = array_keys(array_filter($this->getContainer()->getParameter('kernel.bundles'), function ($val) { return strstr($val, 'EasternColor'); }));
        $question = new ChoiceQuestion('Which bundle? ', $ecBundles);
        $question->setErrorMessage('Bundle %s is invalid.');
        $this->bundle = $helper->ask($input, $output, $question);
        $this->bundleDirectory = $this->getContainer()->get('kernel')->locateResource('@'.$this->bundle);
        $bundleClass = $this->getContainer()->get('kernel')->getBundles()[$bundle];
        $this->bundleNamespace = str_replace('\\'.$this->bundle, '', get_class($bundleClass));
        $io->text('Bundle:    '.$this->bundle);
        $io->text('Directory: '.$this->bundleDirectory);

        $question = new Question('Name (Sample: PascalCasedName, * no ""): ');
        $command = $this;
        $question->setValidator(function ($answer) use ($command) {
            if (null === $answer) {
                throw new RuntimeException('Please provide the Name.');
            } elseif (0 === preg_match('@^([A-Z][a-z]+){2,}$@', $answer)) {
                throw new RuntimeException('Name must be atleast two words in PascalCase.');
            } else {
                $dir = $command->bundleDirectory."Services/{$command->registryNamespace}/{$command->registryName}/";
                // dump($dir.$answer.$command->registryName.'.php');
                if (file_exists($dir.$answer.$command->registryName.'.php')) {
                    throw new RuntimeException('already exists! (Name: '.$answer.')');
                }
            }

            return $answer;
        });
        $this->providerName = $helper->ask($input, $output, $question);
    }
}
