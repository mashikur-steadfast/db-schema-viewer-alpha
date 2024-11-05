<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PDO;
use ZipArchive;

class BackupCommand extends Command
{
    protected $signature = 'backup:run {--database : Backup only database} {--files : Backup only files}';
    protected $description = 'Backup database and files. Use --database for database only, --files for files only, or neither for both.';
    //config
    private $maxBackups = 1;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $backupDatabase = $this->option('database');
        $backupFiles = $this->option('files');

        // If no specific option is provided, backup both
        if (!$backupDatabase && !$backupFiles) {
            $this->info('Backing up both database and files...');
            $this->backupDatabase();
            $this->backupFiles();
        } else {
            // Selective backup based on options
            if ($backupDatabase) {
                $this->info('Backing up database only...');
                $this->backupDatabase();
            }
            if ($backupFiles) {
                $this->info('Backing up files only...');
                $this->backupFiles();
            }
        }

        $this->info('Backup process completed!');
    }

    public function backupDatabase()
    {
        $filename = 'database_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $path = storage_path('app/backups/database/' . $filename);

        // Ensure the backup directory exists
        File::makeDirectory(dirname($path), 0755, true, true);

        // Get database configuration
        $host = env('DB_HOST', '');
        $username = env('DB_USERNAME', '');
        $password = env('DB_PASSWORD', '');
        $database = env('DB_DATABASE', '');

        // Check database connection
        try {
            $dsn = "mysql:host=$host;dbname=$database";
            $pdo = new \PDO($dsn, $username, $password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            \Log::error('Database connection failed: ' . $e->getMessage());
            return false;
        }

        $this->info('Trying to dump database using mysqldump...');
        \Log::info('Trying to dump database using mysqldump...');

        // Attempt mysqldump first
        $command = "mysqldump --user={$username} --password={$password} --host={$host} {$database} > {$path} 2>&1";

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->warn('mysqldump failed, falling back to PHP-based backup...');
            \Log::warning('mysqldump failed, falling back to PHP-based backup...');

            // Fallback to PHP-based backup
            try {
                $tables = [];
                $result = $pdo->query('SHOW TABLES');
                while ($row = $result->fetch(\PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }

                $sql = "SET FOREIGN_KEY_CHECKS=0;\n\n";
                foreach ($tables as $table) {
                    $result = $pdo->query("SELECT * FROM `$table`");
                    $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                    $row2 = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_NUM);
                    $sql .= $row2[1] . ";\n\n";
                    while ($row = $result->fetch(\PDO::FETCH_NUM)) {
                        $sql .=
                            "INSERT INTO `$table` VALUES(" .
                            implode(
                                ',',
                                array_map(function ($value) use ($pdo) {
                                    if ($value === null) {
                                        return 'NULL';
                                    }
                                    return $pdo->quote($value);
                                }, $row),
                            ) .
                            ");\n";
                    }
                    $sql .= "\n\n";
                }
                $sql .= 'SET FOREIGN_KEY_CHECKS=1;';

                file_put_contents($path, $sql);
                $this->info('PHP-based backup completed successfully');
                \Log::info('PHP-based backup completed successfully');
            } catch (\Exception $e) {
                $this->error('PHP-based backup failed: ' . $e->getMessage());
                \Log::error('PHP-based backup failed: ' . $e->getMessage());
                return false;
            }
        } else {
            $this->info('mysqldump backup completed successfully');
            \Log::info('mysqldump backup completed successfully');
        }

        $this->info('Database backup completed: ' . $filename);
        \Log::info('Database backup completed: ' . $filename);

        $this->deletePrevious('database');

        return true;
    }

    public function backupFiles()
    {
        $filename = 'files_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $path = storage_path('app/backups/files/' . $filename);

        File::makeDirectory(dirname($path), 0755, true, true);

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $storagePath = storage_path();
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($storagePath), \RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($storagePath) + 1);

                    if (!$this->shouldExclude($relativePath)) {
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            }

            $zip->close();

            $this->info('Files backup completed: ' . $filename);
            \Log::info('Files backup completed: ' . $filename);

            $this->deletePrevious('files');
        } else {
            $this->error('Failed to create zip file');
            \Log::error('Failed to create zip file');
        }
    }

    private function shouldExclude($path)
    {
        $excludeList = ['app/backups', '.gitignore'];

        foreach ($excludeList as $excluded) {
            if (strpos($path, $excluded) !== false) {
                return true;
            }
        }

        return false;
    }

    private function deletePrevious($type)
    {
        $backupPath = storage_path("app/backups/{$type}");

        $files = File::files($backupPath);
        
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        if (count($files) > $this->maxBackups) {
            $filesToDelete = array_slice($files, $this->maxBackups);

            foreach ($filesToDelete as $file) {
                try {
                    File::delete($file);
                    $this->info("Deleted old backup: " . basename($file));
                    \Log::info("Deleted old backup: " . basename($file));
                } catch (\Exception $e) {
                    $this->error("Failed to delete backup file {$file}: " . $e->getMessage());
                    \Log::error("Failed to delete backup file {$file}: " . $e->getMessage());
                }
            }

            $this->info("Kept {$this->maxBackups} latest {$type} backups, deleted " . count($filesToDelete) . " old backup(s)");
            \Log::info("Kept {$this->maxBackups} latest {$type} backups, deleted " . count($filesToDelete) . " old backup(s)");
        }
    }
}