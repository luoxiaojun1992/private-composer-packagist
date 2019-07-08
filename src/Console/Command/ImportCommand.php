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
        $packages = $this->parseLockFile($input->getArgument('lock'));

        $satisFile = new JsonFile($input->getArgument('file'));
        $satisConfig = $satisFile->read();

        $satisConfig['require'] = $this->mergeRequirements($satisConfig['require'], $packages);

        $satisFile->write($satisConfig);

        return 0;
    }

    private function parseLockFile($filePath)
    {
        $lockFile = new JsonFile($filePath);
        $lockConfig = $lockFile->read();
        return array_merge($lockConfig['packages'], $lockConfig['packages-dev']);
    }

    private function mergeRequirements($requirement, $packages)
    {
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

        return $requirement;
    }
}
