<?php

namespace Task;

use Mage\Task\AbstractTask;

class PrepareDemoDeployment extends AbstractTask
{
    public function getName()
    {
        return 'Preparing the deployment for the demo on the production environment';
    }

    public function run()
    {
        $commandList = array(
            'mv app/config/parameters.yml.dist_demo app/config/parameters.yml',
            'mv web/app.php.dist_demo web/app.php',
        );

        $command = implode(" && ", $commandList);

        $result = $this->runCommandRemote($command);

        return $result;
    }
}