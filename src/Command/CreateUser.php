<?php

namespace Pantono\Authentication\Command;

use Symfony\Component\Console\Command\Command;
use Pantono\Authentication\UserAuthentication;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Pantono\Utilities\StringUtilities;
use Pantono\Authentication\Model\User;

class CreateUser extends Command
{
    private UserAuthentication $authentication;

    public function __construct(UserAuthentication $authentication)
    {
        $this->authentication = $authentication;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('user:create')
            ->addOption('email', 'e', InputArgument::OPTIONAL)
            ->addOption('forename', 'f', InputArgument::OPTIONAL)
            ->addOption('surname', 'p', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getOption('email');
        $helper = new QuestionHelper();
        if (!$email) {
            $email = $helper->ask($input, $output, new Question('Enter user e-mail address: '));
        }
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('E-mail is invalid');
        }

        $forename = $input->getOption('forename');
        if (!$forename) {
            $forename = $helper->ask($input, $output, new Question('Enter forename: '));
        }
        if (!$forename) {
            throw new \RuntimeException('Forename is invalid');
        }

        $surname = $input->getOption('surname');
        if (!$surname) {
            $surname = $helper->ask($input, $output, new Question('Enter surname: '));
        }
        if (!$surname) {
            throw new \RuntimeException('Surname is invalid');
        }

        $password = StringUtilities::generateRandomString(8);
        $user = new User();
        $user->setForename($forename);
        $user->setSurname($surname);
        $user->setPassword($password);
        $user->setDeleted(false);
        $user->setDisabled(false);
        $this->authentication->saveUser($user);

        $output->writeln('<success>User created!</success>');
        $output->writeln('Username: ' . $email);
        $output->write('Password: ' . $password);
        return 0;
    }
}
