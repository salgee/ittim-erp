<?php
declare (strict_types = 1);

namespace catcher\command\Tools;

use catcher\library\BackUpDatabase;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class BackupCommand extends Command
{
    protected $table;

    protected function configure()
    {
        // 指令配置
        $this->setName('backup:data')
            ->addArgument('tables', Argument::REQUIRED, 'backup tables')
            ->setDescription('backup data you need');
    }

    protected function execute(Input $input, Output $output)
    {
        $tables = $this->input->getArgument('tables');

        (new BackUpDatabase)->done($tables);

        $output->info('succeed!');
    }
}
