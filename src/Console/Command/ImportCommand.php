<?php

declare(strict_types=1);

/*
 * This file is part of composer/satis.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Satis\Console\Command;

use Composer\Command\BaseCommand;
use Composer\Json\JsonFile;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('import')
            ->setDescription('Add repository URL to satis JSON file')
            ->setDefinition([
                new InputArgument('lock', InputArgument::REQUIRED, 'Lock file to use'),
                new InputArgument('file', InputArgument::OPTIONAL, 'JSON file to use', './satis.json'),
            ])
            ->setHelp(<<<'EOT'
The <info>import</info> command import given packages to the json file
(satis.json is used by default). You will need to run <comment>build</comment> command to
fetch updates from repository.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lockFile = $this->parseLockFile($input->getArgument('lock'));
        $packages = $this->parseLockPackages($lockFile);

        $satisFile = $this->parseSatisFile($input->getArgument('file'));
        $satisConfig = $this->parseSatisConfig($satisFile);

        $satisConfig = $this->addSatisRequire($satisConfig, $packages);

        $this->writeSatisConfig($satisFile, $satisConfig);

        return 0;
    }

    private function parseLockFile($filePath)
    {
        return $this->parseJsonFile($filePath);
    }

    private function parseLockPackages($lockFile)
    {
        $lockConfig = $lockFile->read();
        return array_merge($lockConfig['packages'], $lockConfig['packages-dev']);
    }

    private function parseSatisFile($filePath)
    {
        return $this->parseJsonFile($filePath);
    }

    private function parseSatisConfig($satisFile)
    {
        return $satisFile->read();
    }

    private function parseJsonFile($filePath)
    {
        return new JsonFile($filePath);
    }

    private function addSatisRequire($satisConfig, $packages)
    {
        $requirement = $satisConfig['require'];
        foreach ($packages as $package) {
            $packageName = strtolower($package['name']);

            if (array_key_exists($packageName, $requirement)) {
                $oldVersion = explode('|', $requirement[$packageName]);
                $newVersion = array_values(array_unique(array_merge($oldVersion, [$package['version']])));
                $requirement[$packageName] = implode('|', $newVersion);
            } else {
                $requirement[$packageName] = $package['version'];
            }
        }

        $satisConfig['require'] = $requirement;
        return $satisConfig;
    }

    private function writeSatisConfig($satisFile, $satisConfig)
    {
        $satisFile->write($satisConfig);
    }
}
