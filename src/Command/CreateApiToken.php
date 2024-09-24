<?php

namespace Pantono\Authentication\Command;

use Symfony\Component\Console\Command\Command;
use Pantono\Authentication\ApiAuthentication;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

class CreateApiToken extends Command
{
    private ApiAuthentication $authentication;

    public function __construct(ApiAuthentication $authentication)
    {
        $this->authentication = $authentication;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('api:token:create')
            ->addOption('ApplicationName', 'n', InputOption::VALUE_OPTIONAL, 'Application Name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getOption('ApplicationName');
        $helper = new QuestionHelper();
        if (!$name) {
            $name = $helper->ask($input, $output, new Question('Enter application name: '));
        }
        $token = $this->authentication->createNewApplicationToken($name);

        $output->writeln('New token generated: ' . $token->getToken());
        return 0;
    }
}
