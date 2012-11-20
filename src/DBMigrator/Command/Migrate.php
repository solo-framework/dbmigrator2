<?php
/**
 * Выполнение миграции
 *
 * PHP version 5
 *
 * @package
 * @author  Eugene Kurbatov <ekur@i-loto.ru>
 */

namespace DBMigrator\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Utils\MigrationHelper;

class Migrate extends Base
{
	private $from = null;
	
	private $to = null;
	
	private $force = false;
	
	protected function configure()
	{
		$this->setName("migrate")
			->setDescription("Apply migraion.")			
			->setHelp(sprintf('%sApply migraion by name or index.%s', PHP_EOL, PHP_EOL))
			->setDefinition(array(
				new InputArgument("from:to", InputArgument::OPTIONAL, "Version or migration index range"),				
			    new InputOption("force", "f", InputOption::VALUE_NONE, "Migrate from backup")
            ));
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->prepareArguments($input);

		exit;
		
		$migrations = $this->getAllMigrations();

		$this->checkMigrations($migrations);
		$this->checkMigration($uid, $migrations);

		$this->dbHelper->makeDBEmpty();

		if ($force)
		{
			$this->dbHelper->importFiles("{$migrStorage}/{$uid}",
				array('scheme.sql', 'data.sql', 'procedures.sql', 'triggers.sql'));
		}
		else
		{
			foreach ($migrations as $m)
			{
				if ($m->id == 1)
				{
					$this->dbHelper->importFiles("{$migrStorage}/{$m->createTime}",
						array('scheme.sql', 'data.sql', 'procedures.sql', 'triggers.sql'));
				}
				else
				{
					$this->dbHelper->importFiles("{$migrStorage}/{$m->createTime}", array('delta.sql'));
				}

				if ($m->createTime == $uid)
				{
					break;
				}
			}
		}
		$this->restoreMigrations($migrations);
		self::setCurrentVersion($migrStorage, $uid);
		
	}

	private function prepareArguments($input)
	{
		$fromTo = $input->getArgument("from:to");		

		list($from, $to) = explode(":", $fromTo);
		$from = (empty($from)) ? null : $from;
		$to = (empty($to)) ? null : $to;

		if ($this->isIndex($from))			
			$from = MigrationHelper::getVersionByIndex($from);			

		if ($this->isIndex($to))
			$to = MigrationHelper::getVersionByIndex($to);

		if (!$this->isVersion($from))
			throw new \Exception("Invalid migration version 'from'");

		if (!$this->isVersion($to))
			throw new \Exception("Invalid migration version 'to'");

		$this->force = $input->getOption("force");
		$this->from = $from;
		$this->to = $to;				
	}

	private function isVersion($val)
	{		
		return preg_match("/^[0-9]+\.[0-9]+$/", $val) == 1;
	}

	private function isIndex($val)
	{
		return preg_match("/^[0-9]+$/", $val) == 1;
	}
}
