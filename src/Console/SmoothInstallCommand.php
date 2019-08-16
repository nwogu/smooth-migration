<?php

namespace Nwogu\SmoothMigration\Console;

use Illuminate\Console\Command;
use Nwogu\SmoothMigration\Helpers\Constants;
use Nwogu\SmoothMigration\Repositories\SmoothMigrationRepository;

class SmoothInstallCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'smooth:install';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smooth:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install a Smooth Schema Migration Table';

    /**
     * SmoothMigrationRepository
     * 
     * @var \Nwogu\SmoothMigration\Repositories\SmoothMigrationRepository $repository
     */
    protected $repository;

    /**
     * Create a new smooth schema class.
     * 
     * @return void
     */
    public function __construct(SmoothMigrationRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->repository->createRepository();
    }

}