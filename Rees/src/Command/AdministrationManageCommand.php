<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'administration:manage',
    description: 'Manage administrators of the application',
)]
class AdministrationManageCommand extends Command
{
    protected static $defaultName = 'administration:manage';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Show all users
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $output->writeln("<comment>-- ALL USERS IN THE DATABASE --</comment>");
        foreach ($users as $user) {
            $output->writeln("<comment>USER -> ID: " . $user->getId() . " | E-mail address: " . $user->getEmail() .
            " | Name: " . $user->getName() .
            " | Is administrator: " . ($user->isAdmin() ? "yes" : "no") . "</comment>\n");
        }

        // Interactive questions
        $helper = $this->getHelper('question');
        $question1 = new Question("<info>User ID:</info> ");
        $question2 = new Question("<question>Promote the user to administrator (y/n) [y]:</question> ", "y");

        $ID = $helper->ask($input, $output, $question1);
        $promote = $helper->ask($input, $output, $question2);
        if (is_numeric($ID)) {
            // Check if user exists
            $user = $this->entityManager->getRepository(User::class)->find($ID);
            if ($user) {
                if ($promote == 'y') {
                    $isAdmin = 1;
                    $outputMsg = "\n<info>User successfully promoted to administrator.</info>";
                } elseif ($promote == 'n') {
                    $isAdmin = 0;
                    $outputMsg = "\n<info>Administrator successfully retrograted to user.</info>";
                } else {
                    $output->writeln("\n<error>[ERROR] Promotion value incorrect.</error>");
                    return Command::FAILURE;
                }

                // SQL
                $sql = "UPDATE user SET admin = :is_admin WHERE id = :id;";
                $statement = $this->entityManager->getConnection()->prepare($sql);
                $statement->bindValue('is_admin', $isAdmin);
                $statement->bindValue('id', $ID);
                $statement->executeStatement();
                $output->writeln($outputMsg);
                return Command::SUCCESS;
            }
            $output->writeln("\n<error>[ERROR] User does not exist.</error>");
            return Command::FAILURE;
        }
        $output->writeln("\n<error>[ERROR] Incorrect user ID.</error>");
        return Command::FAILURE;
    }
}
