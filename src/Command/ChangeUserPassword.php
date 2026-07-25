<?php

namespace Pantono\Authentication\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Pantono\Utilities\StringUtilities;
use Pantono\Authentication\Users;

class ChangeUserPassword extends Command
{
    private Users $users;

    public function __construct(Users $users)
    {
        $this->users = $users;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('user:create')
            ->addArgument('id', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $password = $input->getArgument('password');
        if (!$password) {
            $password = StringUtilities::generateRandomString(8);
        }
        $user = $this->users->getUserById($id);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        $this->users->updateUserPassword($user, $password);

        $output->writeln('<success>User password updated!</success>');
        if (!$input->hasArgument('password')) {
            $output->writeln('<success>Password is: ' . $password . '</success>');
        }
        return 0;
    }
}
