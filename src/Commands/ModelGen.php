<?php
/**
 * @date: 9:22 2023/4/10
 */

namespace WenGen\Commands;

use Illuminate\Console\Command;
use WenGen\Generators\Model\Generator as ModelGenerator;

class ModelGen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:model {tableName?} {modelClass?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate a new model';

    protected ModelGenerator $generator;

    public function __construct(ModelGenerator $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->generator->tableName = $this->argument('tableName') ?? '*';
        $this->generator->modelClass = $this->argument('modelClass');
        $this->info("Running '{$this->generator->getName()}'...");
        $this->generateCode();
    }

    protected function generateCode()
    {
        $files = $this->generator->generate();
        $n = count($files);
        if ($n === 0) {
            echo "No code to be generated.\n";
            return;
        }
        echo "The following files will be generated:\n";
        $skipAll = null;
        $answers = [];
        $choice_question = <<<EOF
Do you want to overwrite this file?
    y:Overwrite this file.
    n:Skip this file.
    ya:Overwrite this and the rest of the changed files.
    na:Skip this and the rest of the changed files.
    v:View difference.
EOF;

        foreach ($files as $file) {
            $path = $file->getRelativePath();
            if (is_file($file->path)) {
                $existingFileContents = file_get_contents($file->path);
                if ($existingFileContents === $file->content) {
                    echo '  ';
                    $this->info('[unchanged]');
                    $this->info(" $path\n");
                    $answers[$file->id] = false;
                } else {
                    echo '    ';
                    $this->info('[changed]');
                    $this->info(" $path\n");
                    if ($skipAll !== null) {
                        $answers[$file->id] = !$skipAll;
                    } else {
                        do {
                            $answer = $this->choice($choice_question, [
                                'y' ,
                                'n' ,
                                'ya' ,
                                'na' ,
                                'v' ,
                            ], 1);

                            if ($answer === 'v') {
                                $diff = new \Diff(explode("\n", $existingFileContents), explode("\n", $file->content));
                                echo $diff->render(new \Diff_Renderer_Text_Unified());
                            }
                        } while ($answer === 'v');

                        $answers[$file->id] = $answer === 'y' || $answer === 'ya';
                        if ($answer === 'ya') {
                            $skipAll = false;
                        } elseif ($answer === 'na') {
                            $skipAll = true;
                        }
                    }
                }
            } else {
                echo '        ';
                $this->info('[new]');
                $this->info(" $path\n");
                $answers[$file->id] = true;
            }
        }

        if (!array_sum($answers)) {
           $this->info("\nNo files were chosen to be generated.\n");
            return;
        }

        if (!app()->environment('testing')) {
            if (!$this->confirm("\nReady to generate the selected files?", true)) {
                $this->info("\nNo file was generated.\n");
                return;
            }
        }

        if ($this->generator->save($files, (array) $answers, $results)) {
            $this->info("\nFiles were generated successfully!\n");
        } else {
            $this->error("\nSome errors occurred while generating the files.");
        }
        echo preg_replace('%<span class="error">(.*?)</span>%', '\1', $results) . "\n";
    }
}
